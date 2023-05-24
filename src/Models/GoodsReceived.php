<?php

namespace Rutatiina\GoodsReceived\Models;

use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rutatiina\Inventory\Scopes\StatusEditedScope;

class GoodsReceived extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use Cloneable;

    protected static $logName = 'Txn';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_goods_received';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'contact_id' => 'integer',
        'canceled' => 'integer',
    ];

    protected $cloneable_relations = [
        'items', 
        'comments'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    protected $appends = [
        'number_string',
        'total_in_words',
        'ledgers'
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
        static::addGlobalScope(new StatusEditedScope);

        self::deleting(function($txn) {
             $txn->items()->each(function($row) {
                $row->delete();
             });
             $txn->inputs()->each(function($row) {
                $row->delete();
             });
             $txn->comments()->each(function($row) {
                $row->delete();
             });
        });

        self::restored(function($txn) {
             $txn->items()->each(function($row) {
                $row->restore();
             });
             $txn->inputs()->each(function($row) {
                $row->restore();
             });
             $txn->comments()->each(function($row) {
                $row->restore();
             });
        });

    }
    
    /**
     * Scope a query to only include approved records users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    /**
     * Scope a query to only include not canceled records
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotCancelled($query)
    {
        return $query->where(function($q) {
            $q->where('canceled', 0);
            $q->orWhereNull('canceled');
        });
    }

    public function rgGetAttributes()
    {
        $attributes = [];
        $describeTable =  \DB::connection('tenant')->select('describe ' . $this->getTable());

        foreach ($describeTable  as $row) {

            if (in_array($row->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id', 'user_id'])) continue;

            if (in_array($row->Field, ['currencies', 'taxes'])) {
                $attributes[$row->Field] = [];
                continue;
            }

            if ($row->Default == '[]') {
                $attributes[$row->Field] = [];
            } else {
                $attributes[$row->Field] = ''; //$row->Default; //null affects laravel validation
            }
        }

        //add the relationships
        $attributes['type'] = [];
        $attributes['debit_account'] = [];
        $attributes['credit_account'] = [];
        $attributes['items'] = [];
        $attributes['ledgers'] = [];
        $attributes['comments'] = [];
        $attributes['contact'] = [];
        $attributes['recurring'] = [];

        return $attributes;
    }

    public function getContactAddressArrayAttribute()
    {
        return preg_split("/\r\n|\n|\r/", $this->contact_address);
    }

    public function getNumberStringAttribute()
    {
        return $this->number_prefix.(str_pad(($this->number), $this->number_length, "0", STR_PAD_LEFT)).$this->number_postfix;
    }

    public function getTotalInWordsAttribute()
    {
        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        return ucfirst($f->format($this->total));
    }

    public function items()
    {
        return $this->hasMany('Rutatiina\GoodsReceived\Models\GoodsReceivedItem', 'goods_received_id')->orderBy('id', 'asc');
    }

    public function inputs()
    {
        return $this->hasMany('Rutatiina\GoodsReceived\Models\GoodsReceivedInput', 'goods_received_id')->orderBy('id', 'asc');
    }

    public function getLedgersAttribute($txn = null)
    {
        // if (!$txn) $this->items;

        $txn = $txn ?? $this;

        $txn = (is_object($txn)) ? $txn : collect($txn);

        //If the credit_financial_account_code has not been set, clear the ledgers, there is no need for ledger entries
        if (empty($txn->credit_financial_account_code)) return collect([]);
        
        $ledgers = [];

        foreach ($txn->items as $item)
        {
            $taxable_amount = $item->taxable_amount ?? $item->total;

            //DR ledger
            $ledgers[$item->debit_financial_account_code]['financial_account_code'] = $item->debit_financial_account_code;
            $ledgers[$item->debit_financial_account_code]['effect'] = 'debit';
            $ledgers[$item->debit_financial_account_code]['total'] = ($ledgers[$item->debit_financial_account_code]['total'] ?? 0) + $taxable_amount;
            $ledgers[$item->debit_financial_account_code]['contact_id'] = $txn->contact_id;
        }

        //CR ledger
        $ledgers[] = [
            'financial_account_code' => $txn->credit_financial_account_code,
            'effect' => 'credit',
            'total' => $txn->total,
            'contact_id' => $txn->contact_id
        ];

        foreach ($ledgers as &$ledger)
        {
            $ledger['tenant_id'] = $txn->tenant_id;
            $ledger['date'] = $txn->date;
            $ledger['base_currency'] = $txn->base_currency;
            $ledger['quote_currency'] = $txn->quote_currency;
            $ledger['exchange_rate'] = $txn->exchange_rate;
        }
        unset($ledger);

        return collect($ledgers);
    }

    public function comments()
    {
        return $this->hasMany('Rutatiina\GoodsReceived\Models\GoodsReceivedComment', 'goods_received_id')->latest();
    }

    public function contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'contact_id');
    }

}

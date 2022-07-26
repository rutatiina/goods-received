<?php

namespace Rutatiina\GoodsReceived\Classes;

use Illuminate\Support\Facades\Auth;
use Rutatiina\FinancialAccounting\Models\Txn;

use Rutatiina\GoodsReceived\Traits\Init as TxnTraitsInit;
use Rutatiina\GoodsReceived\Traits\Item as TxnItem;

class Create
{
    use TxnTraitsInit;
    use TxnItem; // >> get the item attributes template << !!important

    public function __construct()
    {}

    public function run()
    {
        $tenant = Auth::user()->tenant;

        $Txn = new Txn;
        $txnAttributes = $Txn->rgGetAttributes();

        if ($this->txnEntreeSlug) {
            $TxnNumber = new Number();
            $txnAttributes['number'] = $TxnNumber->run($this->txnEntreeSlug);
        }

        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = false;
        $txnAttributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [$this->itemCreate()];

        return $txnAttributes;
    }

}

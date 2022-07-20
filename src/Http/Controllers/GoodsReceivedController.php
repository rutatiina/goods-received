<?php

namespace Rutatiina\GoodsReceived\Http\Controllers;

use Rutatiina\GoodsReturned\Models\GoodsReturnedSetting;
use Rutatiina\GoodsReturned\Models\GoodsReturned;
use URL;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\View;
use Rutatiina\GoodsReceived\Models\GoodsReceived;
use Rutatiina\FinancialAccounting\Classes\Transaction;
use Rutatiina\FinancialAccounting\Models\Entree;
use Rutatiina\Contact\Traits\ContactTrait;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\GoodsReceived\Classes\Store as TxnStore;
use Rutatiina\GoodsReceived\Classes\Approve as TxnApprove;
use Rutatiina\GoodsReceived\Classes\Read as TxnRead;
use Rutatiina\GoodsReceived\Classes\Copy as TxnCopy;
use Rutatiina\GoodsReceived\Classes\Number as TxnNumber;
use Rutatiina\GoodsReceived\Traits\Item as TxnItem;
use Rutatiina\GoodsReceived\Classes\Edit as TxnEdit;
use Rutatiina\GoodsReceived\Classes\Update as TxnUpdate;

class GoodsReceivedController extends Controller
{
    //use TenantTrait;
    use ContactTrait;
    use FinancialAccountingTrait;
    use TxnItem;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:goods-received.view');
        $this->middleware('permission:goods-received.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:goods-received.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:goods-received.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $per_page = ($request->per_page) ? $request->per_page : 20;

        $txns = GoodsReceived::latest()->paginate($per_page);

        return [
            'tableData' => $txns
        ];
    }

    private function nextNumber()
    {
        $txn = GoodsReceived::latest()->first();
        $settings = GoodsReturnedSetting::first();

        return $settings->number_prefix . (str_pad((optional($txn)->number + 1), $settings->minimum_number_length, "0", STR_PAD_LEFT)) . $settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new GoodsReceived())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();

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

        return [
            'pageTitle' => 'Create Goods Received Note', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/goods-received', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function store(Request $request)
    {
        $TxnStore = new TxnStore();
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false)
        {
            return [
                'status' => false,
                'messages' => $TxnStore->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods Received Note saved'],
            'number' => 0,
            'callback' => URL::route('goods-received.show', [$insert->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        if (FacadesRequest::wantsJson())
        {
            $TxnRead = new TxnRead();
            return $TxnRead->run($id);
        }
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $TxnEdit = new TxnEdit();
        $txnAttributes = $TxnEdit->run($id);

        $data = [
            'pageTitle' => 'Edit Good received note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/goods-received/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $TxnStore = new TxnUpdate();
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false)
        {
            return [
                'status' => false,
                'messages' => $TxnStore->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods received note updated'],
            'number' => 0,
            'callback' => URL::route('goods-received.show', [$insert->id], false)
        ];
    }

    public function destroy()
    {
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $TxnApprove = new TxnApprove();
        $approve = $TxnApprove->run($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => $TxnApprove->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods Received Note approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $TxnCopy = new TxnCopy();
        $txnAttributes = $TxnCopy->run($id);

        $TxnNumber = new TxnNumber();
        $txnAttributes['number'] = $TxnNumber->run($this->txnEntreeSlug);

        $data = [
            'pageTitle' => 'Copy Goods Received Note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/goods-received', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function datatables()
    {
        $txns = Transaction::setRoute('show', route('accounting.inventory.goods-received.show', '_id_'))
            ->setRoute('edit', route('accounting.inventory.goods-received.edit', '_id_'))
            ->paginate(false)
            ->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request)
    {
        $txns = collect([]);

        $txns->push([
            'DATE',
            'DOCUMENT#',
            'REFERENCE',
            'CUSTOMER',
            'STATUS',
            'EXPIRY DATE',
            'TOTAL',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id)
        {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->reference,
                $txn->contact_name,
                $txn->status,
                $txn->expiry_date,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-goods-received-export-' . date('Y-m-d-H-m-s') . '.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }
}

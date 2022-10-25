<?php

namespace Rutatiina\GoodsReceived\Http\Controllers;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\Facades\DataTables;
use Rutatiina\Contact\Traits\ContactTrait;
use Rutatiina\GoodsReceived\Models\GoodsReceived;
use Rutatiina\GoodsReceived\Traits\Item as TxnItem;
use Rutatiina\GoodsReceived\Classes\Copy as TxnCopy;

use Rutatiina\GoodsReceived\Classes\Edit as TxnEdit;
use Rutatiina\GoodsReceived\Classes\Read as TxnRead;
use Rutatiina\FinancialAccounting\Classes\Transaction;
use Rutatiina\GoodsReceived\Classes\Number as TxnNumber;
use Rutatiina\GoodsReceived\Classes\Update as TxnUpdate;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\GoodsReceived\Classes\Approve as TxnApprove;
use Rutatiina\GoodsReceived\Services\GoodsReceivedService;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;

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

        $txns = GoodsReceived::with('items')->latest()->paginate($per_page);

        return [
            'tableData' => $txns,
            'routes' => $this->routes()
        ];
    }

    private function nextNumber()
    {
        $txn = GoodsReceived::latest()->first();
        $settings = GoodsReceivedService::settings();

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
        // return $request;

        $storeService = GoodsReceivedService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods received note saved'],
            'number' => 0,
            'callback' => URL::route('goods-received.show', [$storeService->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txn = GoodsReceived::findOrFail($id);
        $txn->load('contact', 'items');
        $txn->setAppends([
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();

    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = GoodsReceivedService::edit($id);

        return [
            'pageTitle' => 'Edit Goods received note', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/goods-received/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function update(Request $request)
    {
        //print_r($request->all()); exit;

        $storeService = GoodsReceivedService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Goods received note updated'],
            'number' => 0,
            'callback' => URL::route('goods-received.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
    {
        if (GoodsReceivedService::destroy($id))
        {
            return [
                'status' => true,
                'messages' => ['Goods received note deleted'],
                'callback' => URL::route('goods-received.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        if (GoodsReceivedService::approve($id))
        {
            return [
                'status' => true,
                'messages' => ['Goods received note approved.'],
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txnAttributes = GoodsReceivedService::copy($id);

        return [
            'pageTitle' => 'Copy Goods delivered note', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/goods-received', #required
            'txnAttributes' => $txnAttributes, #required
        ];
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
            $txn = GoodsReceived::transaction($id);
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

    public function routes()
    {
        return [
            'delete' => route('goods-received.delete'),
            'cancel' => route('goods-received.cancel'),
        ];
    }

    public function delete(Request $request)
    {
        if (GoodsReceivedService::destroyMany($request->ids))
        {
            return [
                'status' => true,
                'messages' => [count($request->ids) . ' Goods received note(s) deleted.'],
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }
    }

    public function cancel(Request $request)
    {
        if (GoodsReceivedService::cancelMany($request->ids))
        {
            return [
                'status' => true,
                'messages' => [count($request->ids) . ' Goods received note(s) canceled.'],
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => GoodsReceivedService::$errors
            ];
        }
    }
}

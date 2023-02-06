<?php

namespace Rutatiina\GoodsReceived\Services;

use Illuminate\Support\Facades\Validator;
use Rutatiina\Contact\Models\Contact;
use Rutatiina\GoodsReceived\Models\GoodsReceivedSetting;
use Rutatiina\Item\Models\Item;

class GoodsReceivedValidateService
{
    public static $errors = [];

    public static function run($requestInstance)
    {
        //$request = request(); //used for the flash when validation fails
        $user = auth()->user();


        // >> data validation >>------------------------------------------------------------

        //validate the data
        $customMessages = [
            //'total.in' => "Item total is invalid:\nItem total = item rate x item quantity",
        ];

        $rules = [
            'contact_id' => 'numeric|nullable',
            'date' => 'required|date',
            'salesperson_contact_id' => 'numeric|nullable',
            'memo' => 'string|nullable',

            'items' => 'required|array',
            'items.*.name' => 'required_without:item_id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.units' => 'numeric|nullable',

            'inputs' => 'nullable|array',
            'inputs.*.name' => 'required_without:item_id',
            'inputs.*.quantity' => 'required|numeric|gt:0',
            // 'inputs.*.units' => 'numeric|nullable',

        ];

        $validator = Validator::make($requestInstance->all(), $rules, $customMessages);

        if ($validator->fails())
        {
            self::$errors = $validator->errors()->all();
            return false;
        }

        // << data validation <<------------------------------------------------------------

        $settings = GoodsReceivedSetting::firstOrFail();
        //Log::info($this->settings);

        $contact = Contact::find($requestInstance->contact_id);


        $data['id'] = $requestInstance->input('id', null); //for updating the id will always be posted
        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant->id;
        $data['created_by'] = $user->name;
        $data['app'] = 'web';
        $data['document_name'] = $settings->document_name;
        $data['number_prefix'] = $settings->number_prefix;
        $data['number'] = $requestInstance->input('number');
        $data['number_length'] = $settings->minimum_number_length;
        $data['number_postfix'] = $settings->number_postfix;
        $data['date'] = $requestInstance->input('date');
        $data['credit_financial_account_code'] = $requestInstance->input('credit_financial_account_code', null);
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = optional($contact)->name;
        $data['contact_address'] = trim(optional($contact)->shipping_address_street1 . ' ' . optional($contact)->shipping_address_street2);
        $data['reference'] = $requestInstance->input('reference', null);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = (empty($requestInstance->input('exchange_rate'))) ? 1 : $requestInstance->input('exchange_rate');
        $data['salesperson_contact_id'] = $requestInstance->input('salesperson_contact_id', null);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['due_date'] = $requestInstance->input('due_date', null);
        $data['terms_and_conditions'] = $requestInstance->input('terms_and_conditions', null);
        $data['contact_notes'] = $requestInstance->input('contact_notes', null);
        $data['status'] = strtolower($requestInstance->input('status', null));

        $data['total'] = 0;
        $data['ledgers'] = [];

        //Formulate the DB ready items array
        $data['items'] = [];
        foreach ($requestInstance->items as $key => $item)
        {
            $data['total'] += ($item['rate']*$item['quantity']);

            //use item selling_financial_account_code if available and default if not
            $financialAccountToDebit = $item['debit_financial_account_code'];

            //get the item
            $itemModel = Item::find($item['item_id']);

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'item_id' => $item['item_id'],
                'debit_financial_account_code' => $financialAccountToDebit,
                'financial_account_code' => $financialAccountToDebit,
                'name' => $item['name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'units' => ($item['quantity']*$itemModel['units']), //$requestInstance->input('items.'.$key.'.units', null),
                'rate' => $item['rate'],
                'total' => $item['total'],
                'batch' => $requestInstance->input('items.'.$key.'.batch', null),
                'expiry' => $requestInstance->input('items.'.$key.'.expiry', null),
                'inventory_tracking' => ($itemModel->inventory_tracking ?? 0),
            ];

            //DR ledger
            $data['ledgers'][$financialAccountToDebit]['financial_account_code'] = $financialAccountToDebit;
            $data['ledgers'][$financialAccountToDebit]['effect'] = 'debit';
            $data['ledgers'][$financialAccountToDebit]['total'] = @$data['ledgers'][$financialAccountToDebit]['total'] + $item['total'];
            $data['ledgers'][$financialAccountToDebit]['contact_id'] = $data['contact_id'];

        }

        //Formulate the DB ready inputs array
        $data['inputs'] = [];
        foreach ($requestInstance->inputs as $key => $item)
        {
            // $data['total'] += ($item['rate']*$item['quantity']);

            //use item selling_financial_account_code if available and default if not
            $financialAccountToCredit = $item['credit_financial_account_code'];

            //get the item
            $itemModel = Item::find($item['item_id']);

            $data['inputs'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'item_id' => $item['item_id'],
                'credit_financial_account_code' => $financialAccountToCredit,
                'financial_account_code' => $financialAccountToCredit,
                'name' => $item['name'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'units' => ($item['quantity']*$itemModel['units']), //$requestInstance->input('items.'.$key.'.units', null),
                'rate' => $item['rate'],
                'total' => $item['total'],
                'batch' => $requestInstance->input('items.'.$key.'.batch', null),
                'expiry' => $requestInstance->input('items.'.$key.'.expiry', null),
                'inventory_tracking' => ($itemModel->inventory_tracking ?? 0),
            ];
        }
        
        //CR ledger
        $data['ledgers'][] = [
            'financial_account_code' => $data['credit_financial_account_code'],
            'effect' => 'credit',
            'total' => $data['total'],
            'contact_id' => $data['contact_id']
        ];

        //Now add the default values to items and ledgers

        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['tenant_id'] = $data['tenant_id'];
            $ledger['date'] = date('Y-m-d', strtotime($data['date']));
            $ledger['base_currency'] = $data['base_currency'];
            $ledger['quote_currency'] = $data['quote_currency'];
            $ledger['exchange_rate'] = $data['exchange_rate'];
        }
        unset($ledger);

        //If the credit_financial_account_code has not been set, clear the ledgers, there is no need for ledger entries
        if (empty($data['credit_financial_account_code'])) $data['ledgers'] = [];

        //Return the array of txns
        //print_r($data); exit;

        return $data;

    }

}

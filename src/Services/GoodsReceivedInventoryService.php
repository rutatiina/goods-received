<?php

namespace Rutatiina\GoodsReceived\Services;

use Rutatiina\Inventory\Models\Inventory;
use Rutatiina\Item\Models\Item;

class GoodsReceivedInventoryService
{
    private static function record($transaction, $item)
    {
        $item['batch'] = (isset($item['batch'])) ? $item['batch'] : '';
        $financialAccountCode = $item['debit_financial_account_code'] ?? $item['credit_financial_account_code'];

        $inventory = Inventory::whereDate('date', '<=', $transaction['date'])
            ->where([
                'tenant_id' => $item['tenant_id'], 
                'project_id' => @$transaction['project_id'], 
                'item_id' => $item['item_id'],
                'batch' => $item['batch'],
            ])
            ->orderBy('date', 'desc')
            ->first();

        //var_dump($accountBalance); exit;
        //Log::info('>>Last account balance entry for account id::'.$ledger['financial_account_code'].' in '.$currency.' date: '.$ledger['date'].': '.$ledger['effect'].' '.$ledger['total']);
        //Log::info($ledger);
        //Log::info($accountBalance);

        if ($inventory)
        {
            //create a new row with the last balances
            if ($transaction['date'] == $inventory->date)
            {
                //do nothing because the records for this dates balances already exists
            }
            else
            {
                $inventoryModel = new Inventory;
                $inventoryModel->tenant_id = $item['tenant_id'];
                $inventoryModel->date = $transaction['date'];
                $inventoryModel->financial_account_code = $financialAccountCode;
                $inventoryModel->item_id = $item['item_id'];
                $inventoryModel->batch = $item['batch'];
                $inventoryModel->units_received = $inventory->units_received;
                $inventoryModel->units_delivered = $inventory->units_delivered;
                $inventoryModel->units_issued = $inventory->units_issued;
                $inventoryModel->units_returned = $inventory->units_returned;
                $inventoryModel->units_available = $inventory->units_available;
                $inventoryModel->save();

            }

        }
        else
        {

            //create a new balance record
            $inventoryModel = new Inventory;
            $inventoryModel->tenant_id = $item['tenant_id'];
            $inventoryModel->date = $transaction['date'];
            $inventoryModel->financial_account_code = $financialAccountCode;
            $inventoryModel->item_id = $item['item_id'];
            $inventoryModel->batch = $item['batch'];
            $inventoryModel->save();

        }

        return Inventory::whereDate('date', '>=', $transaction['date'])
            ->where([
                'financial_account_code' => ($financialAccountCode ?? null), 
                'tenant_id' => $item['tenant_id'], 
                'project_id' => @$transaction['project_id'], 
                'item_id' => $item['item_id'],
                'batch' => $item['batch'],
            ]);
    }

    public static function update($data)
    {
        if ($data['status'] != 'approved') return false; //can only update balances if status is approved
        $inventory_items = (isset($data['inventory_items'])) ? $data['inventory_items'] : $data['items'];

        //Update the inventory summary
        foreach ($inventory_items as $item)
        {
            if (!isset($item['inventory_tracking']))
            {
                if(is_numeric($item['item_id']))
                {
                    $item['inventory_tracking'] = optional(Item::find($item['item_id']))->inventory_tracking;
                }
                else
                {
                    continue;
                }
            }

            if ($item['inventory_tracking'] == 0) continue;

            $inventory = self::record($data, $item);

            //increase the 
            $inventory->increment('units_received', $item['units']);
            $inventory->increment('units_available', $item['units']);

        }

        foreach ($data['inputs'] as $input)
        {
            if (!isset($input['inventory_tracking']))
            {
                if(is_numeric($input['item_id']))
                {
                    $input['inventory_tracking'] = optional(Item::find($input['item_id']))->inventory_tracking;
                }
                else
                {
                    continue;
                }
            }

            if ($input['inventory_tracking'] == 0) continue;

            $inventory = self::record($data, $input);

            //increase the 
            $inventory->decrement('units_received', $input['units']);
            $inventory->decrement('units_available', $input['units']);

        }

        return true;
    }

    public static function reverse($data)
    {
        if ($data['status'] != 'approved') return false; //can only update balances if status is approved
        
        //Update the inventory summary
        foreach ($data['items'] as $item)
        {
            if (!isset($item['inventory_tracking']))
            {
                if(is_numeric($item['item_id']))
                {
                    $item['inventory_tracking'] = optional(Item::find($item['item_id']))->inventory_tracking;
                }
                else
                {
                    continue;
                }
            }

            if ($item['inventory_tracking'] == 0) continue;
            
            $inventory = self::record($data, $item);

            //increase the 
            $inventory->decrement('units_received', $item['units']);
            $inventory->decrement('units_available', $item['units']);
        }

        foreach ($data['inputs'] as $input)
        {
            if (!isset($input['inventory_tracking']))
            {
                if(is_numeric($input['item_id']))
                {
                    $input['inventory_tracking'] = optional(Item::find($input['item_id']))->inventory_tracking;
                }
                else
                {
                    continue;
                }
            }

            if ($input['inventory_tracking'] == 0) continue;
            
            $inventory = self::record($data, $input);

            //increase the 
            $inventory->increment('units_received', $input['units']);
            $inventory->increment('units_available', $input['units']);
        }

        return true;
    }

}

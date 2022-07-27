<?php

namespace Rutatiina\GoodsReceived\Services;

use Rutatiina\GoodsReceived\Models\GoodsReceivedItem;
use Rutatiina\GoodsReceived\Models\GoodsReceivedItemTax;

class GoodsReceivedItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['goods_delivered_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = GoodsReceivedItem::create($item);

        }
        unset($item);

    }

}

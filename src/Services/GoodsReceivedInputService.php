<?php

namespace Rutatiina\GoodsReceived\Services;

use Rutatiina\GoodsReceived\Models\GoodsReceivedInput;

class GoodsReceivedInputService
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
        foreach ($data['inputs'] as &$input)
        {
            $input['goods_received_id'] = $data['id'];

            GoodsReceivedInput::create($input);

        }
        unset($input);

    }

}

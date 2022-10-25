<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDebitFinancialAccountCodeColumnsToRgGoodsReceivedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('rg_goods_received_items', function (Blueprint $table) {
            $table->unsignedBigInteger('debit_financial_account_code')->nullable()->after('item_id');

            $table->unsignedDecimal('rate', 20,5)->after('units');
            $table->unsignedDecimal('total', 20, 5)->after('rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->table('rg_goods_received_items', function (Blueprint $table) {
            $table->dropColumn('debit_financial_account_code');
            $table->dropColumn('rate');
            $table->dropColumn('total');
        });
    }
}

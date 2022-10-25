<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreditFinancialAccountCodeColumnsToRgGoodsReceivedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('rg_goods_received', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_financial_account_code')->nullable()->after('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->table('rg_goods_received', function (Blueprint $table) {
            $table->dropColumn('credit_financial_account_code');
        });
    }
}

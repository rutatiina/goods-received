<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalColumnsToRgGoodsReceivedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('rg_goods_received', function (Blueprint $table) {
            $table->string('base_currency', 3)->after('reference');
            $table->string('quote_currency', 3)->after('base_currency');
            $table->unsignedDecimal('exchange_rate', 20,10)->nullable()->after('quote_currency');
            $table->unsignedDecimal('total', 20, 5)->nullable()->after('exchange_rate');
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
            $table->dropColumn('base_currency');
            $table->dropColumn('quote_currency');
            $table->dropColumn('exchange_rate');
            $table->dropColumn('total');
        });
    }
}

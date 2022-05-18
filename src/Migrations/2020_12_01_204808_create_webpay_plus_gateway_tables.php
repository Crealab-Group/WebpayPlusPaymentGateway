<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateWebpayPlusGatewayTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webpay_payment_type', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->timestamps();
        });

        Schema::create('webpay_plus_payment', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->string('buy_order');
            $table->text('submit_url')->nullable()->default(null);
            $table->text('return_url');
            $table->string('token_ws')->index()->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('card_number')->default('0000');
            $table->string('card_expiration')->nullable()->default(null);
            $table->bigInteger('webpay_payment_type_id')->unsigned()->nullable();
            $table->foreign('webpay_payment_type_id')->references('id')->on('webpay_payment_type')->onDelete('CASCADE')->onUpdate('CASCADE');;
            $table->timestamps();
        });

        $this->insertWebpayStatusData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webpay_payment');
        Schema::dropIfExists('webpay_payment_type');
    }

    private function insertWebpayStatusData(){
        DB::table('webpay_payment_type')->insert([
            [
                'name' => 'Venta Normal',
                'key' => 'VN'
            ],[
                'name' => '2 Cuotas sin interés',
                'key' => 'S2',
            ],[
                'name' => '3 Cuotas sin interés',
                'key' => 'SI'
            ],[
                'name' => 'Cuotas sin interés',
                'key' => 'NC'
            ],[
                'name' => 'Cuotas normales',
                'key' => 'VC'
            ],[
                'name' => 'Venta débito Redcompra',
                'key'  => 'VD'
            ],[
                'name' => 'Venta Prepago',
                'key'  => 'VP'
            ]
        ]);
    }
}

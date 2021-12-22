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
        Schema::create('webpay_mall_payment', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->integer('refund_amount')->default(0);
            $table->string('buy_order');
            $table->text('submit_url')->nullable()->default(null);
            $table->text('return_url');
            $table->string('token_ws')->index()->nullable();
            $table->string('card_number')->default('0000');
            $table->string('card_expiration')->nullable()->default(null);
            $table->bigInteger('payment_id')->unsigned()->nullable();
            $table->foreign('payment_id')->references('id')->on('payment')->onDelete('CASCADE')->onUpdate('CASCADE');
            $table->timestamps();
        });

        Schema::create('webpay_mall_payment_subtransactions', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->string('buy_order');
            $table->string('authorization_code')->nullable();
            $table->integer('payment_fees')->nullable();
            $table->string('commerce_code');
            $table->integer('response_code')->nullable();
            $table->bigInteger('webpay_payment_type_id')->unsigned()->nullable();
            $table->foreign('webpay_payment_type_id')->references('id')->on('webpay_payment_type')->onDelete('CASCADE')->onUpdate('CASCADE');

            $table->bigInteger('webpay_plus_mall_payment_id')->unsigned()->nullable();
            $table->foreign('webpay_plus_mall_payment_id')->references('id')->on('webpay_plus_mall_payment')->onDelete('CASCADE')->onUpdate('CASCADE');

            $table->timestamps();
        });
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
}

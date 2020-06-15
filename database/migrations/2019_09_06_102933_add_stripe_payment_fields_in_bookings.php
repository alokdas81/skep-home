<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStripePaymentFieldsInBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('charge_id')->after('accept_at')->nullable();
            $table->string('amount_paid')->after('charge_id')->nullable();
            $table->string('transaction_id')->after('amount_paid')->nullable();
            $table->string('paid_status')->after('transaction_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('charge_id');
            $table->dropColumn('amount_paid');
            $table->dropColumn('transaction_id');
            $table->dropColumn('paid_status');
        });
    }
}

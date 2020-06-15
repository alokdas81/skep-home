<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCleanersPaymentInfoToBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('is_cleaner_paid')->after('paid_status')->nullable();
            $table->string('transfer_id')->after('is_cleaner_paid')->nullable();
            $table->string('amount_paid_cleaner')->after('transfer_id')->nullable();
            $table->string('stripe_payout_fees')->after('amount_paid_cleaner')->nullable();
            $table->string('skep_net_revenue')->after('stripe_payout_fees')->nullable();
            $table->string('transaction_id_cleaner')->after('skep_net_revenue')->nullable();
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
            $table->dropColumn('is_cleaner_paid');
            $table->dropColumn('transfer_id');
            $table->dropColumn('amount_paid_cleaner');
            $table->dropColumn('stripe_payout_fees'); 
            $table->dropColumn('skep_net_revenue');
            $table->dropColumn('transaction_id_cleaner');
        });
    }
}

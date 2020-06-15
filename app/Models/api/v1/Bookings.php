<?php

namespace App\Models\api\v1;
use Illuminate\Database\Eloquent\Model;
use App\Models\api\v1\StripeUserDetails;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Illuminate\Support\Facades\Log;
use App\Models\api\v1\UserReferralHistories;

class Bookings extends Model{

    protected $table = 'bookings';
    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';
    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */

    protected $fillable = ['user_id', 'service_provider_id', 'space_id', 'booking_services', 'booking_date', 'service_start_time', 'service_end_time', 'booking_hours', 'booking_price', 'booking_type', 'booking_frequency', 'booking_address', 'latitude', 'longitude', 'special_instructions', 'booking_status', 'created_at', 'updated_at','is_in_progress','job_id','platform_transfer_id','mass_blast_search','search_work_region','instant_invitation','stripe_refund_amount','balance_refund','advance_fav_cleaner_notify','exclude_previous_cleaner','transfer_by_charge_id','transfer_by_platform_balance','ratingGivenByCleaner', 'ratingGivenByHomeOwner'];

    /*      
* Function to save charges Api data 
*
*/
public static function SaveChargesStripeData(array $stripeData, $booking_id) {
    
        
        $query = \DB::table('bookings')
        ->where('id', $booking_id)  // find booking by id
        ->limit(1)  // optional - to ensure only one record is updated.
        ->update($stripeData);  // update the stripe data
        return $query;
  }


/*
     * @param user_id as cleaner id
     * check if referral amount is available or not
     * if available then apply 
     */

    public function applyCleanerReferralAmount($user_id)
    {    
        $referral_data = [];
        $is_applicable = false;
        $where = ['user_id' => $user_id, 'is_active' => 1,'is_used'=>0,'referral_type'=>'receiver'];
        $check_referral = UserReferralHistories::where($where)->first();       
        if(!empty($check_referral))
        {
            $is_applicable = true;
            $referral_data = array(
                                    'referral_id'=>$check_referral['id'],
                                    'referral_amount'=>$check_referral['referral_amount'],                                    
                                    'referral_group'=>$check_referral['referral_group'],
                                    'referral_type'=>$check_referral['referral_type']
                                );

        }
        else
        {
            $where = ['user_id' => $user_id, 'is_active' => 1,'is_used'=>0,'referral_type'=>'sender'];
            $check_referral = UserReferralHistories::where($where)->first();       
            if(!empty($check_referral))
            {
                $is_applicable = true;
                $referral_data= array(
                                        'referral_id'=>$check_referral['id'],
                                        'referral_amount'=>$check_referral['referral_amount'],                                    
                                        'referral_group'=>$check_referral['referral_group'],
                                        'referral_type'=>$check_referral['referral_type']
                                    );
            }
        }        
        return array('is_applicable'=>$is_applicable,'referral_data'=>$referral_data);
    }
function transferToCleaner($booking_id)
{

    $where = ['id' => $booking_id];
    $bookings = Bookings::where($where)->first();
    
    $chargeId = $bookings->charge_id;
    $homeOwnerId = $bookings->user_id;
    $cleanerId = $bookings->service_provider_id;
    $price = $bookings->booking_price;
    $amount_paid = $bookings->amount_paid;
    $amount_paid_in_cent = $bookings->amount_paid*100;

    $payPriceWithTaxes = $this->getFinalPriceForCleaner($price);       
    $amount_pay_cleaner = $this->amountToFloat($payPriceWithTaxes['amt']);
    $amount_pay_cleaner_in_cent = $amount_pay_cleaner*100;
                
    $transfer_by_platform_balance = 0;
    $transfer_by_charge_id = 0;
    if($amount_paid_in_cent >= $amount_pay_cleaner_in_cent )
    {
        $transfer_by_charge_id = $amount_pay_cleaner_in_cent;
    }
    else
    {
        if($amount_paid_in_cent > 0)
        {
            $transfer_by_charge_id = $amount_paid_in_cent;

        }
        $transfer_by_platform_balance = $amount_pay_cleaner_in_cent - $transfer_by_charge_id;
    }
    
    Log::Info("\n payPriceWithTaxes: \n ".json_encode($payPriceWithTaxes));
    Log::Info("\n amount_paid_in_cent: ".  $amount_paid_in_cent);
    Log::Info("\n amount_pay_cleaner_in_cent: ".  $amount_pay_cleaner_in_cent);
    Log::Info("\n transfer_by_charge_id: ".  $transfer_by_charge_id);
    Log::Info("\n transfer_by_platform_balance: ".  $transfer_by_platform_balance);    
    Log::Info("\n stripe_payout_fees: ".  $this->amountToFloat($payPriceWithTaxes['stripe_payout_fees']));
    Log::Info("\n skep_net_revenue: ". $this->amountToFloat($payPriceWithTaxes['skep_net_revenue']));
            
    $stripeUserDetails = StripeUserDetails::where(['user_id' => $cleanerId])->first();
    
    $platform_transfer_id = $transfer_id =  $balance_transaction = '';
    $platform_transfer_id = NULL;
    if(!empty($stripeUserDetails) && !empty($stripeUserDetails->account_id)){

        $cleanerActId = $stripeUserDetails->account_id;
        
        $STRIPE_SECRET = env("STRIPE_SECRET");
        \Stripe\Stripe::setApiKey($STRIPE_SECRET);
        
        if($chargeId && $transfer_by_charge_id > 0)
        {                                 

            try{

                $transfer = \Stripe\Transfer::create([
                    "amount" => $transfer_by_charge_id,
                    "currency" => "cad",
                    "destination" => $cleanerActId,
                    "source_transaction" => $chargeId
                    ]);
            }
            catch(\Exception $e){

                Log::Info("Transfer FAILED: ". $e->getMessage());
                return array('success'=>false,'message'=>"Sorry you can't mark this job as complete. There seems to be an issue with your Stripe account. Please contact support at ".env("MAIL_SUPPORT").".");
            }

            $transfer_id = $transfer['id'];
            $balance_transaction = $transfer['balance_transaction'];            

            $bookings->is_cleaner_paid = 1;
            $bookings->transfer_by_charge_id = $this->amountToFloat($transfer_by_charge_id);            
            $bookings->transfer_id = $transfer_id;
            $bookings->amount_paid_cleaner = $amount_pay_cleaner ;
            $bookings->stripe_payout_fees = $this->amountToFloat($payPriceWithTaxes['stripe_payout_fees']);
            $bookings->skep_net_revenue = $this->amountToFloat($payPriceWithTaxes['skep_net_revenue']);
            $bookings->transaction_id_cleaner = $balance_transaction;
            $bookings->save();
            
        }

        if($transfer_by_platform_balance > 0)
        {
            $platform_balance = $this->stripe_platform_balance();

            if($platform_balance > $transfer_by_platform_balance)
            {
                try 
                {
                    $platform_transfer = \Stripe\Transfer::create([
                        "amount" => $transfer_by_platform_balance,
                        "currency" => "cad",
                        "destination" => $cleanerActId                           
                        ]);
                    Log::info("platform_transfer :".json_encode($platform_transfer));    

                    $platform_transfer_id = $platform_transfer['id'];                                                
                        
                }                                            
                catch(Exception $e) {
                    Log::Info( 'Transfer from Platform Balance FAILED: ' .$e->getMessage());                    
                    
                    return array('success'=>false,'message'=>"Sorry you can't mark this job as complete. There seems to be an issue with your Stripe account. Please contact support at ".env("MAIL_SUPPORT").".");
                    
                }
            }
            else
            {
                Log::Info( "Stripe platform does not have sufficient balance");
                
            }                            

        }

        $transfer_by_charge_amt = $this->amountToFloat($transfer_by_charge_id/100);
        $transfer_by_platform_amt = $this->amountToFloat($transfer_by_platform_balance/100);
        
        $bookings->is_cleaner_paid = 1;
        $bookings->transfer_by_charge_id = $this->amountToFloat($transfer_by_charge_amt);            
        $bookings->transfer_by_platform_balance = $this->amountToFloat($transfer_by_platform_amt);            
        $bookings->transfer_id = $transfer_id;
        $bookings->platform_transfer_id = $platform_transfer_id;
        $bookings->amount_paid_cleaner = $amount_pay_cleaner ;
        $bookings->stripe_payout_fees = $this->amountToFloat($payPriceWithTaxes['stripe_payout_fees']);
        $bookings->skep_net_revenue = $this->amountToFloat($payPriceWithTaxes['skep_net_revenue']);
        $bookings->transaction_id_cleaner = $balance_transaction;
        $bookings->save();

        return array('success'=>true);
    }
    else{
        Log::Info( "Stripe Account is not  there!! ".$bookings->id);
        return array('success'=>false,'message'=>"Sorry you can't mark this job as complete. You didn't created stripe account yet. Please contact support at ".env("MAIL_SUPPORT").".");
                    
    }
}

/* 
    transfer cleaner referral_balance to cleaner stripe account once in a week
*/
public function transferMoneyToCleaners(){

    
}
public function getFinalPriceForCleaner($price){

    $cleanerDeduction = env("CLEANER_CHARGE_DEDUCTION_PERCENT");
    $stripePayoutFees = env("STRIPE_FEES");
    $skepNetRevenue = $this->amountToFloat($price * $cleanerDeduction / 100);
    $finalCalculations = $this->amountToFloat($price - $skepNetRevenue - $stripePayoutFees); 
    $prices = ['amt' => $finalCalculations, 'skep_net_revenue' => $skepNetRevenue, 'stripe_payout_fees' =>  $stripePayoutFees];
    Log:Info("getFinalPriceForCleaner :: ".json_encode($prices));
    return $prices;
    
}
function amountToFloat($amount)
{
    return number_format($amount,2,'.','');
}

function stripe_platform_balance()
{
    $STRIPE_SECRET = env("STRIPE_SECRET");
    \Stripe\Stripe::setApiKey($STRIPE_SECRET);

    $platform_account_balance = \Stripe\Balance::retrieve();
    
    $platform_balances = $platform_account_balance->available;
    $platform_total_available_balance = 0;
    if($platform_balances)
    {
        foreach($platform_balances as $key=>$balance)
        {
            $platform_total_available_balance +=$balance->amount;
        }
    }
    
    return $platform_total_available_balance;
}

}
?>

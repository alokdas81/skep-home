<?php

namespace App\Models\api\v1;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
class UserReferralHistories extends Model{

    protected $table = 'user_referral_histories';
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

    protected $fillable = ['user_id','referral_type', 'referral_amount', 'is_active', 'is_used','reference_user_activity_id','created_at', 'updated_at','referral_group'];
    
}
?>

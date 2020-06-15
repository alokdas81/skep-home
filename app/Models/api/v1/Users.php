<?php



namespace App\Models\api\v1;
use App\Models\api\v1\Bookings;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
   protected $table = 'users';
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
    protected $fillable = ['email', 'password', 'first_name', 'last_name', 'phone_number', 'qb_id', 'address', 'country_code', 'latitude', 'longitude', 'referral_code', 'social_type', 'social_id', 'profile_pic', 'remember_token', 'device_type', 'device_token', 'user_type', 'push_notification', 'status', 'date_of_birth', 'gender','rating', 'created_date', 'updated_date','timezone','authenticate_status','is_email_verified','is_phone_number_verified','unique_code','referral_user_id','referral_balance','phone_verification_code','phone_otp_expire','work_area','address_latitude','address_longitude','country','state','city','postal_code'];

    /*public function bookings(){
        return $this->belongsTo('Bookings', 'foreign_key', 'other_key');
    }*/
}

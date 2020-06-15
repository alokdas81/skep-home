<?php

namespace App\Models\api\v1;

use Illuminate\Database\Eloquent\Model;

class StripeUserDetails extends Model
{
	protected $table = 'stripe_user_details';
/**
* The database primary key value.
*
* @var string
*/
    protected $primaryKey = 'id';
}

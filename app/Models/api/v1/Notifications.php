<?php

namespace App\Models\api\v1;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model{

	protected $table = 'notifications';
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

    protected $fillable = ['sender_id', 'receiver_id', 'booking_id', 'notification_read', 'status', 'created_at', 'updated_at'];

}

?>
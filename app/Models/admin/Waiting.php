<?php

namespace App\Models\admin;
use Illuminate\Database\Eloquent\Model;

class Waiting extends Model{
	protected $table = 'waitingtime';

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



    protected $fillable = ['waiting_time', 'waiting_for', 'created_at', 'updated_at'];

}



?>
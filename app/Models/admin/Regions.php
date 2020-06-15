<?php

namespace App\Models\admin;
use Illuminate\Database\Eloquent\Model;

class Regions extends Model{
	protected $table = 'regions';

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

    protected $fillable = ['region_id', 'region_lat_lng', 'region_name', 'center_positions', 'status', 'created_at', 'updated_at'];

}



?>
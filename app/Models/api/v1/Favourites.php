<?php 

namespace App\Models\api\v1;

use Illuminate\Database\Eloquent\Model;

class Favourites extends Model
{
	protected $table = 'favourites';

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

    protected $fillable = ['user_id', 'service_provider_id', 'status', 'updated_at', 'created_at'];
}



?>
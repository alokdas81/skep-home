<?php



namespace App\Models\api\v1;



use Illuminate\Database\Eloquent\Model;



class Myspace extends Model

{

   protected $table = 'my_space';



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

    protected $fillable = ['user_id', 'name', 'address', 'latitude', 'longitude', 'unit_number', 'city', 'postalcode', 'buzz_number', 'type', 'bedrooms', 'bathrooms', 'dens', 'family_room', 'dining_room', 'powder_room', 'special_instructions', 'set_as_default'];

}

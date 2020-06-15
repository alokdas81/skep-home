<?php



namespace App\Models\admin;



use Illuminate\Database\Eloquent\Model;



class Extraservices extends Model{



	protected $table = 'extra_services';



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



    protected $fillable = ['name', 'time', 'price', 'image', 'unselected_image', 'created_at', 'updated_at'];

    

}



?>
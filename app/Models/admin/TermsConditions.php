<?php

namespace App\Models\admin;
use Illuminate\Database\Eloquent\Model;

class TermsConditions extends Model{
	protected $table = 'terms_and_conditions';

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



    protected $fillable = ['title', 'description', 'created_at', 'updated_at'];

}



?>
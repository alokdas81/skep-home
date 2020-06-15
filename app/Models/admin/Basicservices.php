<?php

namespace App\Models\admin;
use Illuminate\Database\Eloquent\Model;

class Basicservices extends Model{

	protected $table = 'basic_services';
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

    protected $fillable = ['name', 'time', 'price', 'created_at', 'updated_at'];
}



?>
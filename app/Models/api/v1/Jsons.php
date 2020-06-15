<?php

namespace App\Models\api\v1;

use Illuminate\Database\Eloquent\Model;

class Jsons extends Model
{
   protected $table = 'jsons';
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
    protected $fillable = ['action', 'data', 'call_type'];

   
}

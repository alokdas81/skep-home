<?php

namespace App\Models\api\v1;

use Illuminate\Database\Eloquent\Model;

class Ratings extends Model{

	protected $table = 'ratings';

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

    protected $fillable = ['ratings_by', 'ratings_for', 'ratings', 'booking_id', 'reviews', 'created_at', 'updated_at'];
} 

?>
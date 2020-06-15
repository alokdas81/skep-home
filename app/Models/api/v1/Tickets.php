<?php


namespace App\Models\api\v1;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model{

	protected $table = 'tickets';
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

    protected $fillable = ['ticket_number', 'job_id','user_id', 'user_type', 'title', 'description', 'status', 'created_at', 'updated_at'];

} 



?>
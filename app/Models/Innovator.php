<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Innovator extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "inov_id";

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'inov_id',
        'inno_id',
        'inov_last_name',
        'inov_first_name',
        'inov_middle_name',
        'inov_age',
        'inov_sex',
        'edub_id',
        'edub_others',
        'att_id',
        'att_others',
        'inov_address',
        'inov_mobile',
        'inov_email',
    ];

    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }
}

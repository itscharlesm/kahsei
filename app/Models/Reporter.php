<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporter extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "rep_id";

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'rep_id',
        'inno_id',
        'rep_last_name',
        'rep_first_name',
        'rep_middle_name',
        'rep_mobile',
        'rep_email',
    ];

    public function innovation(): BelongsTo
    {
        return $this->belongsTo(Innovation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "act_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "act_date_created";
    
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "act_date_modified";

    protected $fillable = [
        'act_uuid',
        'act_title',
        'act_content',
        'act_image',
        'act_date_created',
        'act_created_by',
        'act_date_modified',
        'act_modified_by',
        'act_active'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "ann_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "ann_date_created";

    protected $fillable = [
        'ann_uuid',
        'ann_title',
        'ann_content',
        'ann_image',
        'ann_created_by',
        'ann_date_created',
        'ann_active'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

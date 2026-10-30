<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "art_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "art_date_created";

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "art_date_created";

    protected $fillable = [
        'art_uuid',
        'art_title',
        'art_content',
        'art_image',
        'art_date_created',
        'art_created_by',
        'art_date_modified',
        'art_modified_by',
        'art_active'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

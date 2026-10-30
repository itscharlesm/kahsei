<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    use HasFactory;

     /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "news_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "news_date_created";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const UPDATED_AT = "news_date_updated";

    protected $fillable = [
        'news_uuid',
        'news_date',
        'news_title',
        'news_content',
        'news_image',
        'news_date_created',
        'news_created_by',
        'news_date_updated',
        'news_updated_by',
        'news_active'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

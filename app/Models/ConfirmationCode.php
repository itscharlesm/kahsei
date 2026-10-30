<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmationCode extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "cc_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "cc_created_at";

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "cc_updated_at";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cc_code',
        'cc_email',
        'cc_expired_at',
        'cc_active'
    ];
}

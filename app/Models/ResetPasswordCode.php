<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetPasswordCode extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "rp_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "rp_created_at";

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "rp_updated_at";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rp_code',
        'rp_email',
        'rp_expired_at',
        'rp_active'
    ];
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "usr_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "usr_date_created";

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "usr_date_modified";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usr_uuid',
        'typ_id',
        'usr_email',
        'usr_password',
        'usr_last_name',
        'usr_first_name',
        'usr_middle_name',
        'usr_mobile',
        'usr_birth_date',
        'usr_date_created',
        'usr_date_modified',
        'usr_image_path',
        'usr_email_activation_code',
        'usr_invalid_login_count',
        'usr_is_verified',
        'usr_date_verified',
        'usr_password_reset_code',
        'usr_password_reset_allowed',
        'usr_theme',
        'usr_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function innovations(): HasMany
    {
        return $this->hasMany(Innovation::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}

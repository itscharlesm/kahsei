<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Innovation extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = "inno_id";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = "inno_date_created";

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = "inno_date_modified";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inno_uuid',
        'usr_id',
        'inno_title',
        'nat_id',
        'typ_id',
        'cat_id',
        'inno_cat_type_others',
        'brg_id',
        'inno_brg',
        'mun_id',
        'inno_mun',
        'prov_id',
        'inno_prov',
        'reg_id',
        'inno_reg',
        'com_id',
        'inno_com_type_specify',
        'lic_id',
        'stat_id',
        'inno_cover_photo',
        'inno_description',
        'inno_needs',
        'inno_users',
        'inno_insights',
        'inno_latitude',
        'inno_longitude',
        'inno_date_solutions_map',
        'inno_date_created',
        'inno_created_by',
        'inno_date_modified',
        'inno_active'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function innovators(): HasMany
    {
        return $this->hasMany(Innovator::class);
    }

    public function reporters(): HasMany
    {
        return $this->hasMany((Reporter::class));
    }
}

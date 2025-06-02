<?php

namespace DemoShop\Data\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuthToken extends Model
{
    protected $table = 'admin_auth_tokens';

    protected $fillable = [
        'admin_id',
        'selector',
        'hashed_validator',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * @return BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
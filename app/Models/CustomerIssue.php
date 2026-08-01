<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerIssue extends Model
{
    protected $fillable = [
        'user_id',
        'customer_code',
        'name',
        'email',
        'category',
        'subject',
        'message',
        'status',
        'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

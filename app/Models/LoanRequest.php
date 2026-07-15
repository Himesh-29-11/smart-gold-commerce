<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRequest extends Model
{
    protected $fillable = ['user_id', 'partner_id', 'reference', 'monthly_income', 'employment_type', 'requested_amount', 'tenure_months', 'existing_monthly_emi', 'estimated_emi', 'eligibility_score', 'status', 'provider_reference', 'transmitted_at', 'consent_given', 'documents', 'admin_notes'];

    protected function casts(): array
    {
        return ['documents' => 'array', 'transmitted_at' => 'datetime', 'consent_given' => 'boolean', 'monthly_income' => 'decimal:2', 'requested_amount' => 'decimal:2', 'existing_monthly_emi' => 'decimal:2', 'estimated_emi' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}

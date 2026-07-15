<?php

namespace App\Services\Loans;

final readonly class LoanSubmissionResult
{
    public function __construct(
        public string $providerReference,
        public string $status = 'forwarded',
    ) {}
}

<?php

namespace App\Contracts;

use App\Models\LoanRequest;
use App\Services\Loans\LoanSubmissionResult;

interface LoanProviderConnector
{
    public function configuredFor(LoanRequest $loan): bool;

    public function submit(LoanRequest $loan): LoanSubmissionResult;
}

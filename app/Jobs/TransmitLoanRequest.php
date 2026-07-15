<?php

namespace App\Jobs;

use App\Models\LoanRequest;
use App\Services\Loans\ConfiguredHttpLoanConnector;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TransmitLoanRequest implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 300;

    public function __construct(public readonly LoanRequest $loan) {}

    public function uniqueId(): string
    {
        return (string) $this->loan->id;
    }

    public function handle(ConfiguredHttpLoanConnector $connector): void
    {
        $loan = $this->loan->fresh(['user', 'partner']);
        if (! $loan || $loan->transmitted_at || ! $connector->configuredFor($loan)) {
            return;
        }

        $result = $connector->submit($loan);
        $loan->update([
            'provider_reference' => $result->providerReference,
            'transmitted_at' => now(),
            'status' => $result->status,
        ]);
    }
}

<?php

namespace App\Services\Loans;

use App\Contracts\LoanProviderConnector;
use App\Models\LoanRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ConfiguredHttpLoanConnector implements LoanProviderConnector
{
    public function configuredFor(LoanRequest $loan): bool
    {
        $connection = config('loan.primary');

        return $loan->partner !== null
            && $loan->partner->slug === ($connection['slug'] ?? null)
            && ! empty($connection['endpoint'])
            && ! empty($connection['token']);
    }

    public function submit(LoanRequest $loan): LoanSubmissionResult
    {
        if (! $this->configuredFor($loan)) {
            throw new RuntimeException('No credentialed connector is configured for this loan provider.');
        }
        if (! $loan->consent_given) {
            throw new RuntimeException('A loan request cannot be transmitted without recorded consent.');
        }

        $endpoint = (string) config('loan.primary.endpoint');
        if (app()->isProduction() && ! str_starts_with($endpoint, 'https://')) {
            throw new RuntimeException('Loan provider connections must use HTTPS in production.');
        }

        $loan->loadMissing(['user', 'partner']);
        $response = Http::timeout((int) config('loan.primary.timeout'))
            ->retry(2, 500)
            ->withToken((string) config('loan.primary.token'))
            ->withHeaders(['Idempotency-Key' => $loan->reference])
            ->acceptJson()
            ->post($endpoint, [
                'request_reference' => $loan->reference,
                'applicant' => [
                    'name' => $loan->user->name,
                    'email' => $loan->user->email,
                    'phone' => $loan->user->phone,
                ],
                'application' => [
                    'monthly_income' => (float) $loan->monthly_income,
                    'employment_type' => $loan->employment_type,
                    'requested_amount' => (float) $loan->requested_amount,
                    'tenure_months' => $loan->tenure_months,
                    'existing_monthly_emi' => (float) $loan->existing_monthly_emi,
                    'document_types_available' => $loan->documents,
                ],
                'consent' => [
                    'given' => true,
                    'recorded_at' => $loan->created_at->toIso8601String(),
                    'purpose' => 'financing_provider_introduction',
                ],
            ]);
        $response->throw();
        $data = $response->json();
        $providerReference = is_array($data) ? ($data['reference'] ?? $data['id'] ?? null) : null;

        if (! is_string($providerReference) || $providerReference === '') {
            throw new RuntimeException('The loan provider returned no application reference.');
        }

        return new LoanSubmissionResult($providerReference);
    }
}

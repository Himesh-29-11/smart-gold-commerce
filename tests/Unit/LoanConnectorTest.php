<?php

namespace Tests\Unit;

use App\Jobs\TransmitLoanRequest;
use App\Models\LoanRequest;
use App\Models\Partner;
use App\Models\User;
use App\Services\Loans\ConfiguredHttpLoanConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoanConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_consented_request_can_be_handed_to_configured_https_connector(): void
    {
        $user = User::factory()->create(['phone' => '9876500010', 'otp_verified_at' => now()]);
        $partner = Partner::create([
            'type' => 'loan', 'name' => 'Contracted Provider', 'slug' => 'contracted-provider',
            'is_verified' => true, 'is_active' => true,
        ]);
        $loan = LoanRequest::create([
            'user_id' => $user->id, 'partner_id' => $partner->id, 'reference' => 'LOAN-TEST-1',
            'monthly_income' => 100000, 'employment_type' => 'salaried', 'requested_amount' => 200000,
            'tenure_months' => 24, 'existing_monthly_emi' => 0, 'estimated_emi' => 9500,
            'eligibility_score' => 90, 'status' => 'submitted', 'consent_given' => true,
            'documents' => ['pan', 'income'],
        ]);
        config([
            'loan.primary.slug' => 'contracted-provider',
            'loan.primary.endpoint' => 'https://provider.example/applications',
            'loan.primary.token' => 'provider-secret',
        ]);
        Http::fake([
            'https://provider.example/applications' => Http::response(['reference' => 'EXT-123']),
        ]);

        (new TransmitLoanRequest($loan))->handle(app(ConfiguredHttpLoanConnector::class));

        $loan->refresh();
        $this->assertSame('EXT-123', $loan->provider_reference);
        $this->assertSame('forwarded', $loan->status);
        $this->assertNotNull($loan->transmitted_at);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer provider-secret')
                && $request->hasHeader('Idempotency-Key', 'LOAN-TEST-1')
                && $request['application']['document_types_available'] === ['pan', 'income'];
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\LoanRequest;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\LoanStatusNotification;
use App\Notifications\LoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_login_creates_an_in_app_security_notification(): void
    {
        $user = User::factory()->create([
            'otp_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => LoginNotification::class,
        ]);
    }

    public function test_admin_loan_status_change_creates_customer_notification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'otp_verified_at' => now(),
            'is_active' => true,
        ]);
        $customer = User::factory()->create([
            'otp_verified_at' => now(),
            'is_active' => true,
        ]);
        $partner = Partner::create([
            'type' => 'loan',
            'name' => 'Test Finance',
            'slug' => 'test-finance-notification',
            'is_verified' => true,
            'is_active' => true,
        ]);
        $loan = LoanRequest::create([
            'user_id' => $customer->id,
            'partner_id' => $partner->id,
            'reference' => 'LOAN-NOTIFY-1',
            'monthly_income' => 100000,
            'employment_type' => 'salaried',
            'requested_amount' => 200000,
            'tenure_months' => 24,
            'existing_monthly_emi' => 0,
            'estimated_emi' => 9500,
            'eligibility_score' => 90,
            'status' => 'submitted',
            'consent_given' => true,
            'documents' => ['pan', 'income'],
        ]);

        $this->actingAs($admin)->patch(route('admin.loans.update', $loan), [
            'status' => 'approved',
            'admin_notes' => 'Provider reported approval.',
        ])->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => LoanStatusNotification::class,
        ]);
    }
}

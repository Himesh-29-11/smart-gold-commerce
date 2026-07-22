<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationMail;
use App\Models\LoanRequest;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\LoanStatusNotification;
use App\Notifications\LoginNotification;
use App\Notifications\OtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_queues_otp_email_without_blocking_request(): void
    {
        Queue::fake();

        $this->post(route('register'), [
            'name' => 'OTP Test User',
            'email' => 'otp-test-user@gmail.com',
            'phone' => '9876512345',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'terms' => '1',
        ])->assertRedirect(route('otp.show'));

        $user = User::where('email', 'otp-test-user@gmail.com')->firstOrFail();
        $this->assertDatabaseHas('otp_codes', ['user_id' => $user->id, 'purpose' => 'registration']);
        Queue::assertPushed(SendNotificationMail::class, fn ($job) => $job->notification instanceof OtpNotification);
    }

    public function test_verified_login_creates_an_in_app_security_notification(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendNotificationMail::class);
    }

    public function test_admin_loan_status_change_creates_customer_notification(): void
    {
        Queue::fake();
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
        Queue::assertPushed(SendNotificationMail::class);
    }
}

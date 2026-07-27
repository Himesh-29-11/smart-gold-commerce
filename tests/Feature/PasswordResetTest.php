<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationMail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_queued_password_reset_link(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Queue::assertPushed(SendNotificationMail::class, fn ($job) => $job->user->is($user) && $job->notification instanceof ResetPassword);
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewStrongPassword123',
            'password_confirmation' => 'NewStrongPassword123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStrongPassword123', $user->fresh()->password));
    }
}

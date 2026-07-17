<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'access' => 'nullable|in:active,disabled',
            'verification' => 'nullable|in:verified,pending',
        ]);
        $query = User::where('role', 'customer')->withCount(['orders', 'loanRequests']);

        if ($search = $data['q'] ?? null) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%'));
        }
        if (($data['access'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($data['access'] ?? null) === 'disabled') {
            $query->where('is_active', false);
        }
        if (($data['verification'] ?? null) === 'verified') {
            $query->whereNotNull('otp_verified_at');
        } elseif (($data['verification'] ?? null) === 'pending') {
            $query->whereNull('otp_verified_at');
        }

        return view('admin.customers', [
            'customers' => $query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Customer status updated.');
    }
}

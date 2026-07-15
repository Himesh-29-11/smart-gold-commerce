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
        $query = User::where('role', 'customer')->withCount(['orders', 'loanRequests']);
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%'));
        }

        return view('admin.customers', ['customers' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Customer status updated.');
    }
}

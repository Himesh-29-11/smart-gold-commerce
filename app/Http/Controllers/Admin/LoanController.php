<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use App\Notifications\LoanStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:submitted,under_review,documents_required,forwarded,approved,rejected,closed',
        ]);
        $query = LoanRequest::with(['user', 'partner']);

        if ($search = $data['q'] ?? null) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'));
            });
        }
        if ($status = $data['status'] ?? null) {
            $query->where('status', $status);
        }

        return view('admin.loans', [
            'loans' => $query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function update(Request $request, LoanRequest $loan): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:submitted,under_review,documents_required,forwarded,approved,rejected,closed', 'admin_notes' => 'nullable|string|max:2000']);
        $loan->update($data);
        $loan->user->notify(new LoanStatusNotification($loan));

        return back()->with('success', 'Loan request updated.');
    }
}

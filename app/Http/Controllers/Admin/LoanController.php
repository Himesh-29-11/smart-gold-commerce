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
        $query = LoanRequest::with(['user', 'partner']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

return view('admin.loans', ['loans' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function update(Request $request, LoanRequest $loan): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:submitted,under_review,documents_required,forwarded,approved,rejected,closed', 'admin_notes' => 'nullable|string|max:2000']);
        $loan->update($data);
        $loan->user->notify(new LoanStatusNotification($loan));

        return back()->with('success', 'Loan request updated.');
    }
}

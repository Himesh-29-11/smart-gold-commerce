<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(): View
    {
        $issues = CustomerIssue::with('user')->latest()->get();

        return view('admin.issues', [
            'issues' => $issues,
            'openCount' => $issues->where('status', 'open')->count(),
            'inProgressCount' => $issues->where('status', 'in_progress')->count(),
            'resolvedCount' => $issues->where('status', 'resolved')->count(),
        ]);
    }

    public function update(Request $request, CustomerIssue $issue): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $issue->update($data);

        return back()->with('success', 'Customer issue status updated.');
    }
}

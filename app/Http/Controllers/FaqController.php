<?php

namespace App\Http\Controllers;

use App\Models\CustomerIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('faq');
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'category' => 'required|string|in:order,loan,payment,delivery,general',
            'subject' => 'required|string|max:180',
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        CustomerIssue::create([
            'user_id' => $user?->id,
            'customer_code' => $user?->customer_code,
            'name' => $data['name'],
            'email' => $data['email'],
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
        ]);

        return back()->with('success', 'Your support request has been submitted. Our operations team will review your issue and respond promptly.');
    }
}

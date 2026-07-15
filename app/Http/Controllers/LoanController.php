<?php

namespace App\Http\Controllers;

use App\Jobs\TransmitLoanRequest;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $partners = Partner::where('type', 'loan')
            ->where('is_verified', true)
            ->where('is_active', true)
            ->orderBy('interest_rate_min')
            ->get();
        $requests = $request->user()
            ?->loanRequests()
            ->with('partner')
            ->latest()
            ->get() ?? collect();

        return view('loans.index', compact('partners', 'requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'monthly_income' => 'required|numeric|min:10000|max:100000000',
            'employment_type' => 'required|in:salaried,self-employed,business',
            'requested_amount' => 'required|numeric|min:10000|max:10000000',
            'tenure_months' => 'required|integer|min:3|max:84',
            'existing_monthly_emi' => 'nullable|numeric|min:0',
            'documents' => 'required|array|min:2',
            'documents.*' => 'in:pan,identity,address,income,bank',
            'consent' => 'accepted',
        ]);

        $partner = Partner::where('type', 'loan')
            ->where('is_verified', true)
            ->where('is_active', true)
            ->findOrFail($data['partner_id']);

        $monthlyRate = ((float) $partner->interest_rate_min / 100) / 12;
        $tenure = (int) $data['tenure_months'];
        $principal = (float) $data['requested_amount'];
        $estimatedEmi = $monthlyRate > 0
            ? $principal * $monthlyRate * pow(1 + $monthlyRate, $tenure)
                / (pow(1 + $monthlyRate, $tenure) - 1)
            : $principal / $tenure;
        $debtRatio = ($estimatedEmi + (float) ($data['existing_monthly_emi'] ?? 0))
            / (float) $data['monthly_income'];
        $score = max(0, min(100, (int) round(100 - ($debtRatio * 100))));

        $loan = $request->user()->loanRequests()->create([
            'partner_id' => $partner->id,
            'reference' => 'LOAN-'.now()->format('Ymd').'-'.strtoupper(Str::random(7)),
            'monthly_income' => $data['monthly_income'],
            'employment_type' => $data['employment_type'],
            'requested_amount' => $principal,
            'tenure_months' => $tenure,
            'existing_monthly_emi' => $data['existing_monthly_emi'] ?? 0,
            'estimated_emi' => round($estimatedEmi, 2),
            'eligibility_score' => $score,
            'status' => 'submitted',
            'consent_given' => true,
            'documents' => array_values(array_unique($data['documents'])),
        ]);

        TransmitLoanRequest::dispatch($loan)->afterCommit();

        return redirect()->route('loans.index')->with(
            'success',
            'Request '.$loan->reference.' was securely queued for review. No loan has been issued by us.',
        );
    }
}

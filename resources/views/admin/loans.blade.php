@extends('layouts.admin')
@section('title', 'Loan Requests')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Assistance operations</span>
            <h1>Loan requests</h1>
            <p>Track consented introductions. Never record full KYC documents or lender credentials here.</p>
        </div>
    </div>
    <div class="source-disclaimer"><b>Role boundary</b>
        <p>N & H Trust is a connector, not a lender. “Approved” reflects provider-reported status; staff must not promise a
            rate, approval or disbursal.</p>
    </div>
    <article class="admin-panel">
        <form class="admin-search" method="GET"><select name="status">
                <option value="">All statuses</option>
                @foreach (['submitted', 'under_review', 'documents_required', 'forwarded', 'approved', 'rejected', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}
                    </option>
                @endforeach
            </select>
            <button class="button button-outline">Filter</button>
        </form>
        <div class="loan-admin-list">
            @foreach ($loans as $loan)
                <article>
                    <div class="loan-admin-head">
                        <div><b>{{ $loan->reference }}</b><span>{{ $loan->created_at->format('d M Y, h:i A') }} ·
                                {{ $loan->user->name }} · {{ $loan->user->email }}</span></div><span
                            class="status status-{{ $loan->status }}">{{ str_replace('_', ' ', $loan->status) }}</span>
                    </div>
                    <div class="loan-metrics">
                        <span><small>Provider</small><b>{{ $loan->partner?->name ?? 'Unassigned' }}</b></span><span><small>Requested</small><b>₹{{ number_format($loan->requested_amount) }}</b></span><span><small>Income
                                / month</small><b>₹{{ number_format($loan->monthly_income) }}</b></span><span><small>Est.
                                EMI</small><b>₹{{ number_format($loan->estimated_emi) }}</b></span><span><small>Affordability
                                signal</small><b>{{ $loan->eligibility_score }}/100</b></span><span><small>Documents
                                stated</small><b>{{ implode(', ', $loan->documents ?? []) }}</b></span></div>
                    <form class="loan-update" method="POST" action="{{ route('admin.loans.update', $loan) }}">@csrf
                        @method('PATCH')<label>Status<select name="status">
                                @foreach (['submitted', 'under_review', 'documents_required', 'forwarded', 'approved', 'rejected', 'closed'] as $status)
                                    <option value="{{ $status }}" @selected($loan->status === $status)>
                                        {{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                        </label><label>Internal notes<input name="admin_notes" value="{{ $loan->admin_notes }}"
                                placeholder="No sensitive KYC data"></label><button class="button button-outline"
                            type="submit">Update</button></form>
                </article>
            @endforeach
        </div>{{ $loans->links() }}
    </article>
@endsection

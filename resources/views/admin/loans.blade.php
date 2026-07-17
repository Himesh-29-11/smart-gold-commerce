@extends('layouts.admin')
@section('title', 'Loan Requests')
@section('admin-content')
    <div class="admin-heading"><div><span class="kicker dark">Assistance operations</span><h1>Loan requests</h1><p>Track consented introductions without storing KYC files or lender credentials.</p></div></div>
    <div class="admin-alert warning"><strong>Connector boundary:</strong> N & H Trust is not the lender. Approval and final terms remain controlled by the selected provider.</div>

    <section class="admin-panel">
        <form class="admin-search" method="GET" action="{{ route('admin.loans.index') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Reference, customer or email">
            <select name="status"><option value="">All statuses</option>@foreach (['submitted', 'under_review', 'documents_required', 'forwarded', 'approved', 'rejected', 'closed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select>
            <button class="button button-outline" type="submit">Filter</button><a class="admin-filter-clear" href="{{ route('admin.loans.index') }}">Clear</a>
        </form>

        <div class="loan-admin-list">
            @forelse ($loans as $loan)
                <article>
                    <div class="loan-admin-head"><div><b>{{ $loan->reference }}</b><span>{{ $loan->created_at->format('d M Y, h:i A') }} · {{ $loan->user->name }} · {{ $loan->user->email }}</span></div><span class="status status-{{ $loan->status }}">{{ str_replace('_', ' ', $loan->status) }}</span></div>
                    <div class="loan-metrics">
                        <span><small>Provider</small><b>{{ $loan->partner?->name ?? 'Unassigned' }}</b></span><span><small>Requested</small><b>₹{{ number_format($loan->requested_amount) }}</b></span><span><small>Income / month</small><b>₹{{ number_format($loan->monthly_income) }}</b></span><span><small>Estimated EMI</small><b>₹{{ number_format($loan->estimated_emi) }}</b></span><span><small>Affordability</small><b>{{ $loan->eligibility_score }}/100</b></span><span><small>Documents stated</small><b>{{ implode(', ', $loan->documents ?? []) }}</b></span>
                    </div>
                    <form class="loan-update" method="POST" action="{{ route('admin.loans.update', $loan) }}">@csrf @method('PATCH')<label>Status<select name="status">@foreach (['submitted', 'under_review', 'documents_required', 'forwarded', 'approved', 'rejected', 'closed'] as $status)<option value="{{ $status }}" @selected($loan->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></label><label>Internal notes<input name="admin_notes" value="{{ $loan->admin_notes }}" placeholder="Never enter sensitive KYC data"></label><button class="button button-outline" type="submit">Update</button></form>
                </article>
            @empty
                <div class="admin-empty"><strong>No loan requests found</strong><span>There are no requests matching these filters.</span></div>
            @endforelse
        </div>
        @include('admin.partials.pagination', ['paginator' => $loans])
    </section>
@endsection

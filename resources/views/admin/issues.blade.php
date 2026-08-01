@extends('layouts.admin')
@section('title', 'Customer Issues & Support Console')
@section('admin-content')
<div class="admin-heading">
    <div>
        <span class="kicker dark">Support &amp; ticket care</span>
        <h1>Customer issues</h1>
        <p>Review customer-reported problems, identify accounts by 4-digit Customer ID (#1001), and update support resolution status.</p>
    </div>
    <a class="button button-outline" href="{{ route('faq') }}" target="_blank">View storefront FAQ ↗</a>
</div>

<div class="stat-grid" style="margin-bottom: 25px;">
    <article><span>Total reported</span><strong>{{ $issues->count() }}</strong><small>All-time submitted tickets</small></article>
    <article><span>Open tickets</span><strong style="color: #b88a38;">{{ $openCount }}</strong><small>Require operations review</small></article>
    <article><span>In progress</span><strong style="color: #356576;">{{ $inProgressCount }}</strong><small>Currently being investigated</small></article>
    <article><span>Resolved</span><strong style="color: #28664f;">{{ $resolvedCount }}</strong><small>Successfully closed</small></article>
</div>

<section class="admin-panel">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Customer tickets</span>
            <h2>Reported issues log</h2>
        </div>
    </div>

    <div class="admin-table">
        <div class="table-row table-head" style="grid-template-columns: 120px 1.5fr 1fr 1.8fr 180px;">
            <span>Customer ID</span>
            <span>Name &amp; Email</span>
            <span>Category</span>
            <span>Subject &amp; Message</span>
            <span>Status / Action</span>
        </div>
        @forelse($issues as $issue)
            <div class="table-row" style="grid-template-columns: 120px 1.5fr 1fr 1.8fr 180px;">
                <span>
                    <b style="font-size: 14px; color: #4a0404;">#{{ $issue->customer_code ?? ($issue->user?->customer_code ?? 'N/A') }}</b>
                    <small>ID: {{ $issue->user_id ? 'Registered' : 'Guest' }}</small>
                </span>
                <span>
                    <b>{{ $issue->name }}</b>
                    <small>{{ $issue->email }}</small>
                </span>
                <span>
                    <span class="status status-processing" style="width: max-content;">{{ ucfirst($issue->category) }}</span>
                    <small>{{ $issue->created_at->format('d M Y, h:i A') }}</small>
                </span>
                <span>
                    <b>{{ $issue->subject }}</b>
                    <small style="margin-top: 4px; display: block;">“{{ $issue->message }}”</small>
                </span>
                <span>
                    <form method="POST" action="{{ route('admin.issues.update', $issue) }}" class="inline-update">
                        @csrf
                        @method('PATCH')
                        <select name="status" onchange="this.form.submit()">
                            <option value="open" @selected($issue->status === 'open')>Open</option>
                            <option value="in_progress" @selected($issue->status === 'in_progress')>In Progress</option>
                            <option value="resolved" @selected($issue->status === 'resolved')>Resolved</option>
                        </select>
                    </form>
                </span>
            </div>
        @empty
            <div class="admin-empty">
                <strong>No customer issues reported yet</strong>
                <span>When customers report problems via the FAQ/Support page, they will appear here.</span>
            </div>
        @endforelse
    </div>
</section>
@endsection

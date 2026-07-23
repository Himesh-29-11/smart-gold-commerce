@extends('layouts.admin')
@section('title', 'Gold-Rate Operations')
@section('admin-content')
<div class="admin-heading"><div><span class="kicker dark">Market-data operations</span><h1>Gold-rate feed</h1><p>Monitor freshness, coverage and provider synchronization without manually typing live prices.</p></div><a class="button button-outline" href="{{ route('gold-prices') }}" target="_blank" rel="noopener">View customer dashboard ↗</a></div>

<div class="admin-alert {{ $mode === 'live' ? 'success' : 'warning' }}"><strong>{{ ucfirst($mode) }} mode.</strong> @if($mode==='live')Customer prices use observations from the configured authorized provider.@elseif($mode==='demo')Values are explicitly labelled demonstration data and are not market quotes.@elseNo usable observation is available; customer pricing should remain unavailable.@endif</div>

<div class="stat-grid gold-ops-stats">
    <article><span>24K price / gram</span><strong>{{ $rates['24K'] ? '₹'.number_format($rates['24K']->price_per_gram,2) : '—' }}</strong><small>{{ $rates['24K'] ? ($rates['24K']->fetched_at->diffForHumans().' · '.$rates['24K']->source) : 'No observation' }}</small></article>
    <article><span>22K price / gram</span><strong>{{ $rates['22K'] ? '₹'.number_format($rates['22K']->price_per_gram,2) : '—' }}</strong><small>{{ $rates['22K'] ? ($rates['22K']->fetched_at->diffForHumans().' · '.$rates['22K']->source) : 'No observation' }}</small></article>
    <article><span>History coverage</span><strong>{{ $coverageTo?->format('d M Y') ?? '—' }}</strong><small>{{ $coverageFrom?->format('d M Y') ?? 'No start' }} to latest · {{ $points['24K'] ?? 0 }} daily 24K points</small></article>
    <article><span>Background jobs</span><strong>{{ $queuedJobs }}</strong><small>{{ $failedJobs }} failed jobs require review</small></article>
</div>

<div class="gold-ops-grid">
    <section class="admin-panel"><div class="section-heading"><div><span class="eyebrow">Configuration status</span><h2>Active feed</h2></div></div><dl class="gold-config-list"><div><dt>Configured provider</dt><dd>{{ $provider }}</dd></div><div><dt>Active source</dt><dd>{{ $source ?? 'Unavailable' }}</dd></div><div><dt>Latest endpoint</dt><dd class="status {{ $latestEndpointConfigured ? 'status-confirmed':'status-pending' }}">{{ $latestEndpointConfigured ? 'Configured':'Not configured' }}</dd></div><div><dt>Historical endpoint</dt><dd class="status {{ $historyEndpointConfigured ? 'status-confirmed':'status-pending' }}">{{ $historyEndpointConfigured ? 'Configured':'Not configured' }}</dd></div><div><dt>Manual live entry</dt><dd class="status status-cancelled">Disabled</dd></div></dl><p class="admin-panel-note">API keys and webhook/provider secrets remain in the server environment and are never displayed or editable here.</p></section>

    <section class="admin-panel"><div class="section-heading"><div><span class="eyebrow">Safe actions</span><h2>Feed controls</h2></div></div>
        @if($provider==='database')
            <form class="gold-operation-form" method="POST" action="{{ route('admin.gold-prices.refresh-demo') }}">@csrf<label>Demo history window<select name="days"><option value="30">30 days</option><option value="90">90 days</option><option value="365" selected>365 days</option><option value="730">730 days</option></select></label><button class="button" type="submit" onclick="return confirm('Refresh clearly labelled demo history through today?')">Refresh demo history</button></form><p class="admin-panel-note">This action is available only in database/demo mode and never creates a live-rate claim.</p>
        @else
            <form class="gold-operation-form" method="POST" action="{{ route('admin.gold-prices.sync') }}">@csrf<button class="button" type="submit">Synchronize latest rates now</button></form>
            <form class="gold-operation-form" method="POST" action="{{ route('admin.gold-prices.backfill') }}">@csrf<label>Historical days<input type="number" name="days" min="1" max="365" value="30" required></label><button class="button button-outline" type="submit">Queue historical backfill</button></form><p class="admin-panel-note">Backfill runs on the default queue. Keep the queue worker active and review failed jobs if the provider rejects a request.</p>
        @endif
    </section>
</div>

<section class="admin-panel"><div class="section-heading"><div><span class="eyebrow">Latest stored records</span><h2>Recent observations</h2></div></div><div class="admin-table gold-observation-table"><div class="table-row table-head"><span>Observed at</span><span>Carat</span><span>Price / gram</span><span>Change</span><span>Source</span></div>@forelse($recentObservations as $observation)<div class="table-row"><span><b>{{ $observation->fetched_at->format('d M Y') }}</b><small>{{ $observation->fetched_at->format('h:i A') }} IST</small></span><span><b>{{ $observation->carat }}</b></span><span><b>₹{{ number_format($observation->price_per_gram,2) }}</b></span><span class="{{ $observation->market_change >= 0 ? 'success-link':'danger-text' }}">{{ $observation->market_change >= 0 ? '+':'' }}₹{{ number_format($observation->market_change,2) }}</span><span><b>{{ $observation->source }}</b></span></div>@empty<div class="admin-empty"><strong>No observations stored</strong><span>Refresh demo history or configure and synchronize an authorized provider.</span></div>@endforelse</div></section>
@endsection

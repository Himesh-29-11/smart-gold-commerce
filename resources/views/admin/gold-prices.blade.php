@extends('layouts.admin')
@section('title', 'Gold-Rate Operations')
@section('admin-content')
<div class="admin-heading">
    <div>
        <span class="kicker dark">Market-data operations</span>
        <h1>Gold-rate feed</h1>
        <p>Monitor freshness, coverage and provider synchronization without manually typing live prices.</p>
    </div>
    <a class="button button-outline" href="{{ route('gold-prices') }}" target="_blank" rel="noopener">View customer dashboard ↗</a>
</div>

<div class="admin-alert {{ $mode === 'live' ? 'success' : 'warning' }}">
    <strong>{{ ucfirst($mode) }} mode active ({{ $source ?? 'Unconfigured' }}).</strong>
    @if($mode === 'live')
        Customer prices and catalog items are dynamically calculated from the latest stored market observation.
    @elseif($mode === 'demo')
        Values are explicitly labelled demonstration data and are not market quotes.
    @else
        No usable observation is available; customer pricing should remain unavailable.
    @endif
</div>

<div class="stat-grid gold-ops-stats">
    <article>
        <span>24K price / gram</span>
        <strong>{{ $rates['24K'] ? '₹'.number_format($rates['24K']->price_per_gram, 2) : '—' }}</strong>
        <small>{{ $rates['24K'] ? ($rates['24K']->fetched_at->diffForHumans().' · '.$rates['24K']->source) : 'No observation' }}</small>
    </article>
    <article>
        <span>22K price / gram</span>
        <strong>{{ $rates['22K'] ? '₹'.number_format($rates['22K']->price_per_gram, 2) : '—' }}</strong>
        <small>{{ $rates['22K'] ? ($rates['22K']->fetched_at->diffForHumans().' · '.$rates['22K']->source) : 'No observation' }}</small>
    </article>
    <article>
        <span>History coverage</span>
        <strong>{{ $coverageTo?->format('d M Y') ?? '—' }}</strong>
        <small>{{ $coverageFrom?->format('d M Y') ?? 'No start' }} to latest · {{ $points['24K'] ?? 0 }} daily 24K points</small>
    </article>
    <article>
        <span>Background jobs</span>
        <strong>{{ $queuedJobs }}</strong>
        <small>{{ $failedJobs }} failed jobs require review</small>
    </article>
</div>

<div class="gold-ops-grid" style="grid-template-columns: 1fr 1.1fr; gap: 24px;">
    <!-- Active Feed Configuration -->
    <section class="admin-panel" style="border-radius: 12px;">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Configuration status</span>
                <h2>Active feed</h2>
            </div>
        </div>
        <dl class="gold-config-list">
            <div>
                <dt>Configured provider (.env)</dt>
                <dd>{{ $provider }}</dd>
            </div>
            <div>
                <dt>Active data source</dt>
                <dd style="color: #4a0404; font-weight: 800;">{{ $source ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Ahmedabad Sarafa premium</dt>
                <dd>+{{ $ahmedabadPremium }}% (MCX Linked)</dd>
            </div>
            <div>
                <dt>API key status (.env)</dt>
                <dd class="status {{ $apiKeyConfigured ? 'status-confirmed' : 'status-pending' }}">
                    {{ $apiKeyConfigured ? 'Configured' : 'Open fallback' }}
                </dd>
            </div>
            <div>
                <dt>Latest endpoint</dt>
                <dd class="status {{ $latestEndpointConfigured ? 'status-confirmed' : 'status-pending' }}">
                    {{ $latestEndpointConfigured ? 'Configured' : 'Not configured' }}
                </dd>
            </div>
            <div>
                <dt>Historical endpoint</dt>
                <dd class="status {{ $historyEndpointConfigured ? 'status-confirmed' : 'status-pending' }}">
                    {{ $historyEndpointConfigured ? 'Configured' : 'Not configured' }}
                </dd>
            </div>
            <div>
                <dt>Manual live entry</dt>
                <dd class="status status-cancelled">Disabled</dd>
            </div>
        </dl>
        <p class="admin-panel-note" style="margin-top: 15px;">API keys and webhook/provider secrets remain in the server environment (.env) and are never displayed or editable here for security.</p>
    </section>

    <!-- Unified, Polished Feed Controls -->
    <section class="admin-panel" style="border-radius: 12px;">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Safe actions</span>
                <h2>Feed controls</h2>
            </div>
        </div>

        <!-- 1. Ahmedabad Bullion Feed (Recommended for Ahmedabad/Gujarat) -->
        <div style="background: #fbf9f5; border: 1px solid #dcc08b; padding: 18px; border-radius: 10px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong style="color: #4a0404; font-size: 14px;">1. Ahmedabad Bullion Market Feed</strong>
                <span class="badge badge-gold">Gujarat MCX Linked</span>
            </div>
            <p style="font-size: 12px; color: #69746f; margin-bottom: 12px; line-height: 1.5;">
                Synchronizes the Ahmedabad Sarafa Bazaar 22K and 24K bullion rate (+{{ $ahmedabadPremium }}% differential over spot INR). Automatically uses your <code>GOLD_PRICE_API_KEY</code> from .env if present.
            </p>
            <form method="POST" action="{{ route('admin.gold-prices.fetch-ahmedabad') }}" style="margin: 0;">
                @csrf
                <button class="button button-gold full" type="submit" style="min-height: 44px;">
                    Sync Ahmedabad Bullion Rate (MCX + Sarafa Bazaar)
                </button>
            </form>
        </div>

        <!-- 2. Standard Global INR Spot Rate -->
        <div style="background: #fbf9f5; border: 1px solid #deded8; padding: 18px; border-radius: 10px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong style="color: #1d2220; font-size: 14px;">2. Global INR Spot Feed</strong>
                <span class="badge">Open Spot</span>
            </div>
            <p style="font-size: 12px; color: #69746f; margin-bottom: 12px; line-height: 1.5;">
                Fetches international London spot INR/gram valuations without regional city markups from open metals endpoints.
            </p>
            <form method="POST" action="{{ route('admin.gold-prices.fetch-public-real') }}" style="margin: 0;">
                @csrf
                <button class="button button-outline full" type="submit" style="min-height: 42px;">
                    Sync Global INR Spot Rate (CoinGecko Base)
                </button>
            </form>
        </div>

        <!-- 3. Demonstration History Suite -->
        <div style="background: #fbf9f5; border: 1px solid #deded8; padding: 16px; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong style="color: #69746f; font-size: 13px;">3. Demonstration History Suite</strong>
                <span class="badge">Offline Testing</span>
            </div>
            <form method="POST" action="{{ route('admin.gold-prices.refresh-demo') }}" style="display: flex; gap: 10px; align-items: center;">
                @csrf
                <select name="days" style="min-height: 38px; padding: 6px; font-size: 12px; border-radius: 6px;">
                    <option value="30">30 days</option>
                    <option value="90">90 days</option>
                    <option value="365" selected>365 days</option>
                </select>
                <button class="button button-outline" type="submit" style="min-height: 38px; font-size: 12px; padding: 0 16px;" onclick="return confirm('Refresh clearly labelled demo history through today?')">
                    Refresh demo history
                </button>
            </form>
        </div>
    </section>
</div>

<section class="admin-panel" style="border-radius: 12px;">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Latest stored records</span>
            <h2>Recent observations</h2>
        </div>
    </div>
    <div class="admin-table gold-observation-table">
        <div class="table-row table-head">
            <span>Observed at</span>
            <span>Carat</span>
            <span>Price / gram</span>
            <span>Change</span>
            <span>Source</span>
        </div>
        @forelse($recentObservations as $observation)
            <div class="table-row">
                <span>
                    <b>{{ $observation->fetched_at->format('d M Y') }}</b>
                    <small>{{ $observation->fetched_at->format('h:i A') }} IST</small>
                </span>
                <span><b>{{ $observation->carat }}</b></span>
                <span><b style="color: #4a0404;">₹{{ number_format($observation->price_per_gram, 2) }}</b></span>
                <span class="{{ $observation->market_change >= 0 ? 'success-link' : 'danger-text' }}">
                    {{ $observation->market_change >= 0 ? '+' : '' }}₹{{ number_format($observation->market_change, 2) }}
                </span>
                <span><b>{{ $observation->source }}</b></span>
            </div>
        @empty
            <div class="admin-empty">
                <strong>No observations stored</strong>
                <span>Refresh demo history or configure and synchronize an authorized provider.</span>
            </div>
        @endforelse
    </div>
</section>
@endsection

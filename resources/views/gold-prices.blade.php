@extends('layouts.app')
@section('title', 'Live Gold Price Dashboard')
@section('content')
    <section class="page-hero price-hero">
        <span class="kicker">Market intelligence</span>
        <h1>Gold prices, in perspective</h1>
        <p>Authorized rates, genuine historical movement and a clear timestamp—never a manually copied price.</p>
    </section>

    <section class="section rates-wrap">
        <div class="live-rate-bar" role="status">
            <span class="live-rate-dot"></span>
            <strong>Authorized market feed</strong>
            <span id="dashboard-status">Checking for updates…</span>
        </div>

        <div class="rate-cards">
            @foreach ($rates as $carat => $rate)
                <article>
                    <div>
                        <span>{{ $carat }} GOLD</span>
                        <small>Price per gram · INR</small>
                    </div>
                    <strong class="display-price" id="display-price-{{ $carat }}"
                        data-base-price="{{ $rate ? $rate->price_per_gram : 0 }}">
                        {{ $rate ? '₹' . number_format($rate->price_per_gram, 2) : 'Unavailable' }}
                    </strong>
                    <p id="rate-change-{{ $carat }}" class="{{ ($rate?->market_change ?? 0) >= 0 ? 'up' : 'down' }}">
                        @if ($rate)
                            {{ $rate->market_change >= 0 ? '▲' : '▼' }}
                            ₹{{ number_format(abs($rate->market_change), 2) }} today
                        @else
                            Awaiting authorized rate
                        @endif
                    </p>
                    <footer id="rate-meta-{{ $carat }}">
                        @if ($rate)
                            Updated {{ $rate->fetched_at->format('d M Y, h:i A') }} IST<br>
                            Source: {{ $rate->source }}
                        @else
                            No authorized observation stored
                        @endif
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="calculator-panel"
            style="margin-top:2rem; padding:1.5rem; background:rgba(0,0,0,0.03); border-radius:12px; margin-bottom:2rem;">
            <h3 style="margin-top:0; font-size:1.1rem;">Estimate Value by Weight</h3>
            <p class="muted">Uses the latest stored authorized national rate. Retail premiums, making charges and tax are not included.</p>
            <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.5rem;">
                <input type="range" id="gramSlider" min="1" max="100" value="10"
                    style="flex:1; accent-color:#b8862f; cursor:pointer;">
                <span style="font-weight:600; font-size:1.25rem; min-width:3rem; text-align:right;">
                    <span id="gramValue">10</span>g
                </span>
            </div>
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                @foreach ($rates as $carat => $rate)
                    <div
                        style="flex:1; min-width:200px; padding:1.25rem; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <span
                            style="display:block; font-size:0.85rem; color:#666; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                            {{ $carat }} Est. Value
                        </span>
                        <strong style="font-size:1.5rem; color:#111;" class="calculated-est"
                            id="est-{{ $carat }}" data-price="{{ $rate?->price_per_gram ?? 0 }}">
                            {{ $rate ? '₹' . number_format($rate->price_per_gram * 10, 2) : 'Unavailable' }}
                        </strong>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="market-layout">
            <article class="chart-panel">
                <div class="section-heading">
                    <div>
                        <span class="kicker dark">30-day movement</span>
                        <h2>Real price history</h2>
                    </div>
                    <div class="chart-legend">
                        <span class="dot dot-24"></span>24K
                        <span class="dot dot-22"></span>22K
                    </div>
                </div>
                <div class="chart-box">
                    <canvas id="goldChart" aria-label="Authorized gold price history graph"></canvas>
                    <div id="chart-empty" class="chart-empty" hidden>
                        <strong>Genuine history is being collected</strong>
                        <span>Backfill an authorized historical feed or keep the scheduler running to build the graph.</span>
                    </div>
                </div>
            </article>

            <aside class="recommendation">
                <span>MARKET SIGNAL</span>
                <h2 id="market-recommendation">{{ $recommendation }}</h2>
                <p>This rule-based indicator uses only the latest authorized daily movement. It is educational information,
                    not financial advice.</p>
                <dl>
                    <div>
                        <dt>Trend</dt>
                        <dd id="market-trend">{{ ($rates['24K']?->market_change ?? 0) >= 0 ? 'Positive' : 'Negative' }}</dd>
                    </div>
                    <div>
                        <dt>Data freshness</dt>
                        <dd id="market-freshness">{{ $service->isStale($rates['24K']) ? 'Stale' : 'Current' }}</dd>
                    </div>
                </dl>
                <a class="button full" href="{{ route('catalog.index') }}">Browse gold</a>
            </aside>
        </div>

        <div class="source-disclaimer">
            <b>ⓘ Data integrity notice</b>
            <p>The graph uses one observation per day from a single active source. Demo rows are never mixed with real
                provider rows. The browser checks this Laravel endpoint every {{ $pollSeconds }} seconds, while the scheduler
                obtains new authorized rates every 15 minutes.</p>
        </div>
    </section>

    <section class="section section-tint">
        <span class="kicker dark">Market briefing</span>
        <h2>What can move gold prices?</h2>
        <div class="news-grid">
            <article><span>Currency</span>
                <h3>INR–USD movement</h3>
                <p>International gold is commonly quoted in US dollars, so currency changes can affect the local rate.</p>
            </article>
            <article><span>Demand</span>
                <h3>Seasonal purchases</h3>
                <p>Festival, wedding and investment demand can influence retail premiums and availability.</p>
            </article>
            <article><span>Global markets</span>
                <h3>Interest and uncertainty</h3>
                <p>Rates, inflation expectations and global risk sentiment often shape demand for gold.</p>
            </article>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', () => {
            const endpoint = @json(route('gold-prices.data'));
            const initialHistory = @json($history->map(fn($rows) => $rows->map(fn($row) => ['x' => $row->fetched_at->format('Y-m-d'), 'y' => (float) $row->price_per_gram])->values()));
            const slider = document.getElementById('gramSlider');
            const gramValue = document.getElementById('gramValue');
            const status = document.getElementById('dashboard-status');
            const emptyState = document.getElementById('chart-empty');
            const money = new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            let chart;

            const datasets = history => [{
                label: '24K',
                data: history['24K'] || [],
                borderColor: '#b8862f',
                backgroundColor: 'rgba(184,134,47,.1)',
                tension: .35,
                fill: true,
                pointRadius: 2
            }, {
                label: '22K',
                data: history['22K'] || [],
                borderColor: '#173c34',
                backgroundColor: 'transparent',
                tension: .35,
                pointRadius: 2
            }];

            const setEmptyState = history => {
                const points = Math.max(history['24K']?.length || 0, history['22K']?.length || 0);
                emptyState.hidden = points >= 2;
            };

            const canvas = document.getElementById('goldChart');
            if (canvas && window.Chart) {
                chart = new Chart(canvas, {
                    type: 'line',
                    data: { datasets: datasets(initialHistory) },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        parsing: { xAxisKey: 'x', yAxisKey: 'y' },
                        scales: {
                            x: { type: 'category', grid: { display: false }, ticks: { maxTicksLimit: 6 } },
                            y: { ticks: { callback: value => '₹' + Number(value).toLocaleString('en-IN') } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => context.dataset.label + ': ' + money.format(context.parsed.y)
                                }
                            }
                        }
                    }
                });
                setEmptyState(initialHistory);
            }

            const updateEstimates = () => {
                const grams = Number(slider?.value || 10);
                if (gramValue) gramValue.textContent = grams;
                document.querySelectorAll('.calculated-est').forEach(element => {
                    const price = Number(element.dataset.price || 0);
                    element.textContent = price > 0 ? money.format(price * grams) : 'Unavailable';
                });
            };

            slider?.addEventListener('input', updateEstimates);
            updateEstimates();

            const updateRate = (carat, rate) => {
                const priceElement = document.getElementById('display-price-' + carat);
                const estimateElement = document.getElementById('est-' + carat);
                const changeElement = document.getElementById('rate-change-' + carat);
                const metaElement = document.getElementById('rate-meta-' + carat);
                if (!priceElement || !estimateElement || !changeElement || !metaElement) return;

                if (!rate) {
                    priceElement.textContent = 'Unavailable';
                    estimateElement.dataset.price = '0';
                    changeElement.textContent = 'Awaiting authorized rate';
                    metaElement.textContent = 'No authorized observation stored';
                    return;
                }

                priceElement.textContent = money.format(rate.price_per_gram);
                priceElement.dataset.basePrice = rate.price_per_gram;
                estimateElement.dataset.price = rate.price_per_gram;
                const positive = rate.market_change >= 0;
                changeElement.className = positive ? 'up' : 'down';
                changeElement.textContent = (positive ? '▲ ' : '▼ ') + money.format(Math.abs(rate.market_change)) + ' today';
                metaElement.replaceChildren(
                    document.createTextNode('Updated ' + rate.fetched_at_display + ' IST'),
                    document.createElement('br'),
                    document.createTextNode('Source: ' + rate.source)
                );
            };

            const refresh = async () => {
                try {
                    const response = await fetch(endpoint, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error('Rate endpoint returned ' + response.status);
                    const payload = await response.json();

                    updateRate('24K', payload.rates['24K']);
                    updateRate('22K', payload.rates['22K']);
                    updateEstimates();

                    const rate24 = payload.rates['24K'];
                    if (rate24) {
                        const change = Number(rate24.market_change);
                        document.getElementById('market-trend').textContent = change >= 0 ? 'Positive' : 'Negative';
                        document.getElementById('market-freshness').textContent = rate24.stale ? 'Stale' : 'Current';
                        document.getElementById('market-recommendation').textContent = change < -50
                            ? 'Favourable buying window'
                            : (change > 100 ? 'Consider watching the market' : 'Market is steady');
                    }

                    if (chart) {
                        chart.data.datasets = datasets(payload.history);
                        chart.update();
                        setEmptyState(payload.history);
                    }

                    status.textContent = 'Latest check ' + new Date().toLocaleTimeString('en-IN') +
                        ' · Source: ' + (payload.source || 'not configured');
                    status.closest('.live-rate-bar')?.classList.remove('has-error');
                } catch (error) {
                    status.textContent = 'Update check failed; showing the last verified stored rate.';
                    status.closest('.live-rate-bar')?.classList.add('has-error');
                    console.error(error);
                }
            };

            refresh();
            window.setInterval(refresh, {{ $pollSeconds * 1000 }});
        });
    </script>
@endpush

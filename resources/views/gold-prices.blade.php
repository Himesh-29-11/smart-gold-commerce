@extends('layouts.app')
@section('title', 'Live Gold Price Dashboard')
@php
    $modeHeroCopy = match ($dataMode) {
        'live' => 'Authorized rates, genuine historical movement and a clear timestamp—never a manually copied price.',
        'demo' => 'A clearly labelled demonstration dashboard. Connect a licensed provider before treating any value as a market quote.',
        default => 'No market observation is available yet. Configure and synchronize an authorized provider to begin.',
    };
    $feedLabel = match ($dataMode) {
        'live' => 'Authorized market feed',
        'demo' => 'Demonstration dataset',
        default => 'Data unavailable',
    };
    $historyLabel = match ($dataMode) {
        'live' => 'Authorized market history',
        'demo' => 'Demonstration price history',
        default => 'No price history',
    };
    $disclaimerTitle = $dataMode === 'demo' ? 'Demonstration data notice' : 'Data integrity notice';
    $disclaimerCopy = match ($dataMode) {
        'live' => "The graph uses one observation per day from the configured provider. The browser checks for stored updates every {$pollSeconds} seconds.",
        'demo' => 'These generated values exist only to demonstrate the interface and algorithms. They are not live, tradable or suitable for customer pricing.',
        default => 'No gold-price data is available. Configure a provider and run synchronization before enabling customer pricing.',
    };
@endphp
@section('content')
    <section class="page-hero price-hero">
        <span class="kicker">Market intelligence</span>
        <h1>Gold prices, in perspective</h1>
        <p>{{ $modeHeroCopy }}</p>
    </section>

    <section class="section rates-wrap">
        <div class="live-rate-bar {{ $dataMode === 'demo' ? 'is-demo' : '' }}" role="status">
            <span class="live-rate-dot"></span>
            <strong id="feed-kind">{{ $feedLabel }}</strong>
            <span id="dashboard-status">Checking for updates…</span>
        </div>

        <div class="market-summary-grid">
            @foreach ($rates as $carat => $rate)
                <article class="market-metric-card rate-metric-card">
                    <div class="metric-card-heading">
                        <span>{{ $carat }} GOLD</span>
                        <small>Per gram · INR</small>
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
                            {{ $rate->fetched_at->format('d M Y, h:i A') }} IST<br>
                            {{ $rate->source }}
                        @else
                            No authorized observation stored
                        @endif
                    </footer>
                </article>
            @endforeach

            <article class="market-metric-card signal-metric-card">
                <span class="metric-label">Market signal</span>
                <strong id="market-recommendation">{{ $recommendation }}</strong>
                <p>This rule uses the latest authorized 24K movement and is not financial advice.</p>
                <div class="metric-pair">
                    <span>Trend</span>
                    <b id="market-trend">{{ $marketTrend }}</b>
                </div>
            </article>

            <article class="market-metric-card feed-metric-card">
                <span class="metric-label">{{ $dataMode === 'live' ? 'Data status' : 'Demo status' }}</span>
                <strong id="market-freshness">{{ $service->isStale($rates['24K']) ? 'Stale' : 'Current' }}</strong>
                <p id="market-source">Source: {{ $service->activeSource() ?? 'Not configured' }}</p>
                <a href="{{ route('catalog.index') }}">Browse certified gold →</a>
            </article>
        </div>

        <article class="trend-chart-panel" id="live-trend-panel">
            <div class="trend-chart-heading">
                <div>
                    <span class="kicker" id="trend-data-label">{{ $historyLabel }}</span>
                    <h2>Gold price trend</h2>
                    <small>INR per 10 grams · one verified observation per day</small>
                </div>
                <div class="carat-switch" aria-label="Select gold purity">
                    <button class="active" type="button" data-carat="24K">24K</button>
                    <button type="button" data-carat="22K">22K</button>
                </div>
            </div>

            <div class="range-tabs" aria-label="Select chart period">
                <button type="button" data-range="5d">5D</button>
                <button class="active" type="button" data-range="1m">1M</button>
                <button type="button" data-range="1y">1Y</button>
                <button type="button" data-range="max">Max</button>
            </div>

            <div class="trend-chart-stage">
                <canvas id="goldChart" aria-label="Authorized gold price history graph"></canvas>
                <div id="chart-empty" class="chart-empty dark" hidden>
                    <strong>Genuine history is being collected</strong>
                    <span>Backfill an authorized historical feed or keep the scheduler running to build the graph.</span>
                </div>
            </div>

            <div class="trend-chart-footer">
                <span id="history-date-range">Last 30 calendar days</span>
                <span id="chart-series-label">24K · 10g</span>
            </div>
        </article>

        <div class="dashboard-support-grid">
            <section class="weight-calculator-card">
                <div class="support-card-heading">
                    <div>
                        <span class="metric-label">Weight estimator</span>
                        <h3>Estimate market value</h3>
                    </div>
                    <strong><span id="gramValue">10</span>g</strong>
                </div>
                <p>Uses the latest stored national rate. Retail premium, making charges and tax are excluded.</p>
                <input type="range" id="gramSlider" min="1" max="100" value="10">
                <div class="weight-estimates">
                    @foreach ($rates as $carat => $rate)
                        <div>
                            <span>{{ $carat }}</span>
                            <strong class="calculated-est" id="est-{{ $carat }}"
                                data-price="{{ $rate?->price_per_gram ?? 0 }}">
                                {{ $rate ? '₹' . number_format($rate->price_per_gram * 10, 2) : 'Unavailable' }}
                            </strong>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="dashboard-context-card">
                <span class="metric-label">Reading the chart</span>
                <h3>Clear, comparable movement</h3>
                <ul>
                    <li>Choose 5D, 1M, 1Y or Max.</li>
                    <li>Switch between 24K and 22K without reloading.</li>
                    <li>Hover or tap a point for its exact date and 10g price.</li>
                </ul>
            </section>
        </div>

        <div class="source-disclaimer {{ $dataMode === 'demo' ? 'demo-disclaimer' : '' }}">
            <b>ⓘ <span id="data-disclaimer-title">{{ $disclaimerTitle }}</span></b>
            <p id="data-disclaimer">{{ $disclaimerCopy }}</p>
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
            const dateRange = document.getElementById('history-date-range');
            const seriesLabel = document.getElementById('chart-series-label');
            const rangeButtons = document.querySelectorAll('[data-range]');
            const caratButtons = document.querySelectorAll('[data-carat]');
            const money = new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const axisMoney = new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                maximumFractionDigits: 0
            });
            const state = {
                range: '1m',
                carat: '24K',
                history: initialHistory
            };
            let chart;

            const parseHistoryDate = value => {
                const [year, month, day] = String(value).split('-').map(Number);
                return new Date(year, month - 1, day);
            };
            const shortDate = value => parseHistoryDate(value).toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short'
            });
            const fullDate = value => parseHistoryDate(value).toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            const tooltipDate = value => parseHistoryDate(value).toLocaleDateString('en-IN', {
                weekday: 'short',
                day: '2-digit',
                month: 'short'
            });
            const selectedPoints = history => (history[state.carat] || []).map(point => ({
                x: point.x,
                y: Number(point.y) * 10
            }));
            const lineColor = () => state.carat === '24K' ? '#ff867c' : '#71c7ad';
            const fillColor = () => state.carat === '24K' ? 'rgba(255,134,124,.14)' : 'rgba(113,199,173,.14)';
            const dataset = history => {
                const points = selectedPoints(history);

                return {
                    label: state.carat,
                    data: points,
                    borderColor: lineColor(),
                    backgroundColor: fillColor(),
                    borderWidth: 2.5,
                    tension: .18,
                    fill: true,
                    pointRadius: points.length === 1 ? 5 : 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: lineColor(),
                    pointHoverBackgroundColor: lineColor(),
                    pointHoverBorderColor: lineColor()
                };
            };
            const updateDateRange = history => {
                const dates = selectedPoints(history).map(point => point.x).sort();
                dateRange.textContent = dates.length
                    ? (dates.length === 1 ? fullDate(dates[0]) : shortDate(dates[0]) + ' – ' + fullDate(dates[dates.length - 1]))
                    : 'No authorized dates available';
                seriesLabel.textContent = state.carat + ' · 10g';
            };
            const setEmptyState = history => {
                emptyState.hidden = selectedPoints(history).length > 0;
            };
            const updateChart = (history, animate = false) => {
                state.history = history;
                updateDateRange(history);
                setEmptyState(history);
                if (!chart) return;
                chart.data.datasets = [dataset(history)];
                chart.update(animate ? undefined : 'none');
            };

            const hoverGuide = {
                id: 'hoverGuide',
                afterDatasetsDraw(chartInstance) {
                    const active = chartInstance.tooltip?.getActiveElements();
                    if (!active?.length) return;
                    const { ctx, chartArea } = chartInstance;
                    const x = active[0].element.x;
                    ctx.save();
                    ctx.beginPath();
                    ctx.setLineDash([2, 4]);
                    ctx.strokeStyle = 'rgba(255,255,255,.5)';
                    ctx.lineWidth = 1;
                    ctx.moveTo(x, chartArea.top);
                    ctx.lineTo(x, chartArea.bottom);
                    ctx.stroke();
                    ctx.restore();
                }
            };

            const canvas = document.getElementById('goldChart');
            if (canvas && window.Chart) {
                chart = new Chart(canvas, {
                    type: 'line',
                    data: { datasets: [dataset(initialHistory)] },
                    plugins: [hoverGuide],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 250 },
                        parsing: { xAxisKey: 'x', yAxisKey: 'y' },
                        interaction: { mode: 'nearest', intersect: false, axis: 'x' },
                        layout: { padding: { top: 8, right: 4 } },
                        scales: {
                            x: {
                                type: 'category',
                                border: { color: 'rgba(255,255,255,.16)' },
                                grid: { display: false },
                                ticks: {
                                    color: '#f0f2f4',
                                    autoSkip: true,
                                    maxRotation: 0,
                                    maxTicksLimit: 7,
                                    padding: 10,
                                    callback: function(value) {
                                        return shortDate(this.getLabelForValue(value));
                                    }
                                }
                            },
                            y: {
                                border: { display: false },
                                grace: '8%',
                                grid: { color: 'rgba(255,255,255,.14)' },
                                ticks: {
                                    color: '#f0f2f4',
                                    padding: 10,
                                    callback: value => axisMoney.format(Number(value))
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                displayColors: false,
                                backgroundColor: '#202329',
                                borderColor: 'rgba(255,255,255,.08)',
                                borderWidth: 1,
                                titleFont: { size: 0 },
                                bodyColor: '#f7f7f7',
                                bodyFont: { size: 15, weight: '600' },
                                padding: 12,
                                callbacks: {
                                    title: () => '',
                                    label: context => money.format(context.parsed.y) + '  ' + tooltipDate(context.raw.x)
                                }
                            }
                        }
                    }
                });
                updateChart(initialHistory);
                window.requestAnimationFrame(() => chart.resize());
                if ('ResizeObserver' in window) {
                    new ResizeObserver(() => chart?.resize())
                        .observe(document.getElementById('live-trend-panel'));
                }
            } else {
                emptyState.hidden = false;
                emptyState.querySelector('strong').textContent = 'Chart library could not be loaded';
                emptyState.querySelector('span').textContent = 'Rebuild the Vite assets and refresh this page.';
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

            const setPressed = (buttons, active) => buttons.forEach(button => {
                const selected = button.dataset.range === active || button.dataset.carat === active;
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });

            const refresh = async (animate = false) => {
                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('range', state.range);
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error('Rate endpoint returned ' + response.status);
                    const payload = await response.json();

                    updateRate('24K', payload.rates['24K']);
                    updateRate('22K', payload.rates['22K']);
                    document.getElementById('market-source').textContent = 'Source: ' +
                        (payload.source || 'Not configured');
                    updateEstimates();
                    updateChart(payload.history, animate);

                    const rate24 = payload.rates['24K'];
                    if (rate24) {
                        document.getElementById('market-trend').textContent = payload.signal.trend;
                        document.getElementById('market-freshness').textContent = rate24.stale ? 'Stale' : 'Current';
                        document.getElementById('market-recommendation').textContent = payload.signal.label;
                    }

                    const statusBar = status.closest('.live-rate-bar');
                    statusBar?.classList.remove('has-error');
                    statusBar?.classList.toggle('is-demo', payload.is_demo);
                    document.getElementById('feed-kind').textContent = payload.mode === 'demo'
                        ? 'Demonstration dataset'
                        : (payload.mode === 'live' ? 'Authorized market feed' : 'Data unavailable');
                    document.getElementById('trend-data-label').textContent = payload.mode === 'demo'
                        ? 'Demonstration price history'
                        : (payload.mode === 'live' ? 'Authorized market history' : 'No price history');
                    document.getElementById('data-disclaimer-title').textContent = payload.mode === 'demo'
                        ? 'Demonstration data notice'
                        : 'Data integrity notice';
                    document.getElementById('data-disclaimer').textContent = payload.disclaimer;
                    document.querySelector('.source-disclaimer')?.classList.toggle('demo-disclaimer', payload.is_demo);

                    const coverage = payload.coverage?.to ? ' · Through ' + payload.coverage.to : '';
                    status.textContent = 'Latest check ' + new Date().toLocaleTimeString('en-IN') + coverage +
                        ' · Source: ' + (payload.source || 'not configured');
                } catch (error) {
                    status.textContent = 'Update check failed; showing the last verified stored rate.';
                    status.closest('.live-rate-bar')?.classList.add('has-error');
                    console.error(error);
                }
            };

            rangeButtons.forEach(button => button.addEventListener('click', () => {
                state.range = button.dataset.range;
                setPressed(rangeButtons, state.range);
                refresh(true);
            }));
            caratButtons.forEach(button => button.addEventListener('click', () => {
                state.carat = button.dataset.carat;
                setPressed(caratButtons, state.carat);
                updateChart(state.history, true);
            }));
            setPressed(rangeButtons, state.range);
            setPressed(caratButtons, state.carat);

            refresh();
            window.setInterval(() => refresh(false), {{ $pollSeconds * 1000 }});
        });
    </script>
@endpush

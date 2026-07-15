@extends('layouts.app')
@section('title', 'Live Gold Price Dashboard')
@section('content')
    <section class="page-hero price-hero">
        <span class="kicker">Market intelligence</span>
        <h1>Gold prices, in perspective</h1>
        <p>Authorized rate feeds, historical movement and a clear timestamp—never a manually copied price.</p>
    </section>
    <section class="section rates-wrap">

        <div class="location-selector"
            style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
            <label for="citySelect" style="font-weight: 600; font-size: 0.9rem; color: #555;">Select Location:</label>
            <select id="citySelect"
                style="padding: 0.5rem 1rem; border: 1px solid #ccc; border-radius: 6px; font-size: 0.95rem; background: #fff; cursor: pointer; min-width: 200px;">
                <option value="0">National Average (IBJA)</option>
                <option value="-61.91">Ahmedabad</option>
                <option value="45.50">Mumbai</option>
                <option value="55.00">Delhi</option>
                <option value="85.20">Chennai</option>
            </select>
        </div>

        <div class="rate-cards">
            @foreach ($rates as $carat => $rate)
                <article>
                    <div><span>{{ $carat }} GOLD</span><small>Price per gram · INR</small></div><strong
                        class="display-price" id="display-price-{{ $carat }}"
                        data-base-price="{{ $rate ? $rate->price_per_gram : 0 }}">{{ $rate ? '₹' . number_format($rate->price_per_gram, 2) : 'Unavailable' }}</strong>
                    @if ($rate)
                        <p class="{{ $rate->market_change >= 0 ? 'up' : 'down' }}">{{ $rate->market_change >= 0 ? '▲' : '▼' }}
                            ₹{{ number_format(abs($rate->market_change), 2) }} today</p>
                        <footer>Updated {{ $rate->fetched_at->format('d M Y, h:i A') }} IST<br>Source: {{ $rate->source }}
                        </footer>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="calculator-panel"
            style="margin-top:2rem; padding:1.5rem; background:rgba(0,0,0,0.03); border-radius:12px; margin-bottom:2rem;">
            <h3 style="margin-top:0; font-size:1.1rem;">Estimate Value by Weight</h3>
            <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.5rem;">
                <input type="range" id="gramSlider" min="1" max="100" value="10"
                    style="flex:1; accent-color:#b8862f; cursor:pointer;">
                <span style="font-weight:600; font-size:1.25rem; min-width:3rem; text-align:right;"><span
                        id="gramValue">10</span>g</span>
            </div>
            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                @foreach ($rates as $carat => $rate)
                    @if ($rate)
                        <div
                            style="flex:1; min-width:200px; padding:1.25rem; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                            <span
                                style="display:block; font-size:0.85rem; color:#666; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">{{ $carat }}
                                Est. Value</span>
                            <strong style="font-size:1.5rem; color:#111;" class="calculated-est"
                                id="est-{{ $carat }}"
                                data-price="{{ $rate->price_per_gram }}">₹{{ number_format($rate->price_per_gram * 10, 2) }}</strong>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="market-layout">
            <article class="chart-panel">
                <div class="section-heading">
                    <div><span class="kicker dark">30-day movement</span>
                        <h2>Price history</h2>
                    </div>
                    <div class="chart-legend"><span class="dot dot-24"></span>24K <span class="dot dot-22"></span>22K</div>
                </div>
                <div class="chart-box"><canvas id="goldChart" aria-label="Gold price history graph"></canvas></div>
            </article>
            <aside class="recommendation"><span>MARKET SIGNAL</span>
                <h2>{{ $recommendation }}</h2>
                <p>This rule-based indicator uses only the latest daily movement. It is educational information, not
                    financial advice.</p>
                <dl>
                    <div>
                        <dt>Trend</dt>
                        <dd>{{ ($rates['24K']?->market_change ?? 0) >= 0 ? 'Positive' : 'Negative' }}</dd>
                    </div>
                    <div>
                        <dt>Data freshness</dt>
                        <dd>{{ $service->isStale($rates['24K']) ? 'Stale' : 'Current' }}</dd>
                    </div>
                </dl><a class="button full" href="{{ route('catalog.index') }}">Browse gold</a>
            </aside>
        </div>
        <div class="source-disclaimer"><b>ⓘ Data integrity notice</b>
            <p>Live production rates must come from a licensed market-data vendor or an approved partner API. Seeded records
                show “demo-seed-not-live” and must never be represented as a live quote. Product checkout recalculates the
                value from the latest stored authorized rate.</p>
        </div>
    </section>
    <section class="section section-tint"><span class="kicker dark">Market briefing</span>
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
            const canvas = document.getElementById('goldChart');
            if (!canvas || !window.Chart) return;
            const history = @json($history->map(fn($rows) => $rows->map(fn($r) => ['x' => $r->fetched_at->format('Y-m-d'), 'y' => (float) $r->price_per_gram])->values()));
            new Chart(canvas, {
                type: 'line',
                data: {
                    datasets: [{
                        label: '24K',
                        data: history['24K'] || [],
                        borderColor: '#b8862f',
                        backgroundColor: 'rgba(184,134,47,.1)',
                        tension: .35,
                        fill: true,
                        pointRadius: 0
                    }, {
                        label: '22K',
                        data: history['22K'] || [],
                        borderColor: '#173c34',
                        backgroundColor: 'transparent',
                        tension: .35,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    parsing: {
                        xAxisKey: 'x',
                        yAxisKey: 'y'
                    },
                    scales: {
                        x: {
                            type: 'category',
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 6
                            }
                        },
                        y: {
                            ticks: {
                                callback: v => '₹' + Number(v).toLocaleString('en-IN')
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: c => c.dataset.label + ': ₹' + Number(c.parsed.y).toLocaleString(
                                    'en-IN')
                            }
                        }
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const slider = document.getElementById('gramSlider');
            const gramValue = document.getElementById('gramValue');
            const estElements = document.querySelectorAll('.calculated-est');
            const citySelect = document.getElementById('citySelect');
            const displayPrices = {
                '24K': document.getElementById('display-price-24K'),
                '22K': document.getElementById('display-price-22K')
            };

            function updatePrices() {
                const premium = parseFloat(citySelect.value) || 0;

                // Update main rate cards
                Object.keys(displayPrices).forEach(carat => {
                    const el = displayPrices[carat];
                    if (el) {
                        const basePrice = parseFloat(el.getAttribute('data-base-price'));
                        if (basePrice > 0) {
                            // 22K premium is proportionally adjusted
                            const adjustedPremium = carat === '22K' ? premium * 0.916 : premium;
                            const newPrice = basePrice + adjustedPremium;
                            el.textContent = '₹' + newPrice.toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });

                            // Update data-price on estimator so slider uses the regional price
                            const estEl = document.getElementById('est-' + carat);
                            if (estEl) {
                                estEl.setAttribute('data-price', newPrice);
                            }
                        }
                    }
                });

                // Re-trigger slider calculation
                if (slider) {
                    slider.dispatchEvent(new Event('input'));
                }
            }

            if (citySelect) {
                citySelect.addEventListener('change', updatePrices);
            }

            if (slider && gramValue && estElements.length > 0) {
                slider.addEventListener('input', (e) => {
                    const grams = e.target.value;
                    gramValue.textContent = grams;

                    estElements.forEach(el => {
                        const pricePerGram = parseFloat(el.getAttribute('data-price'));
                        const total = pricePerGram * grams;
                        el.textContent = '₹' + total.toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    });
                });
            }
        });
    </script>
@endpush

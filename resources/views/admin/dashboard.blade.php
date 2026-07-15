@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Overview</span>
            <h1>Commerce pulse</h1>
            <p>Sales, fulfilment and assistance requests in one operational view.</p>
        </div><span>{{ now()->format('d M Y, h:i A') }} IST</span>
    </div>
    <div class="stat-grid">
        <article><span>Total paid revenue</span><strong>₹{{ number_format($stats['revenue'], 2) }}</strong><small>Verified
                payments, all time</small></article>
        <article><span>Orders</span><strong>{{ number_format($stats['orders']) }}</strong><small>Across all statuses</small>
        </article>
        <article><span>Customers</span><strong>{{ number_format($stats['customers']) }}</strong><small>Registered customer
                accounts</small></article>
        <article><span>Open loan requests</span><strong>{{ number_format($stats['loans']) }}</strong><small>Submitted or
                under review</small></article>
    </div>
    <div class="admin-panels">
        <article class="admin-panel chart-panel">
            <div class="section-heading">
                <div><span class="eyebrow">Last 30 days</span>
                    <h2>Verified sales</h2>
                </div>
            </div>
            <div class="chart-box admin-chart"><canvas id="salesChart"></canvas></div>
        </article>
        <article class="admin-panel"><span class="eyebrow">Action needed</span>
            <h2>Inventory health</h2>
            <div class="big-metric">{{ $stats['low_stock'] }}</div>
            <p>products at or below three units in stock.</p><a class="button button-outline full"
                href="{{ route('admin.products.index') }}">Review inventory</a>
        </article>
    </div>
    <article class="admin-panel">
        <div class="section-heading">
            <h2>Recent orders</h2><a href="{{ route('admin.orders.index') }}">Manage all →</a>
        </div>
        <div class="admin-table">
            <div class="table-row table-head">
                <span>Reference</span><span>Customer</span><span>Total</span><span>Payment</span><span>Status</span></div>
            @forelse($recentOrders as $order)
                <a class="table-row"
                    href="{{ route('orders.show', $order) }}"><span><b>{{ $order->reference }}</b><small>{{ $order->created_at->diffForHumans() }}</small></span><span>{{ $order->user->email }}</span><span>₹{{ number_format($order->total, 2) }}</span><span
                        class="status status-{{ $order->payment_status }}">{{ $order->payment_status }}</span><span
                    class="status status-{{ $order->status }}">{{ $order->status }}</span></a>@empty<p>No orders.</p>
            @endforelse
        </div>
    </article>
@endsection
@push('scripts')
    <script>
        window.addEventListener('load', () => {
            if (!window.Chart) return;
            const data = @json($sales);
            new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: data.map(x => x.day),
                    datasets: [{
                        data: data.map(x => x.total),
                        backgroundColor: '#b8862f',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                callback: v => '₹' + Number(v).toLocaleString('en-IN')
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush

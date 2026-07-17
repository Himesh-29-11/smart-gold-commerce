@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('admin-content')
    <div class="admin-heading"><div><span class="kicker dark">Live operations</span><h1>Commerce overview</h1><p>Verified sales, customer activity, inventory and assistance queues.</p></div><span>Updated {{ now()->format('d M Y, h:i A') }} IST</span></div>

    <div class="stat-grid">
        <article><span>Total paid revenue</span><strong>₹{{ number_format($stats['revenue'], 2) }}</strong><small>₹{{ number_format($stats['revenue_30d'], 2) }} in the last 30 days</small></article>
        <article><span>Orders</span><strong>{{ number_format($stats['orders']) }}</strong><small>{{ number_format($stats['pending_orders']) }} currently in fulfilment</small></article>
        <article><span>Customers</span><strong>{{ number_format($stats['customers']) }}</strong><small>{{ number_format($stats['active_customers']) }} active accounts</small></article>
        <article><span>Open loan requests</span><strong>{{ number_format($stats['loans']) }}</strong><small>Submitted, reviewing or awaiting documents</small></article>
    </div>

    <div class="admin-panels">
        <article class="admin-panel"><div class="section-heading"><div><span class="eyebrow">Last 30 days</span><h2>Verified sales</h2></div></div><div class="admin-chart"><canvas id="salesChart" aria-label="Paid order revenue chart"></canvas></div></article>
        <article class="admin-panel"><span class="eyebrow">Operational health</span><h2>Inventory & rate feed</h2><div class="big-metric">{{ $stats['low_stock'] }}</div><p>active products at or below three units.</p><dl class="admin-health-list"><div><dt>Price mode</dt><dd class="status {{ $goldDataMode === 'live' ? 'status-confirmed' : 'status-pending' }}">{{ $goldDataMode }}</dd></div><div><dt>Source</dt><dd>{{ $goldSource ?? 'Unavailable' }}</dd></div><div><dt>24K / gram</dt><dd>{{ $rates['24K'] ? '₹'.number_format($rates['24K']->price_per_gram, 2) : 'Unavailable' }}</dd></div></dl><a class="button button-outline full" href="{{ route('admin.products.index', ['stock' => 'low']) }}">Review low stock</a></article>
    </div>

    <section class="admin-panel"><div class="section-heading"><h2>Recent orders</h2><a href="{{ route('admin.orders.index') }}">Manage all →</a></div><div class="admin-table"><div class="table-row table-head"><span>Reference</span><span>Customer</span><span>Total</span><span>Payment</span><span>Status</span></div>@forelse ($recentOrders as $order)<a class="table-row" href="{{ route('orders.show', $order) }}"><span><b>{{ $order->reference }}</b><small>{{ $order->created_at->diffForHumans() }}</small></span><span><b>{{ $order->user->name }}</b><small>{{ $order->user->email }}</small></span><span><b>₹{{ number_format($order->total, 2) }}</b></span><span class="status status-{{ $order->payment_status }}">{{ $order->payment_status }}</span><span class="status status-{{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span></a>@empty<div class="admin-empty"><strong>No orders yet</strong><span>Paid and pending orders will appear here.</span></div>@endforelse</div></section>
@endsection
@push('scripts')
<script>
window.addEventListener('load',()=>{if(!window.Chart)return;const rows=@json($sales);const canvas=document.getElementById('salesChart');if(!canvas)return;new Chart(canvas,{type:'bar',data:{labels:rows.map(row=>row.day),datasets:[{data:rows.map(row=>Number(row.total)),backgroundColor:'#b8862f',borderRadius:3,maxBarThickness:34}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:context=>'Revenue: ₹'+Number(context.parsed.y).toLocaleString('en-IN')}}},scales:{x:{grid:{display:false},ticks:{maxTicksLimit:7}},y:{beginAtZero:true,ticks:{callback:value=>'₹'+Number(value).toLocaleString('en-IN')}}}}});});
</script>
@endpush

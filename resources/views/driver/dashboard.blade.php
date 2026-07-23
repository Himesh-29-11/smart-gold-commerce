@extends('layouts.driver')
@section('title','My Deliveries')
@section('content')
<section class="driver-page-heading"><span>Driver workspace</span><h1>My deliveries</h1><p>Accept assignments, share location only during active delivery, and complete handover securely.</p></section>
<div class="driver-assignment-list">@forelse($assignments as $assignment)<a href="{{ route('driver.deliveries.show',$assignment) }}" class="driver-assignment-card"><div><span class="status status-{{ $assignment->status }}">{{ $assignment->status }}</span><h2>{{ $assignment->shipment->tracking_number }}</h2><p>{{ $assignment->shipment->order->reference }} · {{ $assignment->shipment->order->user->name }}</p><small>{{ data_get($assignment->shipment->order->shipping_address,'city') }}, {{ data_get($assignment->shipment->order->shipping_address,'state') }}</small></div><i>→</i></a>@empty<div class="driver-empty"><span>🛵</span><h2>No assigned deliveries</h2><p>New assignments will appear here after an administrator assigns a paid shipment.</p></div>@endforelse</div>
@endsection

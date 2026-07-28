@extends('layouts.driver')
@section('title', $assignment->shipment->tracking_number)
@section('content')
<a class="driver-back" href="{{ route('driver.dashboard') }}">← My deliveries</a>
<section class="driver-delivery-hero">
    <div>
        <span>Tracking ID</span>
        <h1>{{ $assignment->shipment->tracking_number }}</h1>
        <p>Order {{ $assignment->shipment->order->reference }}</p>
    </div>
    <span class="status status-{{ $assignment->status }}" id="assignment-status">{{ $assignment->status }}</span>
</section>
<div class="driver-detail-grid">
    <section class="driver-panel">
        <h2>Delivery address</h2>
        <p>
            <b>{{ data_get($assignment->shipment->order->shipping_address, 'full_name') }}</b><br>
            {{ data_get($assignment->shipment->order->shipping_address, 'address_line_1') }}<br>
            {{ data_get($assignment->shipment->order->shipping_address, 'address_line_2') }}<br>
            {{ data_get($assignment->shipment->order->shipping_address, 'city') }},
            {{ data_get($assignment->shipment->order->shipping_address, 'state') }}
            {{ data_get($assignment->shipment->order->shipping_address, 'postal_code') }}
        </p>
        <p>
            <a href="tel:{{ data_get($assignment->shipment->order->shipping_address, 'phone') }}">Call customer:
                {{ data_get($assignment->shipment->order->shipping_address, 'phone') }}</a>
        </p>
    </section>
    <section class="driver-panel">
        <h2>Location sharing</h2>
        <p id="location-message">Location is off. It is sent only while this page is open and delivery is active.</p>
        <div style="display: flex; gap: 0.6rem; margin-bottom: 0.8rem; flex-wrap: wrap;">
            <button class="button full" type="button" id="location-toggle" @disabled(!in_array($assignment->status, ['accepted', 'active'], true))>Start location sharing</button>
            <button class="button button-outline full" type="button" id="simulate-toggle" @disabled(!in_array($assignment->status, ['accepted', 'active'], true))>Simulate demo GPS</button>
        </div>
        <div class="driver-location-stats">
            <span>Accuracy <b id="gps-accuracy">—</b></span>
            <span>Last sent <b id="gps-sent">—</b></span>
        </div>
    </section>
</div>
<section class="driver-panel driver-actions">
    <h2>Delivery actions</h2>
    @if ($assignment->status === 'assigned')
        <form method="POST" action="{{ route('driver.deliveries.accept', $assignment) }}">
            @csrf
            <button class="button" type="submit">Accept delivery</button>
        </form>
    @elseif($assignment->status === 'accepted')
        <form method="POST" action="{{ route('driver.deliveries.start', $assignment) }}">
            @csrf
            <button class="button" type="submit">Start delivery</button>
        </form>
    @elseif($assignment->status === 'active')
        <form method="POST" action="{{ route('driver.deliveries.complete', $assignment) }}"
            onsubmit="return confirm('Confirm that the customer received the package?')">
            @csrf
            <button class="button" type="submit">Mark delivered</button>
        </form>
    @else
        <p>No further action is available for this assignment.</p>
    @endif
</section>
<section class="driver-panel">
    <h2>Shipment timeline</h2>
    <ol class="driver-timeline">
        @foreach ($assignment->shipment->events as $event)
            <li>
                <span></span>
                <div>
                    <b>{{ $event->title }}</b>
                    <p>{{ $event->description }}</p>
                    <small>{{ $event->occurred_at->format('d M Y, h:i A') }}</small>
                </div>
            </li>
        @endforeach
    </ol>
</section>
<div class="driver-security"><b>Security:</b> Verify the customer and physical handover before marking delivered. Never
    request a payment OTP or card PIN.</div>
@endsection
@push('scripts')
<script>
window.addEventListener('load', () => {
    const button = document.getElementById('location-toggle');
    const simulateBtn = document.getElementById('simulate-toggle');
    if (!button) return;
    const endpoint = @json(route('driver.deliveries.location', $assignment));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let watchId = null;
    let simulateInterval = null;
    let simStep = 0;
    let lastSent = 0;

    const send = async (payload, isSimulated = false) => {
        if (!isSimulated && Date.now() - lastSent < 15000) return;
        lastSent = Date.now();
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        if (!response.ok) throw new Error('Location update failed');
        document.getElementById('gps-accuracy').textContent = Math.round(payload.accuracy) + (isSimulated ? ' m (Demo)' : ' m');
        document.getElementById('gps-sent').textContent = new Date().toLocaleTimeString('en-IN');
        document.getElementById('location-message').textContent = isSimulated
            ? 'Demo GPS simulation active — sending test coordinates every 15s.'
            : 'Approximate location is being shared securely.';
    };

    const stopAll = () => {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        if (simulateInterval !== null) {
            window.clearInterval(simulateInterval);
            simulateInterval = null;
        }
        button.textContent = 'Start location sharing';
        if (simulateBtn) simulateBtn.textContent = 'Simulate demo GPS';
        document.getElementById('location-message').textContent = 'Location sharing stopped.';
    };

    button.addEventListener('click', () => {
        if (watchId !== null || simulateInterval !== null) {
            stopAll();
            return;
        }
        if (!navigator.geolocation) {
            document.getElementById('location-message').textContent = 'This browser does not support location.';
            return;
        }
        watchId = navigator.geolocation.watchPosition(
            position => send({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                heading: position.coords.heading,
                speed: position.coords.speed
            }).catch(error => {
                document.getElementById('location-message').textContent = error.message;
            }),
            error => {
                document.getElementById('location-message').textContent =
                    'Location permission/error: ' + error.message +
                    ' (Tip: If testing inside an IDE preview iframe, click "Simulate demo GPS" instead!)';
            },
            { enableHighAccuracy: true, maximumAge: 15000, timeout: 30000 }
        );
        button.textContent = 'Stop location sharing';
    });

    simulateBtn?.addEventListener('click', () => {
        if (simulateInterval !== null || watchId !== null) {
            stopAll();
            return;
        }
        simulateBtn.textContent = 'Stop simulation';
        const runSim = () => {
            const lat = 23.0225 + (simStep * 0.0006);
            const lng = 72.5714 + (simStep * 0.0006);
            simStep++;
            send({
                latitude: lat,
                longitude: lng,
                accuracy: 5,
                heading: 45,
                speed: 25
            }, true).catch(err => {
                document.getElementById('location-message').textContent = 'Simulation error: ' + err.message;
            });
        };
        runSim();
        simulateInterval = window.setInterval(runSim, 15000);
    });
});
</script>
@endpush

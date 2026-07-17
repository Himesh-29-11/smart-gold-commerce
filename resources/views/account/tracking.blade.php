@extends('layouts.app')
@section('title', 'Track '.$order->reference)
@php
    $initialLocation = $shipment?->hasLocation()
        ? ['lat' => round((float) $shipment->current_latitude, 3), 'lng' => round((float) $shipment->current_longitude, 3)]
        : null;
@endphp
@section('content')
<section class="tracking-hero"><div><span class="kicker">Secure delivery tracking</span><h1>{{ $shipment?->tracking_number ?? 'Tracking pending' }}</h1><p>Order {{ $order->reference }} · Location is approximate and shown only when supplied by an approved courier.</p></div><span class="tracking-status" id="tracking-status">{{ str_replace('_',' ',$shipment?->status ?? 'preparing') }}</span></section>
<section class="section tracking-page">
    <div class="tracking-summary-grid">
        <article><span>Carrier</span><b>{{ $shipment?->carrier ?? 'Awaiting courier assignment' }}</b></article>
        <article><span>Estimated delivery</span><b>{{ $shipment?->estimated_delivery_at?->format('d M Y, h:i A') ?? 'To be confirmed' }}</b></article>
        <article><span>Latest location</span><b id="location-time">{{ $shipment?->location_updated_at?->diffForHumans() ?? 'Not available' }}</b></article>
    </div>

    <div class="tracking-layout">
        <article class="tracking-map-card">
            <div class="tracking-card-heading"><div><span class="eyebrow">Approximate courier location</span><h2>Delivery map</h2></div><span class="map-privacy">Rounded for security</span></div>
            @if($shipment?->hasLocation() && $googleMapsKey)
                <div id="delivery-map" class="delivery-map" aria-label="Approximate delivery location map"></div>
            @else
                <div class="map-fallback"><span>🛵</span><h3>Live map not available yet</h3><p>{{ $googleMapsKey ? 'The courier has not supplied a location update.' : 'Configure a restricted Google Maps browser key after courier location access is approved.' }}</p></div>
            @endif
        </article>

        <aside class="tracking-timeline-card"><div class="tracking-card-heading"><div><span class="eyebrow">Shipment activity</span><h2>Delivery timeline</h2></div></div><ol class="tracking-timeline" id="tracking-timeline">@forelse($shipment?->events ?? [] as $event)<li class="complete"><span></span><div><b>{{ $event->title }}</b><p>{{ $event->description }}</p><time>{{ $event->occurred_at->format('d M Y, h:i A') }} IST</time></div></li>@empty<li><span></span><div><b>Preparing tracking</b><p>A tracking ID is created after payment confirmation.</p></div></li>@endforelse</ol></aside>
    </div>

    <div class="tracking-security-note"><b>High-value delivery notice</b><p>For customer and courier safety, the map uses approximate coordinates. Always rely on the verified status timeline and never share an OTP before physically receiving and inspecting the package.</p></div>
</section>
@endsection
@push('scripts')
<script>
window.trackingState={map:null,marker:null};
window.initTrackingMap=function(){const point=@json($initialLocation);if(!point||!document.getElementById('delivery-map'))return;const map=new google.maps.Map(document.getElementById('delivery-map'),{center:point,zoom:13,mapTypeControl:false,streetViewControl:false,fullscreenControl:false});const marker=new google.maps.Marker({position:point,map,label:{text:'🛵',fontSize:'26px'},icon:{path:google.maps.SymbolPath.CIRCLE,scale:18,fillColor:'#ffffff',fillOpacity:.9,strokeColor:'#b8862f',strokeWeight:2}});window.trackingState={map,marker};};
window.addEventListener('load',()=>{const endpoint=@json(route('orders.tracking.data',$order));const refresh=async()=>{try{const response=await fetch(endpoint,{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)return;const data=await response.json();if(!data.shipment)return;document.getElementById('tracking-status').textContent=data.shipment.status.replaceAll('_',' ');if(data.shipment.location){document.getElementById('location-time').textContent='Updated '+new Date(data.shipment.location.updated_at).toLocaleString('en-IN');const point={lat:data.shipment.location.latitude,lng:data.shipment.location.longitude};window.trackingState.marker?.setPosition(point);window.trackingState.map?.panTo(point);}}catch(error){console.error(error);}};window.setInterval(refresh,30000);});
</script>
@if($shipment?->hasLocation() && $googleMapsKey)<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&callback=initTrackingMap"></script>@endif
@endpush

@extends('layouts.app')
@section('title', 'Track '.$order->reference)
@php
    $initialLocation = $shipment?->hasLocation()
        ? ['lat' => round((float) $shipment->current_latitude, 3), 'lng' => round((float) $shipment->current_longitude, 3)]
        : null;
@endphp
@section('content')
<section class="tracking-hero"><div><span class="kicker">Secure delivery tracking</span><h1>{{ $shipment?->tracking_number ?? 'Tracking pending' }}</h1><p>Order {{ $order->reference }} · Location is approximate and shown only when supplied by an approved delivery person.</p></div><span class="tracking-status" id="tracking-status">{{ str_replace('_',' ',$shipment?->status ?? 'preparing') }}</span></section>
<section class="section tracking-page">
    <div class="tracking-summary-grid"><article><span>Carrier</span><b>{{ $shipment?->carrier ?? 'N & H own-fleet delivery' }}</b></article><article><span>Estimated delivery</span><b>{{ $shipment?->estimated_delivery_at?->format('d M Y, h:i A') ?? 'To be confirmed' }}</b></article><article><span>Latest location</span><b id="location-time">{{ $shipment?->location_updated_at?->diffForHumans() ?? 'Not available' }}</b></article></div>
    <div class="tracking-layout">
        <article class="tracking-map-card"><div class="tracking-card-heading"><div><span class="eyebrow">Approximate courier location</span><h2>Delivery map</h2></div><span class="map-privacy">Rounded for security</span></div><div id="delivery-map" class="delivery-map" aria-label="Approximate delivery location map" @if(!$initialLocation) hidden @endif></div><div id="map-fallback" class="map-fallback" @if($initialLocation) hidden @endif><span>🛵</span><h3>Live map not available yet</h3><p>The map appears automatically after the assigned driver starts sharing location.</p></div></article>
        <aside class="tracking-timeline-card"><div class="tracking-card-heading"><div><span class="eyebrow">Shipment activity</span><h2>Delivery timeline</h2></div></div><ol class="tracking-timeline" id="tracking-timeline">@forelse($shipment?->events ?? [] as $event)<li class="complete"><span></span><div><b>{{ $event->title }}</b><p>{{ $event->description }}</p><time>{{ $event->occurred_at->format('d M Y, h:i A') }} IST</time></div></li>@empty<li><span></span><div><b>Preparing tracking</b><p>A tracking ID is created after payment confirmation.</p></div></li>@endforelse</ol></aside>
    </div>
    <div class="tracking-security-note"><b>High-value delivery notice</b><p>For customer and courier safety, the map uses approximate coordinates. Map data © OpenStreetMap contributors. Never share an OTP before physically receiving and inspecting the package.</p></div>
</section>
@endsection
@push('scripts')
<script>
window.addEventListener('load',()=>{
    const endpoint=@json(route('orders.tracking.data',$order));
    const initialPoint=@json($initialLocation);
    const mapElement=document.getElementById('delivery-map');
    const fallback=document.getElementById('map-fallback');
    let map=null;
    let marker=null;

    const ensureLeaflet=callback=>{
        if(window.L){
            callback();
            return;
        }
        const onReady=()=>{
            window.removeEventListener('leaflet:ready',onReady);
            callback();
        };
        window.addEventListener('leaflet:ready',onReady);
        const interval=window.setInterval(()=>{
            if(window.L){
                window.clearInterval(interval);
                window.removeEventListener('leaflet:ready',onReady);
                callback();
            }
        },25);
        window.setTimeout(()=>window.clearInterval(interval),10000);
    };

    const showPoint=point=>{
        if(!point||!mapElement)return;
        ensureLeaflet(()=>{
            const lat=Number(point.lat);
            const lng=Number(point.lng);
            if(Number.isNaN(lat)||Number.isNaN(lng))return;

            mapElement.hidden=false;
            mapElement.removeAttribute('hidden');
            if(fallback){
                fallback.hidden=true;
                fallback.setAttribute('hidden','true');
            }
            if(!map){
                map=L.map(mapElement,{zoomControl:true,attributionControl:true}).setView([lat,lng],14);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',crossOrigin:true}).addTo(map);
                const scooter=L.divIcon({className:'scooter-map-marker',html:'<span>🛵</span>',iconSize:[44,44],iconAnchor:[22,22]});
                marker=L.marker([lat,lng],{icon:scooter,title:'Approximate delivery location'}).addTo(map);
                window.setTimeout(()=>map?.invalidateSize(),50);
                window.setTimeout(()=>map?.invalidateSize(),250);
                window.addEventListener('resize',()=>map?.invalidateSize());
            }else{
                marker.setLatLng([lat,lng]);
                map.panTo([lat,lng]);
                map.invalidateSize();
            }
        });
    };

    showPoint(initialPoint);
    const refresh=async()=>{
        try{
            const response=await fetch(endpoint,{headers:{Accept:'application/json'},cache:'no-store'});
            if(!response.ok)return;
            const data=await response.json();
            if(!data.shipment)return;
            document.getElementById('tracking-status').textContent=data.shipment.status.replaceAll('_',' ');
            if(data.shipment.location){
                document.getElementById('location-time').textContent='Updated '+new Date(data.shipment.location.updated_at).toLocaleString('en-IN');
                showPoint({lat:data.shipment.location.latitude,lng:data.shipment.location.longitude});
            }
        }catch(error){console.error(error);}
    };
    window.setInterval(refresh,30000);
});
</script>
@endpush

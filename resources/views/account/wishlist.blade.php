@extends('layouts.app')
@section('title', 'Wishlist')
@section('content')
    <section class="page-hero compact">
        <span class="kicker">Saved for later</span>
        <h1>Your wishlist</h1>
        <p>Keep considered choices close while you compare.</p>
    </section>
    <section class="section">
        <div class="wishlist-grid">
            @forelse($items as $item)
                <article class="wishlist-item"><img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}">
                    <div><span class="eyebrow">{{ $item->product->purity }} · {{ $item->product->weight_grams }}g</span>
                        <h2><a href="{{ route('catalog.show', $item->product) }}">{{ $item->product->name }}</a></h2>
                        <p>{{ $item->product->certification }}</p>
                        <div class="card-actions"><a class="button" href="{{ route('catalog.show', $item->product) }}">View
                                product</a>
                            <form method="POST" action="{{ route('wishlist.toggle', $item->product) }}">@csrf<button
                                    class="button button-outline">Remove</button></form>
                        </div>
                    </div>
            </article>@empty<div class="empty-state wide"><span>♡</span>
                    <h2>No saved products yet</h2><a class="button" href="{{ route('catalog.index') }}">Explore gold</a>
                </div>
            @endforelse
        </div>
    </section>
@endsection

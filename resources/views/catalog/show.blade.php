@extends('layouts.app')
@section('title', $product->name)
@section('content')
    @php($price = $priceService->productPrice($product))
    <section class="section product-detail">
        <div class="product-gallery">
            <div class="main-product-image"><img src="{{ $product->image_url }}" alt="{{ $product->name }}"><span
                    class="purity-badge large">{{ $product->purity }}</span></div>
            @if ($product->gallery)
                <div class="media-gallery">
                    @foreach ($product->gallery as $media)
                        @if (data_get($media, 'type') === 'video')
                            <video controls preload="metadata" poster="{{ data_get($media, 'poster') }}">
                                <source src="{{ data_get($media, 'url') }}" type="video/mp4">Your browser does not support
                                video.
                            </video>
                        @elseif(data_get($media, 'type') === 'image')
                            <img src="{{ data_get($media, 'url') }}" alt="Additional view of {{ $product->name }}"
                                loading="lazy">
                        @endif
                    @endforeach
                </div>
            @endif
            <p>
                Actual product appearance may vary slightly. Product imagery is illustrative unless supplied by the
                contracted partner.</p>
        </div>
        <div class="product-info">
            <nav class="breadcrumbs"><a href="{{ route('catalog.index') }}">Collection</a> / <a
                    href="{{ route('catalog.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
            </nav><span class="eyebrow">{{ $product->partner?->name ?? 'Verified partner' }}</span>
            <h1>{{ $product->name }}</h1>
            <div class="rating"><span>★★★★★</span> <a
                    href="#reviews">{{ number_format($product->approvedReviews->avg('rating') ?: 0, 1) }} ·
                    {{ $product->approvedReviews->count() }} reviews</a></div>
            <div class="detail-price"><strong>₹{{ number_format($price, 2) }}</strong><span>Estimated product
                    value</span><small>GST is calculated at checkout. Price refreshes from the latest authorized
                    rate.</small></div>
            <div class="spec-grid">
                <div><span>Purity</span><b>{{ $product->purity }}</b></div>
                <div><span>Net weight</span><b>{{ rtrim(rtrim(number_format($product->weight_grams, 3), '0'), '.') }}
                        grams</b></div>
                <div><span>Certification</span><b>{{ $product->certification }}</b></div>
                <div><span>Availability</span><b
                        class="{{ $product->stock_quantity ? 'in-stock' : 'out-stock' }}">{{ $product->stock_quantity ? 'In stock' : 'Out of stock' }}</b>
                </div>
            </div>
            <p class="product-description">{{ $product->description }}</p>
            <div class="secure-note">⌾ <span><b>Secure, recorded purchase</b>Payment is processed by Razorpay or Stripe. An
                    invoice and complete price breakup are attached to paid orders.</span></div>
            <div class="buy-actions">@auth<form method="POST" action="{{ route('cart.store', $product) }}">@csrf<label>Qty
                            <input type="number" name="quantity" min="1" max="{{ min(10, $product->stock_quantity) }}"
                                value="1"></label><button class="button button-lg" @disabled(!$product->stock_quantity)>Add to
                            bag</button></form>
                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}">@csrf<button
                            class="button button-outline button-lg"
                        type="submit">{{ $wishlisted ? '♥ Saved' : '♡ Save' }}</button></form>@else<a class="button button-lg"
                    href="{{ route('login') }}">Sign in to purchase</a>@endauth
            </div>
        </div>
    </section>
    <section class="section detail-panels">
        <article><span>01</span>
            <h3>Price composition</h3>
            <dl>
                <div>
                    <dt>Gold value</dt>
                    <dd>Market rate × {{ $product->weight_grams }}g</dd>
                </div>
                <div>
                    <dt>Making / premium</dt>
                    <dd>₹{{ number_format($product->making_charge, 2) }}</dd>
                </div>
                <div>
                    <dt>GST</dt>
                    <dd>{{ number_format($product->gst_percentage, 2) }}%</dd>
                </div>
            </dl>
        </article>
        <article><span>02</span>
            <h3>Certification</h3>
            <p>{{ $product->certification }}. Hallmark reference:
                {{ $product->hallmark_number ?: 'Provided with dispatch documents' }}.</p>
        </article>
        <article><span>03</span>
            <h3>Partner assurance</h3>
            <p>{{ $product->partner?->description ?? 'Supplied by an approved jewellery partner after quality and compliance onboarding.' }}
            </p>
        </article>
    </section>
    <section class="section reviews" id="reviews">
        <div class="section-heading">
            <div><span class="kicker dark">Verified feedback</span>
                <h2>Customer reviews</h2>
            </div>
        </div>
        <div class="review-grid">
            @forelse($product->approvedReviews as $review)
                <blockquote>
                    <div class="rating">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                    <p>“{{ $review->comment }}”</p>
                    <footer><b>{{ $review->user->name }}</b><span>{{ $review->created_at->format('d M Y') }}</span>
                    </footer>
            </blockquote>@empty<p>No reviews yet.</p>
            @endforelse
        </div>
        @auth<div class="review-form">
                <h3>Review this product</h3>
                <p>Reviews are accepted from customers with a paid order for this item.</p>
                <form method="POST" action="{{ route('reviews.store', $product) }}">@csrf<label>Rating<select name="rating"
                            required>
                            <option value="5">5 – Excellent</option>
                            <option value="4">4 – Good</option>
                            <option value="3">3 – Average</option>
                            <option value="2">2 – Fair</option>
                            <option value="1">1 – Poor</option>
                        </select></label><label>Your review
                        <textarea name="comment" rows="4" minlength="10" maxlength="1000" required></textarea>
                    </label><button class="button" type="submit">Submit for review</button></form>
        </div>@endauth
    </section>
    @if ($related->count())
        <section class="section section-tint">
            <div class="section-heading">
                <h2>You may also consider</h2>
            </div>
            <div class="product-grid">
                @foreach ($related as $item)
                    <x-product-card :product="$item" :price-service="$priceService" />
                @endforeach
            </div>
        </section>
    @endif
@endsection

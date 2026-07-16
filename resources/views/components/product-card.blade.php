@php
    $price = $priceService->productPrice($product);
    $reviewCount = $product->approvedReviews->count();
    $averageRating = (float) ($product->approvedReviews->avg('rating') ?: 0);
    $weight = rtrim(rtrim(number_format($product->weight_grams, 3), '0'), '.');
@endphp

<article class="product-card">
    <a class="product-image" href="{{ route('catalog.show', $product) }}" aria-label="View {{ $product->name }}">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        <span class="purity-badge">{{ $product->purity }}</span>
        <span class="stock-badge {{ $product->stock_quantity > 0 ? 'available' : 'unavailable' }}">
            {{ $product->stock_quantity > 0 ? 'In stock' : 'Sold out' }}
        </span>
    </a>

    <div class="product-body">
        <span class="eyebrow">{{ $product->category->name }} · {{ $weight }}g</span>
        <h3><a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a></h3>

        <div class="rating" aria-label="Rating {{ number_format($averageRating, 1) }} out of 5">
            <span aria-hidden="true">★★★★★</span>
            <small>{{ number_format($averageRating, 1) }} · {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</small>
        </div>

        <div class="product-assurance">
            <span aria-hidden="true">✓</span>
            <small>{{ $product->certification }}</small>
        </div>

        <div class="price-row">
            <div>
                <small>Current product value</small>
                <strong>₹{{ number_format($price, 2) }}</strong>
            </div>
            <small>+ applicable GST</small>
        </div>

        <div class="card-actions">
            <a class="button button-outline" href="{{ route('catalog.show', $product) }}">Details</a>
            @auth
                <form method="POST" action="{{ route('cart.store', $product) }}">
                    @csrf
                    <button class="button" type="submit" @disabled($product->stock_quantity < 1)>
                        Add to bag
                    </button>
                </form>
            @else
                <a class="button" href="{{ route('login') }}">Add to bag</a>
            @endauth
        </div>
    </div>
</article>

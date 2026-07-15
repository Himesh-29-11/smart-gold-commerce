@php($price = $priceService->productPrice($product))
<article class="product-card">
    <a class="product-image" href="{{ route('catalog.show', $product) }}"><img src="{{ $product->image_url }}"
            alt="{{ $product->name }}" loading="lazy"><span class="purity-badge">{{ $product->purity }}</span></a>
    <div class="product-body">
        <span class="eyebrow">{{ $product->category->name }} ·
            {{ rtrim(rtrim(number_format($product->weight_grams, 3), '0'), '.') }}g</span>
        <h3><a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="rating"><span>★★★★★</span>
            <small>{{ number_format($product->approvedReviews->avg('rating') ?: 0, 1) }}
                ({{ $product->approvedReviews->count() }})</small></div>
        <div class="price-row"><strong>₹{{ number_format($price, 2) }}</strong><small>+ applicable GST</small></div>
        <div class="card-actions"><a class="button button-outline" href="{{ route('catalog.show', $product) }}">View
                details</a>@auth<form method="POST" action="{{ route('cart.store', $product) }}">@csrf<button
                    class="button" type="submit" @disabled($product->stock_quantity < 1)>Add to bag</button></form>@else<a
                class="button" href="{{ route('login') }}">Add to bag</a>@endauth
        </div>
    </div>
</article>

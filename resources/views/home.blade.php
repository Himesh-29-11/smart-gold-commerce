@extends('layouts.app')
@section('title', 'Certified Gold, Clearly Priced')
@section('content')
    <section class="hero">
        <img class="hero-media" src="{{ asset('images/hero-gold.jpg') }}" alt="Certified gold coins and jewellery">
        <div class="hero-overlay"></div>
        <div class="hero-content"><span class="kicker">A more considered way to buy gold</span>
            <h1>Gold you can trust.<br><em>Clarity you can see.</em></h1>
            <p>Certified products, market-linked pricing and verified partners—brought together in one secure experience.
            </p>
            <div class="hero-actions"><a class="button button-gold" href="{{ route('catalog.index') }}">Explore the
                    collection</a><a class="text-link light" href="{{ route('gold-prices') }}">See today’s rates →</a></div>
        </div>
        <div class="rate-float"><span>Indicative gold rate / gram</span>
            <div>
                @foreach ($rates as $carat => $rate)
                    <p><b>{{ $carat }}</b><strong>{{ $rate ? '₹' . number_format($rate->price_per_gram, 2) : 'Unavailable' }}</strong><small
                            class="{{ ($rate?->market_change ?? 0) >= 0 ? 'up' : 'down' }}">{{ ($rate?->market_change ?? 0) >= 0 ? '▲' : '▼' }}
                            ₹{{ number_format(abs($rate?->market_change ?? 0), 2) }}</small></p>
                @endforeach
            </div>
            <small>Updated {{ $rates['24K']?->fetched_at?->diffForHumans() ?? 'not yet' }} ·
                {{ $rates['24K']?->source ?? 'no source' }}</small>
        </div>
    </section>
    <section class="section intro">
        <span class="kicker dark">Shop by form</span>
        <h2>Gold for every intention</h2>
        <p>From investment-grade bars to hallmarked jewellery, compare the details that matter before you decide.</p>
        <div class="category-grid">
            @foreach ($categories as $category)
                <a class="category-card" href="{{ route('catalog.index', ['category' => $category->slug]) }}"><img
                        src="{{ $category->image_url }}" alt="{{ $category->name }}">
                    <div><span>{{ $category->products_count }} products</span>
                        <h3>{{ $category->name }}</h3>
                        <p>{{ $category->description }}</p><b>View collection →</b>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    <section class="section section-tint">
        <div class="section-heading">
            <div><span class="kicker dark">Curated collection</span>
                <h2>Gold, selected with care</h2>
            </div><a class="text-link" href="{{ route('catalog.index') }}">View all products →</a>
        </div>
        @php
            // Get unique weights from the featured products
            $availableWeights = $products->pluck('weight_grams')->map(fn($w) => round($w))->unique()->sort()->values();
        @endphp
        <div style="display: flex; gap: 10px; margin-bottom: 30px; justify-content: center; flex-wrap: wrap;">
            @foreach($availableWeights as $index => $weight)
                <button type="button" class="button {{ $index === 0 ? 'button-gold' : 'button-outline' }} weight-filter-btn" data-weight="{{ $weight }}">
                    {{ $weight }}g
                </button>
            @endforeach
        </div>
        <div class="product-grid" style="display: flex; justify-content: center;">
            @foreach ($products as $index => $product)
                <div class="product-wrapper" data-weight="{{ round($product->weight_grams) }}" style="max-width: 320px; width: 100%; display: {{ $index === 0 ? 'block' : 'none' }};">
                    <x-product-card :product="$product" :price-service="$priceService" />
                </div>
            @endforeach
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const buttons = document.querySelectorAll('.weight-filter-btn');
                const wrappers = document.querySelectorAll('.product-wrapper');

                buttons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Update button styles
                        buttons.forEach(b => {
                            b.classList.remove('button-gold');
                            b.classList.add('button-outline');
                        });
                        this.classList.remove('button-outline');
                        this.classList.add('button-gold');

                        const selectedWeight = this.getAttribute('data-weight');
                        
                        // Show ONLY the first product that matches the weight
                        let found = false;
                        wrappers.forEach(wrap => {
                            if (!found && wrap.getAttribute('data-weight') === selectedWeight) {
                                wrap.style.display = 'block';
                                found = true;
                            } else {
                                wrap.style.display = 'none';
                            }
                        });
                    });
                });
            });
        </script>
        @endpush
    </section>
    <section class="section trust-section">
        <div><span class="kicker dark">Built around confidence</span>
            <h2>Every detail, before you buy</h2>
            <p>Our experience is designed to make high-value purchases easier to understand and safer to complete.</p>
        </div>
        <div class="trust-grid">
            <article><i>01</i>
                <h3>Transparent price breakup</h3>
                <p>See gold value, making charges, taxes, discount and delivery before payment.</p>
            </article>
            <article><i>02</i>
                <h3>Purity & certification</h3>
                <p>Review carat, weight, hallmark and certification details on every product.</p>
            </article>
            <article><i>03</i>
                <h3>Verified partners</h3>
                <p>Only contractually onboarded jewellery and finance providers belong here.</p>
            </article>
            <article><i>04</i>
                <h3>Protected checkout</h3>
                <p>Payments are tokenized and completed on PCI-compliant provider infrastructure.</p>
            </article>
        </div>
    </section>
    <section class="loan-banner">
        <div><span class="kicker">Flexible purchase planning</span>
            <h2>Bring your gold purchase closer</h2>
            <p>Estimate EMIs and request assistance from verified independent loan providers. We connect you—we do not lend.
            </p><a class="button button-gold" href="{{ route('loans.index') }}">Explore financing</a>
        </div>
    </section>
    @if ($partners->count())
        <section class="section partners"><span class="kicker dark">Verified network</span>
            <h2>Partnered for trust</h2>
            <p>Production partners appear only after signed agreements, compliance checks and approved technical
                integration.</p>
            <div class="partner-row">
                @foreach ($partners as $partner)
                    <div>
                        <span>{{ strtoupper(substr($partner->name, 0, 1)) }}</span><strong>{{ $partner->name }}</strong><small>✓
                            Verified partner</small>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    @if ($reviews->count())
        <section class="section reviews"><span class="kicker dark">Customer notes</span>
            <h2>Experiences built on clarity</h2>
            <div class="review-grid">
                @foreach ($reviews as $review)
                    <blockquote>
                        <div class="rating">★★★★★</div>
                        <p>“{{ $review->comment }}”</p>
                        <footer><b>{{ $review->user->name }}</b><span>Verified customer ·
                                {{ $review->product->name }}</span></footer>
                    </blockquote>
                @endforeach
            </div>
        </section>
    @endif
@endsection

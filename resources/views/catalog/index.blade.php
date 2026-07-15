@extends('layouts.app')
@section('title', 'Gold Collection')
@section('content')
    <section class="page-hero compact">
        <span class="kicker">Certified collection</span>
        <h1>Find your gold</h1>
        <p>Compare purity, weight, certification and price in one place.</p>
    </section>
    <section class="section catalog-layout">
        <aside class="filter-panel">
            <div class="filter-title">
                <h2>Filters</h2><a href="{{ route('catalog.index') }}">Clear</a>
            </div>
            <form method="GET"><label>Search<input type="search" name="q" value="{{ request('q') }}"
                        placeholder="Product or SKU"></label><label>Category<select name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label><label>Purity<select name="purity">
                        <option value="">All purities</option>
                        <option @selected(request('purity') === '22K')>22K</option>
                        <option @selected(request('purity') === '24K')>24K</option>
                    </select></label><label>Weight<select name="weight">
                        <option value="">Any weight</option>
                        <option value="under-5" @selected(request('weight') === 'under-5')>Under 5g</option>
                        <option value="5-20" @selected(request('weight') === '5-20')>5g–20g</option>
                        <option value="over-20" @selected(request('weight') === 'over-20')>Over 20g</option>
                    </select></label><label>Sort<select name="sort">
                        <option value="newest">Newest</option>
                        <option value="weight-asc" @selected(request('sort') === 'weight-asc')>Weight: low to high</option>
                        <option value="weight-desc" @selected(request('sort') === 'weight-desc')>Weight: high to low</option>
                    </select></label><button class="button full" type="submit">Apply filters</button></form>
        </aside>
        <div>
            <div class="results-head">
                <p><strong>{{ $products->total() }}</strong> products found</p>
            </div>
            @if ($products->count())
                <div class="product-grid catalog-grid">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :price-service="$priceService" />
                    @endforeach
                </div>
                {{ $products->links() }}@else<div class="empty-state"><span>◇</span>
                    <h2>No matching gold found</h2>
                    <p>Try clearing a filter or changing your search.</p><a class="button"
                        href="{{ route('catalog.index') }}">View all gold</a>
                </div>
            @endif
        </div>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Gold Collection')
@section('content')
    @php
        $activeFilters = collect([
            'q' => request('q') ? 'Search: “' . request('q') . '”' : null,
            'category' => request('category')
                ? 'Category: ' . ($categories->firstWhere('slug', request('category'))?->name ?? request('category'))
                : null,
            'purity' => request('purity') ? 'Purity: ' . request('purity') : null,
            'weight' => request('weight')
                ? 'Weight: ' . match (request('weight')) {
                    'under-5' => 'Under 5g',
                    '5-20' => '5g–20g',
                    'over-20' => 'Over 20g',
                    default => request('weight'),
                }
                : null,
        ])->filter();
    @endphp

    <section class="page-hero compact catalog-hero">
        <span class="kicker">Certified collection</span>
        <h1>Find your gold</h1>
        <p>Compare purity, exact weight, certification and transparent pricing from verified partners.</p>
    </section>

    <section class="catalog-page">
        <div class="catalog-shell">
            <details class="filter-panel catalog-filters" open>
                <summary>
                    <span>Filter collection</span>
                    <small>{{ $activeFilters->count() ? $activeFilters->count() . ' active' : 'Refine results' }}</small>
                </summary>

                <div class="filter-title">
                    <div>
                        <span class="filter-eyebrow">Refine collection</span>
                        <h2>Filters</h2>
                    </div>
                    <a href="{{ route('catalog.index') }}">Clear all</a>
                </div>

                <form method="GET" action="{{ route('catalog.index') }}">
                    <label class="catalog-search-field">
                        <span>Search</span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Product name or SKU"
                            autocomplete="off">
                    </label>

                    <label>
                        <span>Category</span>
                        <select name="category">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Purity</span>
                        <select name="purity">
                            <option value="">All purities</option>
                            <option value="22K" @selected(request('purity') === '22K')>22K gold</option>
                            <option value="24K" @selected(request('purity') === '24K')>24K gold</option>
                        </select>
                    </label>

                    <label>
                        <span>Weight</span>
                        <select name="weight">
                            <option value="">Any weight</option>
                            <option value="under-5" @selected(request('weight') === 'under-5')>Under 5g</option>
                            <option value="5-20" @selected(request('weight') === '5-20')>5g–20g</option>
                            <option value="over-20" @selected(request('weight') === 'over-20')>Over 20g</option>
                        </select>
                    </label>

                    <label>
                        <span>Sort by</span>
                        <select name="sort">
                            <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                            <option value="weight-asc" @selected(request('sort') === 'weight-asc')>Weight: low to high</option>
                            <option value="weight-desc" @selected(request('sort') === 'weight-desc')>Weight: high to low</option>
                        </select>
                    </label>

                    <button class="button full" type="submit">Show matching gold</button>
                </form>
            </details>

            <div class="catalog-results">
                <header class="results-head">
                    <div>
                        <span class="results-kicker">Curated gold collection</span>
                        <h2>{{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}</h2>
                    </div>
                    <p>Prices use the latest stored authorized gold rate.</p>
                </header>

                @if ($activeFilters->isNotEmpty())
                    <div class="active-filter-list" aria-label="Active filters">
                        @foreach ($activeFilters as $key => $label)
                            <a href="{{ route('catalog.index', request()->except($key, 'page')) }}">
                                {{ $label }} <span aria-hidden="true">×</span>
                            </a>
                        @endforeach
                        <a class="clear-filter-chip" href="{{ route('catalog.index') }}">Clear all</a>
                    </div>
                @endif

                @if ($products->count())
                    <div class="product-grid catalog-grid">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" :price-service="$priceService" />
                        @endforeach
                    </div>
                    @if ($products->hasPages())
                        <nav class="catalog-pagination" aria-label="Product pages">
                            @if ($products->onFirstPage())
                                <span class="disabled" aria-disabled="true">Previous</span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" rel="prev">Previous</a>
                            @endif

                            @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                                @if ($page === $products->currentPage())
                                    <span class="current" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" rel="next">Next</a>
                            @else
                                <span class="disabled" aria-disabled="true">Next</span>
                            @endif
                        </nav>
                    @endif
                @else
                    <div class="empty-state catalog-empty-state">
                        <span>◇</span>
                        <h2>No matching gold found</h2>
                        <p>Try removing a filter or searching for another product.</p>
                        <a class="button" href="{{ route('catalog.index') }}">View all gold</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

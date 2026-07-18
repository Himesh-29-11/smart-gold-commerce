@extends('layouts.app')
@section('title', 'Gold & Jewellery Collection')
@section('content')
@php
    $activeFilters = collect([
        'q' => request('q') ? 'Search: “'.request('q').'”' : null,
        'category' => request('category') ? 'Category: '.($categories->firstWhere('slug', request('category'))?->name ?? request('category')) : null,
        'purity' => request('purity') ? 'Purity: '.request('purity') : null,
        'weight' => request('weight') ? 'Weight: '.match(request('weight')) {'under-5'=>'Under 5g','5-20'=>'5g–20g','over-20'=>'Over 20g',default=>request('weight')} : null,
    ])->filter();
@endphp

<section class="collection-masthead">
    <nav class="collection-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Gold collection</span></nav>
    <div><span class="kicker dark">Certified N & H collection</span><h1>Gold & Jewellery</h1><p>Discover hallmarked pieces, investment bars and coins with transparent market-linked pricing.</p></div>
</section>

<section class="collection-shortcuts" aria-label="Shop by category">
    @foreach($categories as $category)
        <a href="{{ route('catalog.index',['category'=>$category->slug]) }}" @class(['active'=>request('category')===$category->slug])><img src="{{ $category->image_url }}" alt=""><span>{{ $category->name }}</span></a>
    @endforeach
    <a href="{{ route('catalog.index') }}" @class(['active'=>!request('category')])><span class="shortcut-all">All</span><span>View all</span></a>
</section>

<section class="catalog-page tanishq-inspired-catalog">
    <div class="collection-toolbar">
        <div><strong>All products</strong><span>{{ number_format($products->total()) }} results</span></div>
        <details class="catalog-filter-drawer" @if($activeFilters->isNotEmpty()) open @endif>
            <summary><span>☷</span> Filter & sort @if($activeFilters->count())<b>{{ $activeFilters->count() }}</b>@endif</summary>
            <div class="filter-drawer-panel">
                <form method="GET" action="{{ route('catalog.index') }}">
                    <label>Search<input type="search" name="q" value="{{ request('q') }}" placeholder="Product name or SKU"></label>
                    <label>Category<select name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>{{ $category->name }}</option>@endforeach</select></label>
                    <label>Purity<select name="purity"><option value="">All purities</option><option value="22K" @selected(request('purity')==='22K')>22K gold</option><option value="24K" @selected(request('purity')==='24K')>24K gold</option></select></label>
                    <label>Weight<select name="weight"><option value="">Any weight</option><option value="under-5" @selected(request('weight')==='under-5')>Under 5g</option><option value="5-20" @selected(request('weight')==='5-20')>5g–20g</option><option value="over-20" @selected(request('weight')==='over-20')>Over 20g</option></select></label>
                    <label>Sort by<select name="sort"><option value="newest" @selected(request('sort','newest')==='newest')>New arrivals</option><option value="name-asc" @selected(request('sort')==='name-asc')>Name: A to Z</option><option value="weight-asc" @selected(request('sort')==='weight-asc')>Weight: low to high</option><option value="weight-desc" @selected(request('sort')==='weight-desc')>Weight: high to low</option></select></label>
                    <div class="filter-drawer-actions"><a href="{{ route('catalog.index') }}">Clear all</a><button class="button" type="submit">Apply filters</button></div>
                </form>
            </div>
        </details>
    </div>

    @if($activeFilters->isNotEmpty())
        <div class="active-filter-list" aria-label="Active filters">@foreach($activeFilters as $key=>$label)<a href="{{ route('catalog.index',request()->except($key,'page')) }}">{{ $label }} <span>×</span></a>@endforeach<a class="clear-filter-chip" href="{{ route('catalog.index') }}">Clear all</a></div>
    @endif

    @if($products->count())
        <div class="product-grid collection-product-grid">
            @foreach($products as $product)
                <x-product-card :product="$product" :price-service="$priceService" :wishlisted="in_array($product->id,$wishlistProductIds,true)" />
            @endforeach
        </div>
        @if($products->hasPages())
            <nav class="catalog-pagination" aria-label="Product pages">@if($products->onFirstPage())<span class="disabled">Previous</span>@else<a href="{{ $products->previousPageUrl() }}">Previous</a>@endif @foreach($products->getUrlRange(max(1,$products->currentPage()-2),min($products->lastPage(),$products->currentPage()+2)) as $page=>$url)@if($page===$products->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">Next</a>@else<span class="disabled">Next</span>@endif</nav>
        @endif
    @else
        <div class="empty-state catalog-empty-state"><span>◇</span><h2>No matching gold found</h2><p>Try clearing a filter or searching for another design.</p><a class="button" href="{{ route('catalog.index') }}">View all products</a></div>
    @endif
</section>
@endsection

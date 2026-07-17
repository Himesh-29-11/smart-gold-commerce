@extends('layouts.admin')
@section('title', 'Manage Products')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Catalog operations</span><h1>Products</h1><p>Manage product evidence, pricing, visibility and inventory.</p></div>
        <a class="button" href="{{ route('admin.products.create') }}">+ Add product</a>
    </div>

    <section class="admin-panel">
        <form class="admin-search" method="GET" action="{{ route('admin.products.index') }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU">
            <select name="visibility" aria-label="Visibility">
                <option value="">All visibility</option><option value="active" @selected(request('visibility') === 'active')>Active</option><option value="hidden" @selected(request('visibility') === 'hidden')>Hidden</option>
            </select>
            <select name="stock" aria-label="Stock level">
                <option value="">All stock</option><option value="available" @selected(request('stock') === 'available')>Available</option><option value="low" @selected(request('stock') === 'low')>Low stock</option><option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <button class="button button-outline" type="submit">Filter</button>
            <a class="admin-filter-clear" href="{{ route('admin.products.index') }}">Clear</a>
        </form>

        <div class="admin-table product-table">
            <div class="table-row table-head"><span>Product</span><span>Purity / weight</span><span>Pricing</span><span>Stock</span><span>Visibility</span><span>Actions</span></div>
            @forelse ($products as $product)
                <div class="table-row">
                    <span class="table-product"><img src="{{ $product->image_url }}" alt=""><span><b>{{ $product->name }}</b><small>{{ $product->sku }} · {{ $product->category->name }}</small></span></span>
                    <span><b>{{ $product->purity }}</b><small>{{ $product->weight_grams }}g</small></span>
                    <span><b>{{ ucfirst($product->pricing_mode) }}</b><small>Premium ₹{{ number_format($product->making_charge) }}</small></span>
                    <span @class(['danger-text' => $product->stock_quantity <= 3])><b>{{ $product->stock_quantity }}</b><small>units</small></span>
                    <span class="status {{ $product->is_active ? 'status-confirmed' : 'status-cancelled' }}">{{ $product->is_active ? 'Active' : 'Hidden' }}</span>
                    <span class="row-actions"><a href="{{ route('admin.products.edit', $product) }}">Edit</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button type="submit">Delete</button></form></span>
                </div>
            @empty
                <div class="admin-empty"><strong>No products found</strong><span>Adjust the filters or create a product.</span></div>
            @endforelse
        </div>
        @include('admin.partials.pagination', ['paginator' => $products])
    </section>
@endsection

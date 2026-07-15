@extends('layouts.admin')
@section('title', 'Manage Products')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Catalog</span>
            <h1>Products</h1>
            <p>Control certification details, pricing mode, inventory and visibility.</p>
        </div><a class="button" href="{{ route('admin.products.create') }}">+ Add product</a>
    </div>
    <article class="admin-panel">
        <form class="admin-search" method="GET"><input type="search" name="q" value="{{ request('q') }}"
                placeholder="Search name or SKU"><button class="button button-outline">Search</button></form>
        <div class="admin-table product-table">
            <div class="table-row table-head"><span>Product</span><span>Purity /
                    weight</span><span>Pricing</span><span>Stock</span><span>Visibility</span><span></span></div>
            @foreach ($products as $product)
                <div class="table-row"><span class="table-product"><img src="{{ $product->image_url }}"
                            alt=""><span><b>{{ $product->name }}</b><small>{{ $product->sku }} ·
                                {{ $product->category->name }}</small></span></span><span>{{ $product->purity }} /
                        {{ $product->weight_grams }}g</span><span>{{ ucfirst($product->pricing_mode) }}<small>+
                            ₹{{ number_format($product->making_charge) }}</small></span><span
                        @class(['danger-text' => $product->stock_quantity <= 3])>{{ $product->stock_quantity }}</span><span
                        class="status {{ $product->is_active ? 'status-confirmed' : 'status-cancelled' }}">{{ $product->is_active ? 'Active' : 'Hidden' }}</span><span
                        class="row-actions"><a href="{{ route('admin.products.edit', $product) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                            onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button
                                type="submit">Delete</button></form>
                    </span></div>
            @endforeach
        </div>{{ $products->links() }}
    </article>
@endsection

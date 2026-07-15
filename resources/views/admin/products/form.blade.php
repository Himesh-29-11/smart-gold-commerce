@extends('layouts.admin')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('admin-content')
    <div class="admin-heading">
        <div><span class="kicker dark">Catalog editor</span>
            <h1>{{ $product->exists ? 'Edit product' : 'Add product' }}</h1>
            <p>All purity and certification claims must match partner-supplied evidence.</p>
        </div><a class="button button-outline" href="{{ route('admin.products.index') }}">← Products</a>
    </div>
    <form class="admin-panel admin-form" method="POST"
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">@csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <div class="form-section">
            <h2>Identity</h2>
            <div class="form-grid"><label class="span-2">Product name<input name="name"
                        value="{{ old('name', $product->name) }}" required></label><label>SKU<input name="sku"
                        value="{{ old('sku', $product->sku) }}" required></label><label>Category<select name="category_id"
                        required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label><label>Jewellery partner<select name="partner_id">
                        <option value="">No partner</option>
                        @foreach ($partners as $partner)
                            <option value="{{ $partner->id }}" @selected(old('partner_id', $product->partner_id) == $partner->id)>{{ $partner->name }}</option>
                        @endforeach
                    </select></label><label class="span-2">Description
                    <textarea name="description" rows="5" required>{{ old('description', $product->description) }}</textarea>
                </label><label class="span-2">Image URL<input name="image_url"
                        value="{{ old('image_url', $product->image_url) }}" required><small>Use an approved local asset or
                        trusted media CDN URL.</small></label><label class="span-2">Gallery media JSON
                    <small>(optional)</small>
                    <textarea name="gallery_json" rows="4"
                        placeholder='[{"type":"video","url":"https://approved-cdn.example/story.mp4","poster":"/images/poster.jpg"}]'>{{ old('gallery_json', $product->gallery ? json_encode($product->gallery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea><small>Approved images: {type: image, url: …}; promotional videos: {type:
                        video, url: …, poster: …}. Never hotlink unlicensed media.</small>
                </label></div>
        </div>
        <div class="form-section">
            <h2>Gold details</h2>
            <div class="form-grid"><label>Purity<select name="purity">
                        <option @selected(old('purity', $product->purity) === '22K')>22K</option>
                        <option @selected(old('purity', $product->purity) === '24K')>24K</option>
                    </select></label><label>Weight (grams)<input type="number" step="0.001" min="0.001"
                        name="weight_grams" value="{{ old('weight_grams', $product->weight_grams) }}"
                        required></label><label class="span-2">Certification<input name="certification"
                        value="{{ old('certification', $product->certification) }}" required></label><label
                    class="span-2">Hallmark / HUID reference<input name="hallmark_number"
                        value="{{ old('hallmark_number', $product->hallmark_number) }}"></label></div>
        </div>
        <div class="form-section">
            <h2>Price & inventory</h2>
            <div class="form-grid"><label>Pricing mode<select name="pricing_mode">
                        <option value="live" @selected(old('pricing_mode', $product->pricing_mode) === 'live')>Live rate × weight</option>
                        <option value="fixed" @selected(old('pricing_mode', $product->pricing_mode) === 'fixed')>Fixed base price</option>
                    </select></label><label>Base price<input type="number" step="0.01" min="0" name="base_price"
                        value="{{ old('base_price', $product->base_price ?? 0) }}" required></label><label>Making charge /
                    premium<input type="number" step="0.01" min="0" name="making_charge"
                        value="{{ old('making_charge', $product->making_charge ?? 0) }}" required></label><label>GST
                    percentage<input type="number" step="0.01" min="0" max="30" name="gst_percentage"
                        value="{{ old('gst_percentage', $product->gst_percentage ?? 3) }}" required></label><label>Stock
                    quantity<input type="number" min="0" name="stock_quantity"
                        value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required></label>
                <div class="checks vertical"><label><input type="checkbox" name="is_active" value="1"
                            @checked(old('is_active', $product->exists ? $product->is_active : true))> Visible in store</label><label><input type="checkbox"
                            name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Feature on home page</label>
                </div>
            </div>
        </div><button class="button button-lg"
            type="submit">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
    </form>
@endsection

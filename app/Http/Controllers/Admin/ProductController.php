<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'visibility' => 'nullable|in:active,hidden',
            'stock' => 'nullable|in:available,low,out',
        ]);
        $query = Product::with(['category', 'partner']);

        if ($search = $data['q'] ?? null) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('sku', 'like', '%'.$search.'%'));
        }
        if (($data['visibility'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($data['visibility'] ?? null) === 'hidden') {
            $query->where('is_active', false);
        }
        match ($data['stock'] ?? null) {
            'available' => $query->where('stock_quantity', '>', 3),
            'low' => $query->whereBetween('stock_quantity', [1, 3]),
            'out' => $query->where('stock_quantity', 0),
            default => null,
        };

        return view('admin.products.index', [
            'products' => $query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product, 'categories' => Category::where('is_active', true)->get(), 'partners' => Partner::where('type', 'jewelry')->where('is_active', true)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']).'-'.strtolower(Str::random(5));
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::where('is_active', true)->get(), 'partners' => Partner::where('type', 'jewelry')->where('is_active', true)->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors(['product' => 'Products with orders cannot be deleted; mark it inactive instead.']);
        } $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate(['category_id' => 'required|exists:categories,id', 'partner_id' => 'nullable|exists:partners,id', 'name' => 'required|string|max:160', 'sku' => 'required|string|max:80|unique:products,sku,'.($product?->id ?? 'NULL'), 'description' => 'required|string|max:5000', 'purity' => 'required|in:22K,24K', 'weight_grams' => 'required|numeric|min:0.001|max:100000', 'certification' => 'required|string|max:120', 'hallmark_number' => 'nullable|string|max:120', 'pricing_mode' => 'required|in:live,fixed', 'base_price' => 'required|numeric|min:0', 'making_charge' => 'required|numeric|min:0', 'gst_percentage' => 'required|numeric|min:0|max:30', 'stock_quantity' => 'required|integer|min:0', 'image_url' => 'required|string|max:500', 'gallery_json' => 'nullable|json']);
        if (array_key_exists('gallery_json', $data)) {
            $data['gallery'] = $data['gallery_json'] ? json_decode($data['gallery_json'], true) : null;
            unset($data['gallery_json']);
        }

        return $data;
    }
}

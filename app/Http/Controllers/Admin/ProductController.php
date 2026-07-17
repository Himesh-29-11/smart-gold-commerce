<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
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
        return $this->formView(new Product);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $storedPaths = [];

        try {
            $primaryPath = $request->file('primary_image')->store('products/primary', 'public');
            $storedPaths[] = $primaryPath;
            $data['image_url'] = Storage::disk('public')->url($primaryPath);
            $data['gallery'] = $this->storeGalleryFiles($request, $storedPaths);
            $data['slug'] = Str::slug($data['name']).'-'.strtolower(Str::random(5));
            $data['is_active'] = $request->boolean('is_active');
            $data['is_featured'] = $request->boolean('is_featured');
            Product::create($data);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created with uploaded media.');
    }

    public function edit(Product $product): View
    {
        return $this->formView($product);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $newPaths = [];
        $deleteAfterSave = [];
        $gallery = collect($product->gallery ?? []);

        foreach (collect($request->input('remove_gallery', []))->map(fn ($index) => (int) $index)->unique()->sortDesc() as $index) {
            $media = $gallery->get($index);
            if ($media) {
                if ($path = $this->managedPath(data_get($media, 'url'))) {
                    $deleteAfterSave[] = $path;
                }
                $gallery->forget($index);
            }
        }

        try {
            if ($request->hasFile('primary_image')) {
                $primaryPath = $request->file('primary_image')->store('products/primary', 'public');
                $newPaths[] = $primaryPath;
                if ($oldPath = $this->managedPath($product->image_url)) {
                    $deleteAfterSave[] = $oldPath;
                }
                $data['image_url'] = Storage::disk('public')->url($primaryPath);
            }

            $newGallery = $this->storeGalleryFiles($request, $newPaths);
            $data['gallery'] = $gallery->values()->concat($newGallery)->values()->all() ?: null;
            $data['is_active'] = $request->boolean('is_active');
            $data['is_featured'] = $request->boolean('is_featured');
            $product->update($data);
            Storage::disk('public')->delete(array_unique($deleteAfterSave));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }

        return redirect()->route('admin.products.index')->with('success', 'Product and media updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors(['product' => 'Products with orders cannot be deleted; mark it inactive instead.']);
        }

        $paths = collect($product->gallery ?? [])->pluck('url')
            ->push($product->image_url)
            ->map(fn ($url) => $this->managedPath($url))
            ->filter()
            ->all();
        $product->delete();
        Storage::disk('public')->delete($paths);

        return back()->with('success', 'Product and uploaded media deleted.');
    }

    private function formView(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'partners' => Partner::where('type', 'jewelry')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'partner_id' => 'nullable|exists:partners,id',
            'name' => 'required|string|max:160',
            'sku' => 'required|string|max:80|unique:products,sku,'.($product?->id ?? 'NULL'),
            'description' => 'required|string|max:5000',
            'purity' => 'required|in:22K,24K',
            'weight_grams' => 'required|numeric|min:0.001|max:100000',
            'certification' => 'required|string|max:120',
            'hallmark_number' => 'nullable|string|max:120',
            'pricing_mode' => 'required|in:live,fixed',
            'base_price' => 'required|numeric|min:0',
            'making_charge' => 'required|numeric|min:0',
            'gst_percentage' => 'required|numeric|min:0|max:30',
            'stock_quantity' => 'required|integer|min:0',
            'primary_image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_files' => 'nullable|array|max:10',
            'gallery_files.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:25600',
            'gallery_folder' => 'nullable|array|max:20',
            'gallery_folder.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:25600',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'integer|min:0',
        ]);

        return Arr::except($data, ['primary_image', 'gallery_files', 'gallery_folder', 'remove_gallery']);
    }

    private function storeGalleryFiles(Request $request, array &$storedPaths): array
    {
        $files = collect($request->file('gallery_files', []))
            ->concat($request->file('gallery_folder', []))
            ->filter(fn ($file) => $file instanceof UploadedFile);

        return $files->map(function (UploadedFile $file) use (&$storedPaths): array {
            $path = $file->store('products/gallery', 'public');
            $storedPaths[] = $path;

            return [
                'type' => str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image',
                'url' => Storage::disk('public')->url($path),
                'name' => Str::limit($file->getClientOriginalName(), 120, ''),
            ];
        })->all();
    }

    private function managedPath(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        return Str::after($path, '/storage/');
    }
}

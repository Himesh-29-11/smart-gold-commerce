<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_uploaded_primary_and_gallery_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'otp_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $category = Category::create([
            'name' => 'Coins',
            'slug' => 'upload-test-coins',
            'is_active' => true,
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Uploaded Gold Coin',
            'sku' => 'UPLOAD-COIN-1',
            'description' => 'A product created through the secure media uploader.',
            'purity' => '24K',
            'weight_grams' => 1,
            'certification' => 'Test certificate',
            'pricing_mode' => 'fixed',
            'base_price' => 10000,
            'making_charge' => 500,
            'gst_percentage' => 3,
            'stock_quantity' => 5,
            'is_active' => 1,
            'primary_image' => UploadedFile::fake()->createWithContent('primary.png', $png)->mimeType('image/png'),
            'gallery_files' => [UploadedFile::fake()->createWithContent('gallery.png', $png)->mimeType('image/png')],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $product = Product::where('sku', 'UPLOAD-COIN-1')->firstOrFail();
        $this->assertStringStartsWith('/storage/products/primary/', $product->image_url);
        $this->assertSame('image', $product->gallery[0]['type']);
        Storage::disk('public')->assertExists(Str::after($product->image_url, '/storage/'));
        Storage::disk('public')->assertExists(Str::after($product->gallery[0]['url'], '/storage/'));
    }
}

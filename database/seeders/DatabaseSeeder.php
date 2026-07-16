<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\GoldPriceHistory;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(['email' => 'admin@aurumtrust.test'], ['name' => 'System Administrator', 'phone' => '9876500001', 'password' => Hash::make('Admin@12345'), 'role' => 'admin', 'is_active' => true, 'email_verified_at' => now(), 'otp_verified_at' => now()]);
        $customer = User::updateOrCreate(['email' => 'customer@aurumtrust.test'], ['name' => 'Demo Customer', 'phone' => '9876500002', 'password' => Hash::make('Customer@123'), 'role' => 'customer', 'is_active' => true, 'email_verified_at' => now(), 'otp_verified_at' => now()]);

        $categories = collect([
            ['name' => 'Gold Coins', 'slug' => 'coins', 'description' => 'Certified coins for gifting, saving and auspicious occasions.', 'image_url' => '/images/products/gold-coin.jpg'],
            ['name' => 'Gold Bars', 'slug' => 'bars', 'description' => 'Tamper-proof investment bars with purity certification.', 'image_url' => '/images/products/gold-bar.jpg'],
            ['name' => 'Gold Jewellery', 'slug' => 'jewellery', 'description' => 'Hallmarked contemporary jewellery from verified partners.', 'image_url' => '/images/products/gold-necklace.jpg'],
        ])->mapWithKeys(fn ($data) => [$data['slug'] => Category::updateOrCreate(['slug' => $data['slug']], $data + ['is_active' => true])]);

        $jeweler = Partner::updateOrCreate(['slug' => 'heritage-jewels-demo'], ['type' => 'jewelry', 'name' => 'Heritage Jewels (Demo)', 'description' => 'Demonstration partner profile. Replace with a contracted, verified jewellery partner before launch.', 'is_verified' => true, 'is_active' => true, 'meta' => ['certification' => 'BIS-compliant onboarding required']]);
        $loan1 = Partner::updateOrCreate(['slug' => 'finserve-demo'], ['type' => 'loan', 'name' => 'FinServe Capital (Demo)', 'description' => 'Illustrative lending partner for integration testing.', 'interest_rate_min' => 10.5, 'interest_rate_max' => 16.0, 'tenure_min_months' => 6, 'tenure_max_months' => 60, 'is_verified' => true, 'is_active' => true, 'meta' => ['processing_fee' => 'Up to 2%', 'disclaimer' => 'Terms subject to lender assessment']]);
        $loan2 = Partner::updateOrCreate(['slug' => 'trustcredit-demo'], ['type' => 'loan', 'name' => 'TrustCredit Finance (Demo)', 'description' => 'Illustrative regulated-provider profile for comparison UI.', 'interest_rate_min' => 11.25, 'interest_rate_max' => 17.5, 'tenure_min_months' => 3, 'tenure_max_months' => 48, 'is_verified' => true, 'is_active' => true, 'meta' => ['processing_fee' => 'Up to 1.5%', 'disclaimer' => 'Terms subject to lender assessment']]);
        $loan3 = Partner::updateOrCreate(['slug' => 'easyemi-demo'], ['type' => 'loan', 'name' => 'EasyEMI Financial (Demo)', 'description' => 'Illustrative financing provider. No application is transmitted externally in local mode.', 'interest_rate_min' => 12.0, 'interest_rate_max' => 18.0, 'tenure_min_months' => 6, 'tenure_max_months' => 36, 'is_verified' => true, 'is_active' => true, 'meta' => ['processing_fee' => 'Up to 1%', 'disclaimer' => 'Terms subject to lender assessment']]);

        if (GoldPriceHistory::count() === 0) {
            for ($days = 45; $days >= 0; $days--) {
                $date = $days === 0 ? now() : now()->subDays($days)->setTime(10, 0);
                $wave = sin($days / 4) * 75 + (45 - $days) * 4;
                $base = 10150 + $wave;
                foreach (['24K' => 1, '22K' => 0.9167] as $carat => $factor) {
                    GoldPriceHistory::create(['carat' => $carat, 'price_per_gram' => round($base * $factor, 2), 'currency' => 'INR', 'market_change' => round(cos($days / 3) * 42, 2), 'source' => 'demo-seed-not-live', 'fetched_at' => $date]);
                }
            }
        }

        $products = [
            ['category' => 'coins', 'name' => 'Lakshmi 24K Gold Coin – 1g', 'sku' => 'COIN-24K-1G', 'purity' => '24K', 'weight_grams' => 1, 'certification' => 'BIS Hallmarked / 999 Purity', 'making_charge' => 450, 'stock_quantity' => 24, 'image_url' => '/images/products/lakshmi_coin.png', 'featured' => true],
            ['category' => 'coins', 'name' => 'Classic 24K Gold Coin – 2g', 'sku' => 'COIN-24K-2G', 'purity' => '24K', 'weight_grams' => 2, 'certification' => 'BIS Hallmarked / 999 Purity', 'making_charge' => 700, 'stock_quantity' => 15, 'image_url' => '/images/products/gold-coin.jpg', 'featured' => true],
            ['category' => 'coins', 'name' => 'Celebration 22K Gold Coin – 5g', 'sku' => 'COIN-22K-5G', 'purity' => '22K', 'weight_grams' => 5, 'certification' => 'BIS Hallmarked / 916 Purity', 'making_charge' => 1100, 'stock_quantity' => 9, 'image_url' => '/images/products/celebration_coin.png', 'featured' => false],
            ['category' => 'bars', 'name' => 'Secure Assay 24K Gold Bar – 5g', 'sku' => 'BAR-24K-5G', 'purity' => '24K', 'weight_grams' => 5, 'certification' => '999 Purity Assay Certificate', 'making_charge' => 850, 'stock_quantity' => 12, 'image_url' => '/images/products/gold-bar.jpg', 'featured' => true],
            ['category' => 'bars', 'name' => 'Secure Assay 24K Gold Bar – 10g', 'sku' => 'BAR-24K-10G', 'purity' => '24K', 'weight_grams' => 10, 'certification' => '999 Purity Assay Certificate', 'making_charge' => 1300, 'stock_quantity' => 8, 'image_url' => '/images/products/assay_bar_10g.png', 'featured' => true],
            ['category' => 'bars', 'name' => 'Investment 24K Gold Bar – 20g', 'sku' => 'BAR-24K-20G', 'purity' => '24K', 'weight_grams' => 20, 'certification' => '999 Purity Assay Certificate', 'making_charge' => 2200, 'stock_quantity' => 5, 'image_url' => '/images/products/investment_bar_20g.png', 'featured' => false],
            ['category' => 'jewellery', 'name' => 'Aarohi 22K Gold Necklace – 18g', 'sku' => 'JEW-22K-N18', 'purity' => '22K', 'weight_grams' => 18, 'certification' => 'BIS Hallmarked / HUID', 'making_charge' => 18500, 'stock_quantity' => 3, 'image_url' => '/images/products/gold-necklace.jpg', 'featured' => true],
            ['category' => 'jewellery', 'name' => 'Meher 22K Gold Necklace – 24g', 'sku' => 'JEW-22K-N24', 'purity' => '22K', 'weight_grams' => 24, 'certification' => 'BIS Hallmarked / HUID', 'making_charge' => 24800, 'stock_quantity' => 2, 'image_url' => '/images/products/meher_necklace.png', 'featured' => true],
            ['category' => 'jewellery', 'name' => 'Nitya 22K Gold Pendant – 4g', 'sku' => 'JEW-22K-P4', 'purity' => '22K', 'weight_grams' => 4, 'certification' => 'BIS Hallmarked / HUID', 'making_charge' => 5200, 'stock_quantity' => 7, 'image_url' => '/images/products/nitya_pendant.png', 'featured' => true],
        ];
        $models = collect($products)->map(function ($data) use ($categories, $jeweler) {
            return Product::updateOrCreate(['sku' => $data['sku']], ['category_id' => $categories[$data['category']]->id, 'partner_id' => $jeweler->id, 'name' => $data['name'], 'slug' => Str::slug($data['name']), 'description' => 'A certified gold product with transparent weight, purity, pricing and partner details. Product imagery and partner data are demonstrative.', 'purity' => $data['purity'], 'weight_grams' => $data['weight_grams'], 'certification' => $data['certification'], 'hallmark_number' => 'DEMO-'.strtoupper(Str::random(8)), 'pricing_mode' => 'live', 'base_price' => 0, 'making_charge' => $data['making_charge'], 'gst_percentage' => 3, 'stock_quantity' => $data['stock_quantity'], 'image_url' => $data['image_url'], 'is_featured' => $data['featured'], 'is_active' => true]);
        });
        if (Review::count() === 0) {
            Review::create(['user_id' => $customer->id, 'product_id' => $models[0]->id, 'rating' => 5, 'comment' => 'Clear purity and weight details made comparing options straightforward.', 'is_approved' => true]);
            Review::create(['user_id' => $customer->id, 'product_id' => $models[3]->id, 'rating' => 4, 'comment' => 'The price breakdown and certification information are very helpful.', 'is_approved' => true]);
        }
        Coupon::updateOrCreate(['code' => 'WELCOME1000'], ['type' => 'fixed', 'value' => 1000, 'minimum_order' => 25000, 'maximum_discount' => 1000, 'usage_limit' => 500, 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonths(3), 'is_active' => true]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldPriceHistory;
use App\Services\DemoGoldPriceService;
use App\Services\GoldPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GoldPriceController extends Controller
{
    public function index(GoldPriceService $prices): View
    {
        $rates = $prices->latestRates();
        $history = $prices->dailyHistory(null);
        $observations = $history->flatten(1)->sortBy('fetched_at');
        $source = $prices->activeSource();

        return view('admin.gold-prices', [
            'rates' => $rates,
            'mode' => $prices->dataMode(),
            'source' => $source,
            'coverageFrom' => $observations->first()?->fetched_at,
            'coverageTo' => $observations->last()?->fetched_at,
            'points' => $history->map->count(),
            'provider' => config('gold.provider'),
            'apiKeyConfigured' => filled(config('gold.api_key')),
            'ahmedabadPremium' => (float) config('gold.ahmedabad_premium_percent', 0.45),
            'latestEndpointConfigured' => filled(config('gold.endpoint')),
            'historyEndpointConfigured' => filled(config('gold.history_endpoint')),
            'recentObservations' => GoldPriceHistory::latest('fetched_at')->limit(15)->get(),
            'queuedJobs' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
        ]);
    }

    public function refreshDemo(Request $request, DemoGoldPriceService $demo): RedirectResponse
    {
        if (config('gold.provider') !== 'database') {
            return back()->withErrors(['gold' => 'Demo refresh is available only when GOLD_PRICE_PROVIDER=database.']);
        }

        $data = $request->validate(['days' => 'required|integer|min:5|max:730']);
        $count = $demo->refresh((int) $data['days']);
        Log::notice('Administrator refreshed demo gold history.', [
            'admin_id' => $request->user()->id,
            'days' => $data['days'],
            'observations' => $count,
        ]);

        return back()->with('success', "Demo history refreshed through today ({$count} observations).");
    }

    public function sync(Request $request, GoldPriceService $prices): RedirectResponse
    {
        if (config('gold.provider') === 'database') {
            return back()->withErrors(['gold' => 'Configure an authorized provider before running live synchronization.']);
        }

        try {
            $rates = $prices->sync();
            Log::notice('Administrator ran gold-price synchronization.', [
                'admin_id' => $request->user()->id,
                'provider' => config('gold.provider'),
                'observations' => $rates->count(),
            ]);

            return back()->with('success', 'Latest authorized 22K and 24K observations synchronized.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['gold' => 'Synchronization failed: '.$exception->getMessage()]);
        }
    }

    public function fetchPublicReal(Request $request, GoldPriceService $prices): RedirectResponse
    {
        try {
            $rates = $prices->fetchLivePublicSpotRate();
            Log::notice('Administrator fetched real live spot gold price from open public API.', [
                'admin_id' => $request->user()->id,
                'observations' => $rates->count(),
            ]);

            return back()->with('success', 'Real live 22K and 24K Indian market gold spot rates synchronized from free open public API.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['gold' => 'Real live price fetch failed: '.$exception->getMessage()]);
        }
    }

    public function fetchAhmedabad(Request $request, GoldPriceService $prices): RedirectResponse
    {
        try {
            $rates = $prices->fetchAhmedabadLiveRate();
            Log::notice('Administrator fetched live Ahmedabad Sarafa Bazaar bullion gold rates.', [
                'admin_id' => $request->user()->id,
                'observations' => $rates->count(),
                'api_key_configured' => filled(config('gold.api_key')),
            ]);

            return back()->with('success', 'Ahmedabad Bullion Market (MCX Linked + Sarafa Bazaar premium) 22K and 24K gold rates synchronized!');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['gold' => 'Ahmedabad live price fetch failed: '.$exception->getMessage()]);
        }
    }

    public function backfill(Request $request): RedirectResponse
    {
        if (config('gold.provider') === 'database') {
            return back()->withErrors(['gold' => 'Historical provider backfill is unavailable in demo/database mode.']);
        }
        if (blank(config('gold.history_endpoint'))) {
            return back()->withErrors(['gold' => 'GOLD_PRICE_HISTORY_API_URL is not configured.']);
        }

        $data = $request->validate(['days' => 'required|integer|min:1|max:365']);
        Artisan::queue('gold:backfill-prices', ['--days' => (int) $data['days']])->onQueue('default');
        Log::notice('Administrator queued gold-price history backfill.', [
            'admin_id' => $request->user()->id,
            'provider' => config('gold.provider'),
            'days' => $data['days'],
        ]);

        return back()->with('success', 'Historical backfill queued. Monitor the default queue worker for completion.');
    }
}

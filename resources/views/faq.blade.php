@extends('layouts.app')
@section('title', 'Frequently Asked Questions & Support')
@section('content')
<section class="page-hero">
    <span class="kicker">Knowledge base &amp; customer care</span>
    <h1>Frequently Asked Questions</h1>
    <p>Find instant answers on BIS purity certification, Ahmedabad Sarafa Bazaar rates, independent financing assistance, and secure delivery tracking—or report an issue directly to our operations team.</p>
</section>

<section class="section">
    <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr); gap: 50px; align-items: start;">
        <div>
            <span class="kicker dark">Clear answers</span>
            <h2>Core Topics &amp; Commerce FAQ</h2>

            <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 20px;">
                <article class="address-card" style="margin: 0; border-radius: 12px;">
                    <h3 style="margin-top: 0; color: #4a0404;">Why are Ahmedabad gold rates different from global spot prices?</h3>
                    <p style="margin-bottom: 0;">In India, city-specific gold rates vary from international XAU/USD spot rates due to MCX (Multi Commodity Exchange of India) futures pricing, import duties, transportation charges, and local jewellers association markups. Our platform implements an Ahmedabad Sarafa Bazaar Bullion Engine that applies a configurable local premium (+0.45% default) over base INR spot rates.</p>
                </article>

                <article class="address-card" style="margin: 0; border-radius: 12px;">
                    <h3 style="margin-top: 0; color: #4a0404;">How is BIS Hallmark &amp; purity certified on my purchase?</h3>
                    <p style="margin-bottom: 0;">Every product card explicitly identifies its carat purity (22K or 24K) and Bureau of Indian Standards (BIS) hallmark number prior to purchase. Furthermore, your itemized invoice lists the exact gold weight, making charge, and 3% GST breakdown.</p>
                </article>

                <article class="address-card" style="margin: 0; border-radius: 12px;">
                    <h3 style="margin-top: 0; color: #4a0404;">Does N &amp; H Trust issue loans or finance gold directly?</h3>
                    <p style="margin-bottom: 0;">No. N &amp; H Trust is a luxury commerce platform, not an NBFC or lender. We provide an independent EMI estimator and route your consented assistance request to regulated lending partners. We never collect or store sensitive KYC documents locally.</p>
                </article>

                <article class="address-card" style="margin: 0; border-radius: 12px;">
                    <h3 style="margin-top: 0; color: #4a0404;">How does own-fleet GPS delivery tracking protect my order?</h3>
                    <p style="margin-bottom: 0;">High-value bullion shipments are assigned to verified own-fleet drivers. Once delivery begins, you can track approximate courier coordinates on an interactive Leaflet OpenStreetMap. For security, coordinates are rounded to a 100-meter privacy radius.</p>
                </article>

                <article class="address-card" style="margin: 0; border-radius: 12px;">
                    <h3 style="margin-top: 0; color: #4a0404;">What is my 4-Digit Customer ID and Order Code?</h3>
                    <p style="margin-bottom: 0;">Every registered user is assigned an ascending 4-digit Customer ID (starting from #1001), and every order receives an ascending 4-digit Order Code (starting from #5001, formatted as SGC-5001). This allows our operations team to locate your records instantly.</p>
                </article>
            </div>
        </div>

        <div class="form-card" style="border-radius: 14px;">
            <span class="kicker dark">Direct support</span>
            <h2 style="font-size: 22px; margin-bottom: 8px;">Report an Issue</h2>
            <p style="color: var(--muted); font-size: 13px; margin-bottom: 20px;">Experiencing a problem with an order, loan request, payment, or delivery? Submit a ticket and our team will review it.</p>

            <form method="POST" action="{{ route('faq.issues.store') }}">
                @csrf
                <div class="form-grid">
                    <label>Your name
                        <input type="text" name="name" value="{{ auth()->user()?->name ?? old('name') }}" required>
                    </label>
                    <label>Email address
                        <input type="email" name="email" value="{{ auth()->user()?->email ?? old('email') }}" required>
                    </label>
                    @auth
                    <div class="span-2" style="font-size: 12px; color: #4a0404; font-weight: 700; background: #fdfaf2; padding: 10px 14px; border-radius: 6px; border: 1px solid #dcc08b;">
                        Customer ID: #{{ auth()->user()->customer_code ?? auth()->user()->id }}
                    </div>
                    @endauth
                    <label class="span-2">Issue category
                        <select name="category" required>
                            <option value="general">General Inquiry</option>
                            <option value="order">Order &amp; Itemization Issue</option>
                            <option value="loan">Gold Financing / Loan Request</option>
                            <option value="payment">Payment &amp; Checkout Status</option>
                            <option value="delivery">Delivery &amp; GPS Tracking</option>
                        </select>
                    </label>
                    <label class="span-2">Subject / Order ID
                        <input type="text" name="subject" placeholder="e.g., Issue with Order #5001 or EMI Eligibility" value="{{ old('subject') }}" required>
                    </label>
                    <label class="span-2">Description of issue
                        <textarea name="message" rows="4" placeholder="Please describe the issue you are facing in detail..." required>{{ old('message') }}</textarea>
                    </label>
                </div>
                <button class="button button-gold full" type="submit" style="margin-top: 20px;">Submit issue to operations</button>
            </form>
        </div>
    </div>
</section>
@endsection

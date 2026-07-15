@extends('layouts.app')
@section('title', 'Gold Loan & Financing Assistance')
@section('content')
    <section class="page-hero loan-hero">
        <span class="kicker">Independent financing assistance</span>
        <h1>Plan the purchase.<br><em>Keep the choice yours.</em></h1>
        <p>Compare indicative terms, estimate an EMI and request contact from a verified loan provider. N & H Trust never
            lends directly.</p>
    </section>
    <section class="section loan-calculator">
        <div class="calculator-copy"><span class="kicker dark">EMI estimator</span>
            <h2>See what may fit your month</h2>
            <p>Adjust the amount, rate and duration. This estimate excludes processing fees, insurance and lender-specific
                charges.</p>
            <div class="legal-card"><b>We connect. We do not lend.</b>
                <p>Submitting a request does not guarantee approval. Final eligibility, rate, KYC and disbursal are
                    controlled by the selected regulated provider.</p>
            </div>
        </div>
        <div class="calculator-card" data-emi-calculator><label>Purchase amount <span id="amountOutput">₹2,00,000</span><input
                    id="loanAmount" type="range" min="10000" max="1000000" step="5000"
                    value="200000"></label><label>Indicative annual rate <span id="rateOutput">11.5%</span><input
                    id="loanRate" type="range" min="8" max="24" step="0.25"
                    value="11.5"></label><label>Tenure <span id="tenureOutput">24 months</span><input id="loanTenure"
                    type="range" min="3" max="60" value="24"></label>
            <div class="emi-result"><span>Estimated monthly EMI</span><strong id="emiOutput">₹9,367</strong><small
                    id="interestOutput">Approx. total interest ₹24,808</small></div>
        </div>
    </section>
    <section class="section section-tint">
        <div class="section-heading">
            <div><span class="kicker dark">Compare providers</span>
                <h2>Indicative partner terms</h2>
            </div>
            <p>Sorted by starting annual rate</p>
        </div>
        <div class="lender-table">
            <div class="lender-row lender-head"><span>Provider</span><span>Rate
                    range</span><span>Tenure</span><span>Processing</span><span></span></div>
            @forelse($partners as $partner)
                <div class="lender-row"><span><i>{{ substr($partner->name, 0, 1) }}</i><b>{{ $partner->name }}</b><small>✓
                            Verified demo
                            profile</small></span><span><b>{{ number_format($partner->interest_rate_min, 2) }}%–{{ number_format($partner->interest_rate_max, 2) }}%</b><small>per
                            annum</small></span><span>{{ $partner->tenure_min_months }}–{{ $partner->tenure_max_months }}
                        months</span><span>{{ data_get($partner->meta, 'processing_fee', 'Disclosed by provider') }}</span><a
                    href="#eligibility" class="button button-outline">Request help</a></div>@empty<div
                    class="empty-state">
                    <p>No providers are available.</p>
                </div>
            @endforelse
        </div>
    </section>
    <section class="section eligibility" id="eligibility">
        <div><span class="kicker dark">Eligibility request</span>
            <h2>Tell us a little about your plan</h2>
            <p>We use these details to calculate a preliminary affordability signal and route your request to the partner
                you select.</p>
            <h3>Documents generally requested</h3>
            <ul class="check-list">
                <li>PAN card</li>
                <li>Government-issued identity proof</li>
                <li>Current address proof</li>
                <li>Recent income proof</li>
                <li>Bank statements (usually 3–6 months)</li>
            </ul>
            <p class="muted">Do not upload documents here. A selected provider must collect them through its approved
                secure KYC channel.</p>
        </div>
        <div class="form-card">@auth<form method="POST" action="{{ route('loans.store') }}">@csrf<div class="form-grid">
                        <label class="span-2">Preferred provider<select name="partner_id" required>
                                <option value="">Select a verified provider</option>
                                @foreach ($partners as $partner)
                                    <option value="{{ $partner->id }}" @selected(old('partner_id') == $partner->id)>{{ $partner->name }} (from
                                        {{ $partner->interest_rate_min }}%)</option>
                                @endforeach
                            </select>
                        </label><label>Monthly income<input type="number" name="monthly_income" min="10000"
                                value="{{ old('monthly_income') }}" placeholder="₹"></label><label>Employment<select
                                name="employment_type">
                                <option value="salaried">Salaried</option>
                                <option value="self-employed">Self-employed professional</option>
                                <option value="business">Business owner</option>
                            </select></label><label>Requested amount<input type="number" name="requested_amount" min="10000"
                                value="{{ old('requested_amount', 200000) }}"></label><label>Tenure<select name="tenure_months">
                                @foreach ([6, 12, 18, 24, 36, 48, 60] as $months)
                                    <option value="{{ $months }}" @selected(old('tenure_months', 24) == $months)>{{ $months }}
                                        months</option>
                                @endforeach
                            </select>
                        </label><label class="span-2">Existing monthly EMIs<input type="number" name="existing_monthly_emi"
                                min="0" value="{{ old('existing_monthly_emi', 0) }}"></label>
                        <fieldset class="span-2">
                            <legend>Documents available (select at least two)</legend>
                            <div class="checks">
                                @foreach (['pan' => 'PAN', 'identity' => 'Identity proof', 'address' => 'Address proof', 'income' => 'Income proof', 'bank' => 'Bank statements'] as $value => $label)
                                    <label><input type="checkbox" name="documents[]" value="{{ $value }}">
                                        {{ $label }}</label>
                                @endforeach
                            </div>
                        </fieldset><label class="check span-2"><input type="checkbox" name="consent" value="1" required> I
                            consent to N & H Trust storing this request and sharing it with the selected verified provider. I
                            understand that this is not a loan approval.</label>
                </div><button class="button button-lg full" type="submit">Submit assistance request</button></form>@else
                <div class="auth-prompt"><span>◇</span>
                    <h3>Sign in to continue securely</h3>
                    <p>Your account lets you track the status of each request and limits unauthorized submissions.</p><a
                        class="button full" href="{{ route('login') }}">Sign in</a><a class="text-link"
                        href="{{ route('register') }}">Create an account</a>
            </div>@endauth
        </div>
    </section>
    @auth@if ($requests->count())
            <section class="section section-tint"><span class="kicker dark">Your requests</span>
                <h2>Approval status tracking</h2>
                <div class="status-list">
                    @foreach ($requests as $loan)
                        <article>
                            <div><b>{{ $loan->reference }}</b><span>{{ $loan->partner?->name }}</span></div>
                            <div><small>Amount</small><strong>₹{{ number_format($loan->requested_amount) }}</strong></div>
                            <div><small>Estimated EMI</small><strong>₹{{ number_format($loan->estimated_emi) }}</strong></div>
                            <div><small>Submitted</small><strong>{{ $loan->created_at->format('d M Y') }}</strong></div><span
                                class="status status-{{ $loan->status }}">{{ str_replace('_', ' ', $loan->status) }}</span>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif@endauth
    @endsection

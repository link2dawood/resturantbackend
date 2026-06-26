@extends('layouts.auth')

@section('title', 'Sign Up')

@php($states = \App\Helpers\USStates::getStates())

@section('content')
<style>
    /* Widen the auth card for the multi-step business form. */
    .page-center .container-tight { max-width: 46rem; }
    .wizard-steps { display: flex; gap: .5rem; margin-bottom: 1.25rem; }
    .wizard-steps__item { flex: 1; text-align: center; }
    .wizard-steps__bar { height: 4px; border-radius: 4px; background: #e6eaf0; margin-bottom: .4rem; transition: background .2s; }
    .wizard-steps__item.active .wizard-steps__bar,
    .wizard-steps__item.done .wizard-steps__bar { background: #206bc4; }
    .wizard-steps__label { font-size: .72rem; font-weight: 600; color: #9aa7b6; text-transform: uppercase; letter-spacing: .04em; }
    .wizard-steps__item.active .wizard-steps__label { color: #206bc4; }
    .wizard-steps__item.done .wizard-steps__label { color: #1f8a4c; }
</style>

<div class="text-center mb-4">
    <img src="{{ asset('images/logo.jpg') }}" height="36" alt="Restaurant Logo">
    <h1 class="h2 text-white mt-3">Create your owner account</h1>
    <p class="text-white-50">Tell us about your business to start your 30-day free trial.</p>
</div>

<div class="card card-md wizard-card">
    <div class="card-body">
        <div class="wizard-steps">
            <div class="wizard-steps__item" data-dot="0"><div class="wizard-steps__bar"></div><div class="wizard-steps__label">Account</div></div>
            <div class="wizard-steps__item" data-dot="1"><div class="wizard-steps__bar"></div><div class="wizard-steps__label">Business</div></div>
            <div class="wizard-steps__item" data-dot="2"><div class="wizard-steps__bar"></div><div class="wizard-steps__label">Restaurant</div></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">Please review the highlighted fields and complete every step.</div>
        @endif

        <form id="signupForm" action="{{ route('register') }}" method="POST" autocomplete="off" enctype="multipart/form-data" novalidate>
            @csrf

            {{-- ===================== STEP 1 — ACCOUNT ===================== --}}
            <div class="wizard-step" data-step="0">
                <h3 class="mb-3">Your account</h3>

                <div class="mb-3">
                    <label class="form-label required">Full name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                           value="{{ old('name') }}" placeholder="Jane Owner" autocomplete="name" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required">Email address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                           value="{{ old('email') }}" placeholder="you@business.com" autocomplete="email" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                               placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Confirm password</label>
                        <input type="password" class="form-control" name="password_confirmation"
                               placeholder="Re-enter password" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="form-footer d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" data-next>Continue</button>
                </div>
            </div>

            {{-- ================= STEP 2 — BUSINESS / CORPORATE ================= --}}
            <div class="wizard-step d-none" data-step="1">
                <h3 class="mb-3">Business details</h3>

                <div class="mb-3">
                    <label class="form-label">Business logo <span class="text-muted">(optional)</span></label>
                    <div class="d-flex align-items-center gap-3">
                        <span id="logo-preview" class="d-inline-flex align-items-center justify-content-center"
                              style="width:56px;height:56px;border:1px solid #e6eaf0;border-radius:10px;background:#f7f9fc;overflow:hidden;">
                            <img id="logo-preview-img" src="" alt="" style="display:none;max-width:100%;max-height:100%;">
                            <i class="bi bi-image text-muted" id="logo-preview-icon"></i>
                        </span>
                        <div class="flex-fill">
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo"
                                   id="logo-input" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                            <small class="form-hint">Shown on your dashboard instead of the default logo. PNG, JPG, SVG or WebP, up to 2&nbsp;MB.</small>
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label required">Corporate / business address</label>
                        <input type="text" class="form-control @error('corporate_address') is-invalid @enderror" name="corporate_address"
                               value="{{ old('corporate_address') }}" placeholder="123 Main St, Suite 100" required>
                        @error('corporate_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">State</label>
                        <select class="form-select @error('state') is-invalid @enderror" name="state" required>
                            <option value="">Select…</option>
                            @foreach ($states as $code => $label)
                                <option value="{{ $code }}" @selected(old('state') === $code)>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Business phone</label>
                        <input type="text" class="form-control @error('corporate_phone') is-invalid @enderror" name="corporate_phone"
                               value="{{ old('corporate_phone') }}" placeholder="(555) 123-4567" required>
                        @error('corporate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Business email</label>
                        <input type="email" class="form-control @error('corporate_email') is-invalid @enderror" name="corporate_email"
                               value="{{ old('corporate_email') }}" placeholder="office@business.com" required>
                        @error('corporate_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">EIN <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control @error('corporate_ein') is-invalid @enderror" name="corporate_ein"
                               value="{{ old('corporate_ein') }}" placeholder="12-3456789">
                        @error('corporate_ein')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Business start date <span class="text-muted">(optional)</span></label>
                        <input type="date" class="form-control @error('corporate_creation_date') is-invalid @enderror" name="corporate_creation_date"
                               value="{{ old('corporate_creation_date') }}">
                        @error('corporate_creation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-link" data-back>Back</button>
                    <button type="button" class="btn btn-primary" data-next>Continue</button>
                </div>
            </div>

            {{-- ================= STEP 3 — FIRST RESTAURANT ================= --}}
            <div class="wizard-step d-none" data-step="2">
                <h3 class="mb-3">Your first restaurant</h3>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Restaurant name</label>
                        <input type="text" class="form-control @error('store_info') is-invalid @enderror" name="store_info"
                               value="{{ old('store_info') }}" placeholder="Downtown Diner" required>
                        @error('store_info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Contact name</label>
                        <input type="text" class="form-control @error('store_contact_name') is-invalid @enderror" name="store_contact_name"
                               value="{{ old('store_contact_name') }}" placeholder="On-site manager or owner" required>
                        @error('store_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label required">Street address</label>
                        <input type="text" class="form-control @error('store_address') is-invalid @enderror" name="store_address"
                               value="{{ old('store_address') }}" placeholder="456 Market St" required>
                        @error('store_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">Phone</label>
                        <input type="text" class="form-control @error('store_phone') is-invalid @enderror" name="store_phone"
                               value="{{ old('store_phone') }}" placeholder="(555) 765-4321" required>
                        @error('store_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label required">City</label>
                        <input type="text" class="form-control @error('store_city') is-invalid @enderror" name="store_city"
                               value="{{ old('store_city') }}" placeholder="Philadelphia" required>
                        @error('store_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label required">State</label>
                        <select class="form-select @error('store_state') is-invalid @enderror" name="store_state" required>
                            <option value="">Select…</option>
                            @foreach ($states as $code => $label)
                                <option value="{{ $code }}" @selected(old('store_state') === $code)>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('store_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label required">ZIP</label>
                        <input type="text" class="form-control @error('store_zip') is-invalid @enderror" name="store_zip"
                               value="{{ old('store_zip') }}" placeholder="19102" required>
                        @error('store_zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Sales tax rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('store_sales_tax_rate') is-invalid @enderror"
                               name="store_sales_tax_rate" value="{{ old('store_sales_tax_rate', '0') }}" required>
                        @error('store_sales_tax_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Medicare tax rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control @error('store_medicare_tax_rate') is-invalid @enderror"
                               name="store_medicare_tax_rate" value="{{ old('store_medicare_tax_rate', '0') }}" required>
                        @error('store_medicare_tax_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="terms" value="1" class="form-check-input @error('terms') is-invalid @enderror" required {{ old('terms') ? 'checked' : '' }}>
                        <span class="form-check-label">I agree to the <a href="#" tabindex="-1">terms and policy</a>.</span>
                    </label>
                    @error('terms')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="form-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-link" data-back>Back</button>
                    <button type="submit" class="btn btn-success">Start free trial</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="text-center text-white-50 mt-3">
    Already have an account?
    <a href="{{ route('login') }}" class="text-white" tabindex="-1">Sign in</a>
</div>

@push('scripts')
<script>
(function () {
    const steps = Array.from(document.querySelectorAll('.wizard-step'));
    const dots  = Array.from(document.querySelectorAll('.wizard-steps__item'));
    let current = 0;

    function show(i) {
        current = Math.max(0, Math.min(i, steps.length - 1));
        steps.forEach((s, idx) => s.classList.toggle('d-none', idx !== current));
        dots.forEach((d, idx) => {
            d.classList.toggle('active', idx === current);
            d.classList.toggle('done', idx < current);
        });
        document.querySelector('.wizard-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Validate only the fields inside the current step before advancing.
    function validateStep(i) {
        const fields = steps[i].querySelectorAll('input, select, textarea');
        for (const el of fields) {
            if (!el.checkValidity()) { el.reportValidity(); return false; }
        }
        return true;
    }

    document.querySelectorAll('[data-next]').forEach(btn =>
        btn.addEventListener('click', () => { if (validateStep(current)) show(current + 1); }));
    document.querySelectorAll('[data-back]').forEach(btn =>
        btn.addEventListener('click', () => show(current - 1)));

    // Live preview of the chosen business logo.
    const logoInput = document.getElementById('logo-input');
    if (logoInput) {
        logoInput.addEventListener('change', () => {
            const file = logoInput.files && logoInput.files[0];
            const img = document.getElementById('logo-preview-img');
            const icon = document.getElementById('logo-preview-icon');
            if (file) {
                img.src = URL.createObjectURL(file);
                img.style.display = 'block';
                icon.style.display = 'none';
            } else {
                img.style.display = 'none';
                icon.style.display = 'block';
            }
        });
    }

    // Convenience: default the restaurant contact to the account name.
    const nameEl = document.querySelector('[name="name"]');
    const contactEl = document.querySelector('[name="store_contact_name"]');
    if (nameEl && contactEl) {
        nameEl.addEventListener('blur', () => { if (!contactEl.value) contactEl.value = nameEl.value; });
    }

    // --- Persist inputs to localStorage until signup completes ---------------
    const form = document.getElementById('signupForm');
    const STORAGE_KEY = 'signup_wizard_v1';

    // Everything except passwords, the file upload, and hidden fields (e.g. CSRF).
    function persistFields() {
        return Array.from(form.querySelectorAll('input, select, textarea')).filter(function (el) {
            return el.name && el.type !== 'password' && el.type !== 'file' && el.type !== 'hidden';
        });
    }
    function saveState() {
        const data = {};
        persistFields().forEach(function (el) {
            data[el.name] = (el.type === 'checkbox') ? el.checked : el.value;
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch (e) {}
    }
    function restoreState() {
        let data = {};
        try { data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch (e) {}
        persistFields().forEach(function (el) {
            if (!(el.name in data)) return;
            if (el.type === 'checkbox') { if (data[el.name]) el.checked = true; }
            else if (!el.value) { el.value = data[el.name]; } // server old() values win
        });
    }
    restoreState();
    saveState(); // capture any server old() values so a refresh keeps them too
    form.addEventListener('input', saveState);
    form.addEventListener('change', saveState);

    // --- Block completion until every step (incl. the terms box) is valid ----
    form.addEventListener('submit', function (e) {
        for (let i = 0; i < steps.length; i++) {
            const fields = steps[i].querySelectorAll('input, select, textarea');
            let ok = true;
            for (const el of fields) { if (!el.checkValidity()) { ok = false; break; } }
            if (!ok) {
                e.preventDefault();
                show(i);          // reveal the offending step first
                validateStep(i);  // then surface the native message
                return;
            }
        }
        // All good — signup is going through; drop the saved draft.
        try { localStorage.removeItem(STORAGE_KEY); } catch (e2) {}
    });

    // After a server-side validation error, open the step with the first problem.
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        const stepEl = firstInvalid.closest('.wizard-step');
        show(steps.indexOf(stepEl));
    } else {
        show(0);
    }
})();
</script>
@endpush
@endsection

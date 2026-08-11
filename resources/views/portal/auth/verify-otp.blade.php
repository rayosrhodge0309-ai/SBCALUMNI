@extends('layouts.app')

@section('title', 'Verify OTP')

@section('content')
    <div class="page-card p-3 p-md-5">
        <div class="text-center mb-4">
            <div class="stat-pill text-success bg-success-subtle mb-3">OTP Verification</div>
            <h1 class="h3 mb-2">Verify your alumni portal account</h1>
            <p class="text-secondary mb-0">
                We sent a 6-digit OTP to <strong>{{ $email }}</strong>. Enter it below to continue to your dashboard.
            </p>
        </div>

        @if (!empty($deliveryError))
            <div class="alert alert-warning">
                We could not send the OTP email right now. Please click <strong>Resend OTP</strong> to try again.
            </div>
        @endif

        <form method="POST" action="{{ route('portal.otp.store') }}">
            @csrf

            <div class="mb-3">
                <label for="otp" class="form-label">6-digit OTP</label>
                <input
                    id="otp"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    class="form-control text-center fw-semibold fs-4"
                    style="letter-spacing: 0.35rem; font-family: 'Courier New', monospace;"
                    name="otp"
                    value="{{ old('otp') }}"
                    placeholder="Enter OTP code"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="btn btn-success w-100">Verify and Continue</button>
        </form>

        <form method="POST" action="{{ route('portal.otp.resend') }}" class="mt-3">
            @csrf
            <button
                type="submit"
                id="resend-otp-button"
                class="btn btn-outline-secondary w-100"
                data-cooldown="{{ $resendCooldownSeconds }}"
                @disabled($resendCooldownSeconds > 0)
            >
                Resend OTP
            </button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const button = document.getElementById('resend-otp-button');

            if (!button) {
                return;
            }

            let remaining = Number(button.dataset.cooldown || 0);
            const defaultLabel = 'Resend OTP';

            const tick = () => {
                if (remaining <= 0) {
                    button.disabled = false;
                    button.textContent = defaultLabel;
                    return;
                }

                button.disabled = true;
                button.textContent = `Resend OTP (${remaining}s)`;
                remaining -= 1;
                setTimeout(tick, 1000);
            };

            tick();
        })();
    </script>
@endpush

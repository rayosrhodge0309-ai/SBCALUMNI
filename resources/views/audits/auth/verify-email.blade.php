@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
    <div class="main-wrapper py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>{{ __('Verify Your Email Address') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" role="alert">
                            {{ __('Please check your email for a verification link.') }}
                        </div>

                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        <p>{{ __('If you did not receive the email') }},</p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">{{ __('Request another verification link') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

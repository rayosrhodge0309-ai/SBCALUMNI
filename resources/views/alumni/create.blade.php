@extends('layouts.app')

@section('title', 'Add Alumni')
@section('subtitle', 'Create a new alumni record with the essential contact and graduation details.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('alumni.store') }}">
            @csrf
            @include('alumni._form', ['submitLabel' => 'Save Alumni', 'educationLevels' => $educationLevels])
        </form>
    </div>
@endsection

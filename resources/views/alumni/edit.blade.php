@extends('layouts.app')

@section('title', 'Edit Alumni')
@section('subtitle', 'Update the alumni record and keep contact details accurate.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('alumni.update', ['alumnus' => $alumni]) }}">
            @csrf
            @method('PUT')
            @include('alumni._form', ['submitLabel' => 'Update Alumni', 'educationLevels' => $educationLevels])
        </form>
    </div>
@endsection

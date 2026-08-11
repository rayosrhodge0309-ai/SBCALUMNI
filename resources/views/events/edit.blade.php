@extends('layouts.app')

@section('title', 'Edit Event')
@section('subtitle', 'Update event details, timing, and location information.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('events.update', $event) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('events._form', ['submitLabel' => 'Update Event'])
        </form>
    </div>
@endsection

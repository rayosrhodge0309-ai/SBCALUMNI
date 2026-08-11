@extends('layouts.app')

@section('title', 'Add Event')
@section('subtitle', 'Create an upcoming event for alumni activities and announcements.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data">
            @csrf
            @include('events._form', ['submitLabel' => 'Save Event'])
        </form>
    </div>
@endsection

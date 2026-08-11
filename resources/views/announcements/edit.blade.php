@extends('layouts.app')

@section('title', 'Edit Announcement')
@section('subtitle', 'Update this alumni-facing announcement.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('announcements.update', $announcement) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('announcements._form', ['submitLabel' => 'Update Announcement'])
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Create Announcement')
@section('subtitle', 'Post a notice that alumni can read from the public page and their portal.')

@section('content')
    <div class="page-card p-4">
        <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data">
            @csrf
            @include('announcements._form')
        </form>
    </div>
@endsection

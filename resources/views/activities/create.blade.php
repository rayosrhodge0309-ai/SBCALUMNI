@extends('layouts.app')

@section('title', 'Create Alumni Post')
@section('subtitle', 'Compose an SBC alumni post for the landing page and portal.')

@section('content')
    <form method="POST" action="{{ route('activities.store') }}" enctype="multipart/form-data" data-activity-composer>
        @csrf
        @include('activities._form')
    </form>
@endsection

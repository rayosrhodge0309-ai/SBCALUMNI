@extends('layouts.app')

@section('title', 'Edit Alumni Post')
@section('subtitle', 'Update the post before alumni see it.')

@section('content')
    <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data" data-activity-composer>
        @csrf
        @method('PUT')
        @include('activities._form', ['submitLabel' => 'Update Activity'])
    </form>
@endsection

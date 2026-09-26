@extends('layouts.app')

@section('title', 'School Levels & Programs')
@section('subtitle', 'Manage the grades, senior high strands, and college courses alumni can select when creating an account.')

@section('content')
    @php
        $levelDescriptions = [
            'Elementary' => 'Grade school levels',
            'Junior High School' => 'Junior high grade levels',
            'Senior High School' => 'Grade level and strand combinations',
            'College' => 'Degree and certificate programs',
        ];
        $programCount = $programsByLevel->flatten()->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="page-card p-3 h-100">
                <div class="small text-secondary text-uppercase fw-semibold">School levels</div>
                <div class="display-6 fw-bold text-primary">{{ count($educationLevels) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="page-card p-3 h-100">
                <div class="small text-secondary text-uppercase fw-semibold">Available choices</div>
                <div class="display-6 fw-bold text-primary">{{ $programCount }}</div>
            </div>
        </div>
    </div>

    <div class="page-card p-3 p-lg-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Add a grade, strand, or course</h2>
                <p class="text-secondary mb-0">New choices appear immediately on the alumni account registration form.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.academic-programs.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label for="new-education-level" class="form-label">School Level</label>
                <select id="new-education-level" name="education_level" class="form-select" required>
                    <option value="">Select level</option>
                    @foreach ($educationLevels as $level)
                        <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="new-program-name" class="form-label">Grade, Strand, or Course Name</label>
                <input id="new-program-name" name="name" type="text" class="form-control" value="{{ old('name') }}" maxlength="150" placeholder="e.g. Bachelor of Science in Nursing" required>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Add Choice</button>
            </div>
        </form>
    </div>

    <div class="row g-4">
        @foreach ($educationLevels as $level)
            @php($levelPrograms = $programsByLevel->get($level, collect()))
            <div class="col-12">
                <section class="page-card p-0 overflow-hidden" aria-labelledby="level-{{ $loop->index }}">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 p-lg-4 border-bottom bg-light">
                        <div>
                            <h2 id="level-{{ $loop->index }}" class="h5 mb-1">{{ $level }}</h2>
                            <p class="small text-secondary mb-0">{{ $levelDescriptions[$level] ?? 'Registration choices' }}</p>
                        </div>
                        <span class="badge rounded-pill text-bg-primary">{{ $levelPrograms->count() }} {{ \Illuminate\Support\Str::plural('choice', $levelPrograms->count()) }}</span>
                    </div>

                    <div class="p-3 p-lg-4">
                        @forelse ($levelPrograms as $program)
                            <div class="academic-program-row d-flex flex-column flex-lg-row gap-2 align-items-lg-center {{ ! $loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                                <form method="POST" action="{{ route('admin.academic-programs.update', $program) }}" class="row g-2 flex-grow-1 align-items-center">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-lg-4">
                                        <label class="visually-hidden" for="level-{{ $program->id }}">School Level</label>
                                        <select id="level-{{ $program->id }}" name="education_level" class="form-select form-select-sm" required>
                                            @foreach ($educationLevels as $availableLevel)
                                                <option value="{{ $availableLevel }}" @selected($program->education_level === $availableLevel)>{{ $availableLevel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="visually-hidden" for="program-{{ $program->id }}">Grade, strand, or course name</label>
                                        <input id="program-{{ $program->id }}" name="name" type="text" class="form-control form-control-sm" value="{{ $program->name }}" maxlength="150" required>
                                    </div>
                                    <div class="col-lg-2 d-grid">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('admin.academic-programs.destroy', $program) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Remove this choice from account registration? Existing alumni records will keep their saved value.')">Delete</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-center text-secondary py-4">
                                No choices are available for this school level. Use the form above to add one.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endforeach
    </div>
@endsection

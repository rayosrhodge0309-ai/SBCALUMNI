<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicProgramController extends Controller
{
    public function index(): View
    {
        $programs = AcademicProgram::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('education_level');

        return view('admin.academic-programs.index', [
            'educationLevels' => AcademicProgram::educationLevels(),
            'programsByLevel' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate($this->rules($request));

        $validated['sort_order'] = ((int) AcademicProgram::query()
            ->where('education_level', $validated['education_level'])
            ->max('sort_order')) + 1;

        AcademicProgram::create($validated);

        return redirect()
            ->route('admin.academic-programs.index')
            ->with('success', 'The grade, strand, or course was added. It is now available during account registration.');
    }

    public function update(Request $request, AcademicProgram $academicProgram): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate($this->rules($request, $academicProgram));

        if ($academicProgram->education_level !== $validated['education_level']) {
            $validated['sort_order'] = ((int) AcademicProgram::query()
                ->where('education_level', $validated['education_level'])
                ->max('sort_order')) + 1;
        }

        $academicProgram->update($validated);

        return redirect()
            ->route('admin.academic-programs.index')
            ->with('success', 'The grade, strand, or course was updated. The registration form now uses the new value.');
    }

    public function destroy(AcademicProgram $academicProgram): RedirectResponse
    {
        $academicProgram->delete();

        return redirect()
            ->route('admin.academic-programs.index')
            ->with('success', 'The grade, strand, or course was removed from account registration.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?AcademicProgram $academicProgram = null): array
    {
        return [
            'education_level' => ['required', 'string', Rule::in(AcademicProgram::educationLevels())],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('academic_programs', 'name')
                    ->where(fn ($query) => $query->where('education_level', $request->input('education_level')))
                    ->ignore($academicProgram),
            ],
        ];
    }
}

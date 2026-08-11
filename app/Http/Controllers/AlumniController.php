<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Support\StudentIdFormatter;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlumniController extends Controller
{
    private const PER_PAGE = 100;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $alumni = Alumni::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $studentIdSearchVariants = StudentIdFormatter::variants($search);

                $query->where(function ($searchQuery) use ($search, $studentIdSearchVariants) {
                    $searchQuery
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('education_level', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhere('year_graduated', 'like', "%{$search}%");

                    foreach ($studentIdSearchVariants as $studentIdSearch) {
                        $searchQuery->orWhere('student_id', 'like', "%{$studentIdSearch}%");
                    }
                });
            })
            ->orderBy('course')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('alumni.index', [
            'alumni' => $alumni,
            'search' => $search,
            'educationLevels' => $this->educationLevels(),
        ]);
    }

    public function create(): View
    {
        return view('alumni.create', [
            'educationLevels' => $this->educationLevels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'student_id' => trim((string) $request->input('student_id', '')),
        ]);

        $validated = $request->validate($this->rules());

        Alumni::create($validated);

        return redirect()->route('alumni.index')->with('success', 'Alumni added successfully');
    }

    public function edit(Alumni $alumnus): View
    {
        return view('alumni.edit', [
            'alumni' => $alumnus,
            'educationLevels' => $this->educationLevels(),
        ]);
    }

    public function update(Request $request, Alumni $alumnus, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $request->merge([
            'student_id' => trim((string) $request->input('student_id', '')),
        ]);

        $validated = $request->validate($this->rules($alumnus));

        if ($validated['student_id'] === $alumnus->student_id_display) {
            $validated['student_id'] = $alumnus->student_id;
        }

        $alumnus->update($validated);
        $syncService->syncUserFromAlumni($alumnus->fresh());

        return redirect()->route('alumni.index')->with('success', 'Updated successfully');
    }

    public function destroy(Alumni $alumnus): RedirectResponse
    {
        $this->deleteAlumniRecord($alumnus);

        return redirect()->route('alumni.index')->with('success', 'Deleted successfully');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alumni_ids' => ['required', 'array', 'min:1'],
            'alumni_ids.*' => ['integer', 'distinct', Rule::exists('alumni', 'id')],
        ]);

        $alumniRecords = Alumni::query()
            ->with('user')
            ->whereIn('id', $validated['alumni_ids'])
            ->get();

        $deletedCount = 0;

        foreach ($alumniRecords as $alumnus) {
            $this->deleteAlumniRecord($alumnus);
            $deletedCount++;
        }

        return redirect()
            ->route('alumni.index', $request->query())
            ->with('success', $deletedCount === 1
                ? '1 alumni record deleted successfully.'
                : "{$deletedCount} alumni records deleted successfully.");
    }

    public function createPortalAccount(Alumni $alumnus, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $result = $syncService->syncOrCreateUserFromAlumni($alumnus->fresh(), true);

        return match ($result) {
            'created' => redirect()
                ->route('alumni.index')
                ->with('success', 'Portal account created automatically from alumni email.'),
            'updated' => redirect()
                ->route('alumni.index')
                ->with('success', 'Existing alumni portal account activated and synchronized.'),
            'unchanged' => redirect()
                ->route('alumni.index')
                ->with('warning', 'This alumni record already has a portal account.'),
            'skipped_no_email' => redirect()
                ->route('alumni.index')
                ->with('warning', 'Add an email for this alumni record first to enable automatic portal account creation.'),
            'skipped_email_conflict' => redirect()
                ->route('alumni.index')
                ->with('warning', 'That email is already used by another account. Update the alumni email first.'),
            default => redirect()
                ->route('alumni.index')
                ->with('warning', 'Portal account could not be created automatically.'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function rules(?Alumni $alumnus = null): array
    {
        $linkedUserId = $alumnus?->user?->id;

        return [
            'student_id' => ['required', 'string', 'max:50', Rule::unique('alumni', 'student_id')->ignore($alumnus?->id)],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birthday' => 'nullable|date|before_or_equal:today',
            'education_level' => 'required|string|max:100',
            'course' => 'required|string|max:150',
            'year_graduated' => 'required|integer|min:1900|max:'.(now()->year + 1),
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('alumni', 'email')->ignore($alumnus?->id),
                Rule::unique('users', 'email')->ignore($linkedUserId),
            ],
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function educationLevels(): array
    {
        return [
            'Elementary',
            'Junior High School',
            'Senior High School',
            'College',
        ];
    }

    private function deleteAlumniRecord(Alumni $alumnus): void
    {
        $alumnus->loadMissing('user');

        $linkedUser = $alumnus->user;

        if ($linkedUser) {
            if ($linkedUser->profile_photo_path) {
                Storage::disk('public')->delete($linkedUser->profile_photo_path);
            }

            $linkedUser->delete();
        }

        $alumnus->delete();
    }

    private function normalizeStudentId(string $studentId): string
    {
        return StudentIdFormatter::normalize($studentId);
    }
}

<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\User;
use App\Support\GmailAddress;
use App\Support\StudentIdFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AlumniAccountReviewService
{
    public function matchingRecords(User $user, string $search = ''): Collection
    {
        $profile = $user->registration_profile;
        $studentId = StudentIdFormatter::normalize((string) $profile->student_id);
        $firstNames = array_unique(array_map(
            static fn ($name): string => Str::lower(trim((string) $name)),
            [$profile->first_name, $user->registration_details['first_name'] ?? $profile->first_name],
        ));
        $surnames = array_filter([$profile->last_name, $profile->maiden_name]);
        $hasMaidenNameColumn = Schema::hasColumn('alumni', 'maiden_name');

        return Alumni::query()->with('user')->where(function ($query) use ($profile, $studentId, $firstNames, $surnames, $user, $search, $hasMaidenNameColumn) {
            $query->whereRaw('LOWER(email) = ?', [GmailAddress::normalize($user->email)]);

            if ($studentId !== '') {
                $query->orWhereIn('student_id', StudentIdFormatter::variants((string) $profile->student_id));

                if (ctype_digit($studentId)) {
                    $query->orWhereRaw("REPLACE(REPLACE(student_id, '-', ''), ' ', '') = ?", [$studentId]);
                }
            }

            foreach ($surnames as $surname) {
                $query->orWhere(function ($nameQuery) use ($firstNames, $surname) {
                    $nameQuery->whereIn(DB::raw('LOWER(TRIM(first_name))'), $firstNames)
                        ->whereRaw('LOWER(TRIM(last_name)) = ?', [Str::lower(trim($surname))]);
                });
            }

            if ($search !== '') {
                $query->orWhere(function ($searchQuery) use ($search, $hasMaidenNameColumn) {
                    foreach (preg_split('/\s+/', $search) as $term) {
                        $searchQuery->where(function ($termQuery) use ($term, $hasMaidenNameColumn) {
                            $termQuery->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");

                            if ($hasMaidenNameColumn) {
                                $termQuery->orWhere('maiden_name', 'like', "%{$term}%");
                            }

                            $termQuery->orWhere('student_id', 'like', "%{$term}%");
                        });
                    }
                });

                $normalizedSearchId = StudentIdFormatter::normalize($search);

                if (ctype_digit($normalizedSearchId)) {
                    $query->orWhereRaw("REPLACE(REPLACE(student_id, '-', ''), ' ', '') = ?", [$normalizedSearchId]);
                }
            }
        })->orderBy('last_name')->orderBy('first_name')->limit(50)->get();
    }

    public function addToRecords(User $user, ?int $recordId): Alumni
    {
        return DB::transaction(function () use ($user, $recordId): Alumni {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $account->isAlumni() || ! $account->isApproved()) {
                throw ValidationException::withMessages(['alumni_record_id' => 'Review and approve this alumni account before adding it to Alumni Records.']);
            }

            if ($account->alumni_id) {
                return $account->alumni;
            }

            if ($recordId) {
                $record = Alumni::query()->lockForUpdate()->findOrFail($recordId);

                if ($record->user()->exists()) {
                    throw ValidationException::withMessages(['alumni_record_id' => 'This school record is already linked to another account.']);
                }
            } else {
                if ($this->matchingRecords($account)->isNotEmpty()) {
                    throw ValidationException::withMessages(['alumni_record_id' => 'A possible school record already exists. Review and select the correct record to avoid a duplicate.']);
                }

                $details = $account->registration_details;

                if (! $details || empty($details['education_level']) || empty($details['course']) || empty($details['year_graduated'])) {
                    throw ValidationException::withMessages(['alumni_record_id' => 'Select an existing school record for this account. Complete registration details are required to create a new record.']);
                }

                if (! Schema::hasColumn('alumni', 'maiden_name')) {
                    unset($details['maiden_name']);
                }

                $details['first_name'] = $account->registration_profile->first_name;
                unset($details['middle_name']);

                $record = new Alumni($details);
                $record->student_id = ($details['student_id'] ?? null) ?: 'TEMP-'.Str::uuid();
            }

            $email = GmailAddress::normalize($account->email);

            if (! GmailAddress::isAllowed($email)) {
                throw ValidationException::withMessages(['email' => GmailAddress::message()]);
            }

            if (Alumni::query()->whereRaw('LOWER(email) = ?', [$email])->when($record->exists, fn ($query) => $query->whereKeyNot($record->id))->exists()) {
                throw ValidationException::withMessages(['email' => 'This Gmail is already associated with another alumni record.']);
            }

            $record->email = $email;
            $record->save();
            $account->forceFill(['alumni_id' => $record->id])->save();

            return $record;
        });
    }
}

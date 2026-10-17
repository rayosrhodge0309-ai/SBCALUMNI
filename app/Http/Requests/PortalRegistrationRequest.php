<?php

namespace App\Http\Requests;

use App\Models\AcademicProgram;
use App\Support\BirthDate;
use App\Support\GmailAddress;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class PortalRegistrationRequest extends FormRequest
{
    public const EMAIL_IN_USE_MESSAGE = 'That Gmail already has an account request. Please sign in or contact the administrator.';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['first_name', 'middle_name', 'last_name', 'maiden_name'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = trim(preg_replace('/[\p{Z}\s]+/u', ' ', $value) ?? $value);
            }
        }

        foreach (['student_id', 'birthday', 'education_level', 'course', 'year_graduated', 'contact_number', 'address'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = trim($value);
            }
        }

        if (is_string($this->input('email'))) {
            $normalized['email'] = GmailAddress::normalize($this->input('email'));
        }

        if (isset($normalized['address'])) {
            $normalized['address'] = str_replace(["\r\n", "\r"], "\n", $normalized['address']);
        }

        foreach ($normalized as $field => $value) {
            $normalized[$field] = $value === '' ? null : $value;
        }

        // Passwords and non-string values are deliberately left unchanged.
        $this->merge($normalized);
    }

    public function rules(): array
    {
        $submittedLevel = $this->input('education_level');
        $levels = AcademicProgram::educationLevels();
        $availablePrograms = is_string($submittedLevel) && in_array($submittedLevel, $levels, true)
            ? AcademicProgram::query()->where('education_level', $submittedLevel)->pluck('name')->all()
            : [];
        $namePattern = "/\A(?=.*\p{L})[\p{L}\p{M} .'\x{2018}\x{2019}-]+\z/u";
        $passwordBytes = function (string $attribute, mixed $value, Closure $fail): void {
            if (strlen($value) > 72) {
                $fail('Passwords must be no longer than 72 bytes; accented letters and symbols can use more than one byte.');
            }

            if (str_contains($value, "\0")) {
                $fail('Passwords cannot contain null characters.');
            }
        };

        return [
            'student_id' => ['bail', 'nullable', 'string', 'max:50', 'regex:/\A(?=.*[A-Za-z0-9])[A-Za-z0-9 -]+\z/'],
            'first_name' => ['bail', 'required', 'string', 'max:100', 'regex:'.$namePattern],
            'middle_name' => ['bail', 'nullable', 'string', 'max:100', 'regex:'.$namePattern],
            'last_name' => ['bail', 'required', 'string', 'max:100', 'regex:'.$namePattern],
            'maiden_name' => ['bail', 'nullable', 'string', 'max:100', 'regex:'.$namePattern],
            'birthday' => ['bail', 'required', 'string', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:'.BirthDate::maximumDate()],
            'education_level' => ['bail', 'required', 'string', Rule::in($levels)],
            'course' => ['bail', 'required', 'string', 'max:150', Rule::in($availablePrograms)],
            'year_graduated' => ['bail', 'required', 'integer', 'min:1920', 'max:'.(now()->year + 1)],
            'email' => ['bail', 'required', 'string', 'max:255', 'email', GmailAddress::validationRule(), Rule::unique('users', 'email')],
            'contact_number' => ['bail', 'nullable', 'string', 'size:11', 'regex:/\A09[0-9]{9}\z/'],
            'address' => ['bail', 'nullable', 'string', 'max:1000'],
            'password' => ['bail', 'required', 'string', $passwordBytes, Password::min(8)->letters()->numbers(), 'confirmed'],
            'password_confirmation' => ['bail', 'required', 'string', $passwordBytes, 'same:password'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['first_name', 'middle_name', 'last_name'])) {
                $fullName = implode(' ', array_filter([
                    $this->input('first_name'),
                    $this->input('middle_name'),
                    $this->input('last_name'),
                ], static fn (?string $part): bool => filled($part)));

                if (mb_strlen($fullName) > 255) {
                    $validator->errors()->add('first_name', 'Your first, middle, and last names combined must not exceed 255 characters.');
                }
            }

            if (! $validator->errors()->hasAny(['birthday', 'year_graduated']) && filled($this->input('birthday'))) {
                if ((int) $this->input('year_graduated') < (int) substr($this->input('birthday'), 0, 4)) {
                    $validator->errors()->add('year_graduated', 'Graduation year cannot be earlier than your birth year.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'student_id.regex' => 'Student ID may contain only letters, numbers, spaces, and hyphens, and must include a letter or number.',
            'first_name.regex' => 'First name must contain a letter and may use only letters, spaces, apostrophes, periods, and hyphens.',
            'middle_name.regex' => 'Middle name must contain a letter and may use only letters, spaces, apostrophes, periods, and hyphens.',
            'last_name.regex' => 'Last name must contain a letter and may use only letters, spaces, apostrophes, periods, and hyphens.',
            'maiden_name.regex' => 'Maiden name must contain a letter and may use only letters, spaces, apostrophes, periods, and hyphens.',
            'birthday.date_format' => 'Enter a valid birthday in YYYY-MM-DD format.',
            'birthday.required' => 'Please enter your birthday.',
            'birthday.before_or_equal' => 'Birthday cannot be in the future or later than December 31, 2026.',
            'education_level.in' => 'Select a valid school level.',
            'course.in' => 'Select a program, grade, or course offered for your school level.',
            'year_graduated.integer' => 'Graduation year must be a whole year.',
            'year_graduated.min' => 'Graduation year must be 1920 or later.',
            'year_graduated.max' => 'Graduation year cannot be later than :max.',
            'email.unique' => self::EMAIL_IN_USE_MESSAGE,
            'contact_number.size' => 'Enter exactly 11 digits starting with 09, such as 09123456789.',
            'contact_number.regex' => 'Enter exactly 11 digits starting with 09, such as 09123456789.',
            'password.confirmed' => 'Password and Confirm Password must match.',
            'password.min' => 'Password must be at least :min characters.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
            'password_confirmation.required' => 'Please confirm your password.',
            'password_confirmation.same' => 'Password and Confirm Password must match.',
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'student ID',
            'education_level' => 'school level',
            'course' => 'program, grade, or course',
            'year_graduated' => 'graduation year',
            'contact_number' => 'contact number',
            'password_confirmation' => 'password confirmation',
        ];
    }
}

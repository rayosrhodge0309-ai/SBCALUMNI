import { initBirthdayCalendar, isCalendarDate, maximumBirthDate } from './birthday-calendar.js';

const nameFields = new Set(['first_name', 'middle_name', 'last_name', 'maiden_name']);
const passwordFields = new Set(['password', 'password_confirmation']);
const requiredFields = new Set(['first_name', 'last_name', 'birthday', 'education_level', 'course', 'year_graduated', 'email', 'password', 'password_confirmation']);
const labels = {
    first_name: 'First name', last_name: 'Last name', birthday: 'Birthday', education_level: 'School level',
    course: 'Program / grade / course', year_graduated: 'Graduation year',
    email: 'Email address', password: 'Password', password_confirmation: 'Confirm password',
};
const limits = { student_id: 50, first_name: 100, middle_name: 100, last_name: 100, maiden_name: 100, email: 255, contact_number: 11, address: 1000 };
const namePattern = /^(?=.*\p{L})[\p{L}\p{M} .'‘’\-]+$/u;
const studentIdPattern = /^(?=.*[A-Za-z0-9])[A-Za-z0-9 \-]+$/;

export function normalizeRegistrationValue(name, value) {
    if (passwordFields.has(name)) return value;
    if (nameFields.has(name)) return value.replace(/[\s\p{Z}]+/gu, ' ').trim();
    const trimmed = value.trim();
    if (name === 'email') return trimmed.toLowerCase();
    if (name === 'address') return trimmed.replace(/\r\n?/g, '\n');
    return trimmed;
}

// The server remains authoritative, including email uniqueness and current program choices.
export function validateRegistrationValue(name, rawValue, values, constraints) {
    const value = normalizeRegistrationValue(name, rawValue);
    if (!value) return requiredFields.has(name) ? `${labels[name]} is required.` : '';
    if (name === 'contact_number') return /^09[0-9]{9}$/.test(value) ? '' : 'Enter exactly 11 digits starting with 09, such as 09123456789.';
    if (limits[name] && [...value].length > limits[name]) return `Use no more than ${limits[name]} characters.`;
    if (nameFields.has(name) && !namePattern.test(value)) return 'Use letters, spaces, apostrophes, hyphens, or periods, with at least one letter.';

    switch (name) {
        case 'student_id':
            if (!studentIdPattern.test(value)) return 'Use letters, numbers, spaces, or hyphens, with at least one letter or number.';
            break;
        case 'first_name': {
            const fullName = ['first_name', 'middle_name', 'last_name'].map((key) => normalizeRegistrationValue(key, values[key] || '')).filter(Boolean).join(' ');
            if ([...fullName].length > 255) return 'Your first, middle, and last names combined must not exceed 255 characters.';
            break;
        }
        case 'birthday':
            if (!isCalendarDate(value)) return 'Enter a valid birthday.';
            if (value > maximumBirthDate(constraints.today)) return 'Birthday cannot be in the future or later than December 31, 2026.';
            break;
        case 'education_level':
            if (!Object.hasOwn(constraints.programs, value)) return 'Select an available school level.';
            break;
        case 'course':
            if (!Object.hasOwn(constraints.programs, values.education_level) || !constraints.programs[values.education_level].includes(value)) return 'Select a program, grade, or course for your school level.';
            break;
        case 'year_graduated':
            if (!/^[0-9]{4}$/.test(value) || Number(value) < 1920 || Number(value) > constraints.maximumYear) return `Enter a whole graduation year from 1920 to ${constraints.maximumYear}.`;
            if (isCalendarDate(values.birthday || '') && Number(value) < Number(values.birthday.slice(0, 4))) return 'Graduation year cannot be before your birth year.';
            break;
        case 'email': {
            const parts = value.split('@');
            if (parts.length !== 2 || parts[1] !== 'gmail.com' || !/^[a-z0-9!#$%&'*+\-/=?^_`{|}~]+(?:\.[a-z0-9!#$%&'*+\-/=?^_`{|}~]+)*$/i.test(parts[0]) || parts[0].length > 64) return 'Enter a valid Gmail address ending in @gmail.com.';
            break;
        }
        case 'password':
        case 'password_confirmation':
            if (value.includes('\0')) return 'Password cannot contain null characters.';
            if (new TextEncoder().encode(value).length > 72) return 'Password must not exceed 72 bytes. Accented letters and symbols can use more than one byte.';
            if (name === 'password_confirmation') {
                if (value !== values.password) return 'Password and Confirm Password must match.';
            } else {
                if ([...value].length < 8) return 'Password must be at least 8 characters.';
                if (!/\p{L}/u.test(value)) return 'Password must include at least one letter.';
                if (!/\p{N}/u.test(value)) return 'Password must include at least one number.';
            }
            break;
    }
    return '';
}

export function initRegistrationForm(form) {
    const fields = [...form.querySelectorAll('[data-registration-field]')];
    const byName = Object.fromEntries(fields.map((field) => [field.name, field]));
    const { education_level: level, course, password, password_confirmation: confirmation } = byName;
    const message = form.querySelector('#password-match-message');
    const groups = [...course.querySelectorAll('optgroup')];
    const constraints = {
        today: form.dataset.today,
        maximumYear: Number(form.dataset.maximumGraduationYear),
        programs: Object.fromEntries(groups.map((group) => [group.dataset.level, [...group.querySelectorAll('option')].map((option) => option.value)])),
    };
    // Include levels without programs so their selection gets a useful course error.
    [...level.options].forEach((option) => {
        if (option.value && !Object.hasOwn(constraints.programs, option.value)) constraints.programs[option.value] = [];
    });
    const touched = new Set();
    const values = () => Object.fromEntries(fields.map((field) => [field.name, normalizeRegistrationValue(field.name, field.value)]));
    const restrictContactDigits = (field) => {
        if (field.name !== 'contact_number') return;
        const original = field.value;
        const digits = original.replace(/[^0-9]/g, '').slice(0, limits.contact_number);
        if (original === digits) return;
        const start = field.selectionStart;
        const end = field.selectionEnd;
        field.value = digits;
        if (start !== null && end !== null && typeof field.setSelectionRange === 'function') {
            field.setSelectionRange(
                Math.min(original.slice(0, start).replace(/[^0-9]/g, '').length, digits.length),
                Math.min(original.slice(0, end).replace(/[^0-9]/g, '').length, digits.length),
            );
        }
    };
    const normalize = (field) => {
        // Assigning an empty value to a partially entered native date/number would
        // erase the browser's badInput state before we can report the error.
        if (!field.validity.badInput) field.value = normalizeRegistrationValue(field.name, field.value);
    };
    const renderError = (field, error) => {
        field.setCustomValidity(error);
        field.classList.toggle('is-invalid', Boolean(error));
        field.setAttribute('aria-invalid', String(Boolean(error)));
        const feedback = form.querySelector(`#${field.name}-error`);
        feedback.textContent = error;
        feedback.hidden = !error;
        feedback.classList.toggle('d-block', Boolean(error));
    };
    const validate = (field) => {
        const error = field.validity.badInput ? 'Enter a valid value.'
            : validateRegistrationValue(field.name, field.value, values(), constraints);
        renderError(field, error);
        return !error;
    };
    const refreshRelatedFields = (field) => {
        const related = [];
        if (['middle_name', 'last_name'].includes(field.name)) related.push(byName.first_name);
        if (field.name === 'birthday') related.push(byName.year_graduated);
        if (field.name === 'password') related.push(confirmation);
        related.forEach((other) => {
            if (touched.has(other.name) || other.classList.contains('is-invalid')) validate(other);
        });
    };
    const updatePasswordMessage = () => {
        const valid = password.value && confirmation.value && password.value === confirmation.value
            && !validateRegistrationValue('password', password.value, values(), constraints);
        message.textContent = valid ? 'Passwords match.' : 'Use at least 8 characters with letters and numbers.';
        message.classList.toggle('text-success', Boolean(valid));
        message.classList.toggle('text-secondary', !valid);
    };
    const updateCourses = (reset = false) => {
        groups.forEach((group) => {
            group.disabled = group.dataset.level !== level.value;
            group.hidden = group.disabled;
        });
        if (reset || !constraints.programs[level.value]?.includes(course.value)) course.value = '';
        course.disabled = !level.value;
        course.options[0].textContent = level.value && !constraints.programs[level.value]?.length
            ? 'No programs available for this level' : 'Select grade, strand, or course';
    };

    fields.forEach((field) => {
        field.addEventListener('blur', () => {
            restrictContactDigits(field);
            normalize(field);
            touched.add(field.name);
            validate(field);
            refreshRelatedFields(field);
        });
        field.addEventListener('input', () => {
            restrictContactDigits(field);
            if (touched.has(field.name) || field.classList.contains('is-invalid')) validate(field);
            refreshRelatedFields(field);
            if (passwordFields.has(field.name)) updatePasswordMessage();
        });
        field.addEventListener('change', () => {
            restrictContactDigits(field);
            if (field === level) {
                updateCourses(true);
                if (touched.has('course') || course.classList.contains('is-invalid')) validate(course);
            }
            normalize(field);
            touched.add(field.name);
            validate(field);
            refreshRelatedFields(field);
        });
    });

    form.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = byName[toggle.dataset.passwordToggle];
        if (!input) return;
        toggle.addEventListener('click', () => {
            const visible = input.type === 'text';
            const label = toggle.dataset.passwordLabel || 'password';
            input.type = visible ? 'password' : 'text';
            toggle.classList.toggle('is-visible', !visible);
            toggle.setAttribute('aria-label', `${visible ? 'Show' : 'Hide'} ${label}`);
            toggle.setAttribute('title', `${visible ? 'Show' : 'Hide'} ${label}`);
            toggle.setAttribute('aria-pressed', String(!visible));
        });
    });
    form.addEventListener('submit', (event) => {
        fields.forEach(normalize);
        let firstInvalid;
        fields.forEach((field) => {
            touched.add(field.name);
            if (!validate(field) && !firstInvalid) firstInvalid = field;
        });
        updatePasswordMessage();
        if (firstInvalid) {
            event.preventDefault();
            // A disabled course control cannot receive focus until a level is selected.
            (firstInvalid.disabled ? level : firstInvalid).focus();
        }
    });
    updateCourses();
    updatePasswordMessage();
    initBirthdayCalendar(form);
    // Leave native constraints active when the enhancement cannot initialize.
    form.noValidate = true;
}

if (typeof document !== 'undefined') {
    const form = document.getElementById('portal-register-form');
    if (form) initRegistrationForm(form);
}

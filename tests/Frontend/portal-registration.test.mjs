import test from 'node:test';
import assert from 'node:assert/strict';
import {
    initRegistrationForm,
    normalizeRegistrationValue,
    validateRegistrationValue,
} from '../../public/js/portal-registration.js';

const constraints = {
    today: '2026-10-15',
    maximumYear: 2027,
    programs: {
        College: ['BS Information Technology', 'BA Communication'],
        'Senior High School': ['STEM'],
        'No Programs': [],
    },
};
const validValues = () => ({
    student_id: '12-3456-789',
    first_name: 'Ana María',
    middle_name: '',
    last_name: 'Dela Cruz',
    maiden_name: '',
    birthday: '2004-05-20',
    education_level: 'College',
    course: 'BS Information Technology',
    year_graduated: '2026',
    email: 'ana.maria@gmail.com',
    contact_number: '09123456789',
    address: '',
    password: 'AlumniPass123',
    password_confirmation: 'AlumniPass123',
});
const validate = (name, value, overrides = {}) => validateRegistrationValue(
    name, value, { ...validValues(), ...overrides, [name]: value }, constraints,
);

test('normalization cleans names and email while preserving student IDs and passwords', () => {
    assert.equal(normalizeRegistrationValue('first_name', '  Ana\u00a0\tMaría  '), 'Ana María');
    assert.equal(normalizeRegistrationValue('email', '  Ana.Maria+alumni@GMAIL.COM  '), 'ana.maria+alumni@gmail.com');
    assert.equal(normalizeRegistrationValue('student_id', '  0012-3456-789  '), '0012-3456-789');
    assert.equal(normalizeRegistrationValue('address', '  Barangay 1\nBatangas City  '), 'Barangay 1\nBatangas City');
    for (const field of ['password', 'password_confirmation']) {
        assert.equal(normalizeRegistrationValue(field, '  A secure password1  '), '  A secure password1  ');
    }
});

test('name validation accepts Unicode names and punctuation and rejects unusable values', () => {
    for (const name of ['María José', 'Jose\u0301', '陳美玲', "O'Connor", 'D’Arcy', 'D‘Arcy', 'Anne-Marie', 'St. John']) {
        assert.equal(validate('first_name', name), '', name);
    }
    for (const name of ['123', 'Ana123', '<b>Ana</b>', '---', '🙂', 'A'.repeat(101)]) {
        assert.notEqual(validate('first_name', name), '', name);
    }
    assert.match(validate('first_name', '   '), /required/);
    assert.equal(validate('middle_name', '   '), '');
    assert.match(validate('first_name', 'A'.repeat(100), {
        middle_name: 'B'.repeat(100), last_name: 'C'.repeat(54),
    }), /combined/);
    assert.equal(validate('first_name', 'A'.repeat(100), {
        middle_name: 'B'.repeat(100), last_name: 'C'.repeat(53),
    }), '');
});

test('mobile validation requires exactly eleven digits without converting country codes', () => {
    for (const value of ['09123456789', '09999999999']) {
        assert.equal(normalizeRegistrationValue('contact_number', value), value);
        assert.equal(validate('contact_number', value), '', value);
    }
    for (const value of ['0912345678', '091234567890', '08123456789', 'phone09123456789', '+1 9123456789', '09\t123456789', '0912 345 6789', '0912-345-6789', '+639123456789', '639123456789']) {
        assert.notEqual(validate('contact_number', value), '', value);
        assert.equal(normalizeRegistrationValue('contact_number', value), value, 'invalid data must not be silently repaired');
    }
    assert.equal(validate('contact_number', ''), '');
});

test('calendar and graduation validation handles older birthdays, leap dates, and future dates', () => {
    assert.equal(validate('birthday', '1950-05-20'), '');
    assert.equal(validate('birthday', '2004-02-29'), '');
    assert.equal(validate('birthday', '2025-12-31'), '');
    assert.equal(validate('birthday', '2026-01-01'), '');
    assert.equal(validate('birthday', '2026-10-14'), '');
    assert.equal(validate('birthday', constraints.today), '');
    assert.match(validate('birthday', ''), /required/);
    for (const value of ['2003-02-29', '2004-02-30', '2004-13-01', '0000-01-01', '2026-10-16', 'May 20, 2004']) {
        assert.notEqual(validate('birthday', value), '', value);
    }
    assert.equal(validate('year_graduated', '1920', { birthday: '' }), '');
    assert.equal(validate('year_graduated', '2027'), '');
    for (const value of ['1919', '2028', '2026.5', '2e3', '02026']) {
        assert.notEqual(validate('year_graduated', value), '', value);
    }
    assert.match(validate('year_graduated', '2003'), /birth year/);
});

test('school level and course must be an available pair', () => {
    assert.equal(validate('education_level', 'College'), '');
    assert.equal(validate('course', 'BS Information Technology'), '');
    assert.equal(validate('course', 'STEM', { education_level: 'Senior High School' }), '');
    assert.notEqual(validate('education_level', 'Unknown'), '');
    assert.notEqual(validate('course', 'STEM'), '');
    assert.notEqual(validate('course', 'BS Information Technology', { education_level: '' }), '');
    assert.notEqual(validate('course', '', { education_level: 'No Programs' }), '');
});

test('Gmail validation accepts plus addressing and uppercase while rejecting malformed mailboxes', () => {
    for (const value of ['ana.maria@gmail.com', 'Ana.Maria+alumni@GMAIL.COM', '  user@gmail.com  ']) {
        assert.equal(validate('email', value), '', value);
    }
    for (const value of ['user@yahoo.com', 'user@gmail.com.evil.example', '.user@gmail.com', 'user.@gmail.com', 'us..er@gmail.com', 'user@@gmail.com', 'user name@gmail.com', `${'a'.repeat(65)}@gmail.com`]) {
        assert.notEqual(validate('email', value), '', value);
    }
});

test('password rules enforce letters, numbers, exact confirmation, and UTF-8 byte limits', () => {
    assert.equal(validate('password', 'AlumniPass123'), '');
    assert.equal(validate('password', 'Éléphant１２'), '');
    assert.equal(validate('password', `${'é'.repeat(35)}a1`), '', '72 bytes is supported');
    assert.match(validate('password', `${'é'.repeat(36)}a1`), /72 bytes/);
    assert.match(validate('password', 'LettersOnly'), /number/);
    assert.match(validate('password', '123456789'), /letter/);
    assert.match(validate('password', 'Short1'), /8 characters/);
    assert.match(validate('password', 'Alumni\0Pass123'), /null/);
    assert.match(validate('password_confirmation', 'AlumniPass123 '), /match/);
    assert.equal(validate('password_confirmation', '  SecretPass1  ', { password: '  SecretPass1  ' }), '');
});

class FakeElement {
    constructor(properties = {}) {
        Object.assign(this, { value: '', disabled: false, hidden: false, textContent: '', dataset: {}, validity: { badInput: false } }, properties);
        this.handlers = new Map();
        this.attributes = new Map();
        this.classes = new Set();
        this.classList = {
            contains: (name) => this.classes.has(name),
            toggle: (name, enabled) => enabled ? this.classes.add(name) : this.classes.delete(name),
        };
    }
    addEventListener(type, handler) {
        const handlers = this.handlers.get(type) || [];
        this.handlers.set(type, [...handlers, handler]);
    }
    dispatch(type, properties = {}) {
        const event = { ...properties, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        for (const handler of this.handlers.get(type) || []) handler(event);
        return event;
    }
    setAttribute(name, value) { this.attributes.set(name, value); }
    setCustomValidity(message) { this.validationMessage = message; }
    focus() { this.focused = true; }
}

function createForm(overrides = {}, serverErrors = {}) {
    const fields = Object.fromEntries(Object.entries({ ...validValues(), ...overrides }).map(([name, value]) => [name, new FakeElement({ name, value })]));
    const feedback = Object.fromEntries(Object.keys(fields).map((name) => [name, new FakeElement({ hidden: true })]));
    const groups = Object.entries(constraints.programs).map(([level, courses]) => {
        const group = new FakeElement({ dataset: { level } });
        group.querySelectorAll = () => courses.map((value) => ({ value }));
        return group;
    });
    fields.education_level.options = [{ value: '' }, ...Object.keys(constraints.programs).map((value) => ({ value }))];
    fields.course.options = [{ value: '' }];
    fields.course.querySelectorAll = () => groups;
    const message = new FakeElement();
    const form = new FakeElement({ dataset: { today: constraints.today, maximumGraduationYear: String(constraints.maximumYear) } });
    form.querySelectorAll = (selector) => selector === '[data-registration-field]' ? Object.values(fields) : [];
    form.querySelector = (selector) => selector === '#password-match-message' ? message : feedback[selector.slice(1, -6)];
    for (const [name, error] of Object.entries(serverErrors)) {
        fields[name].classes.add('is-invalid');
        fields[name].setAttribute('aria-invalid', 'true');
        feedback[name].textContent = error;
        feedback[name].hidden = false;
        feedback[name].classes.add('d-block');
    }
    initRegistrationForm(form);
    return { form, fields, feedback, groups };
}

test('invalid submission focuses the first error and corrected data can submit', () => {
    const { form, fields, feedback } = createForm({ first_name: '', email: 'wrong@example.com' });
    assert.equal(form.noValidate, true);
    assert.equal(form.dispatch('submit').defaultPrevented, true);
    assert.equal(fields.first_name.focused, true);
    assert.equal(fields.first_name.attributes.get('aria-invalid'), 'true');
    assert.equal(feedback.first_name.hidden, false);
    fields.first_name.value = '  María  ';
    fields.first_name.dispatch('input');
    fields.email.value = 'Maria+alumni@GMAIL.COM';
    fields.email.dispatch('input');
    assert.equal(feedback.first_name.hidden, true);
    assert.equal(form.dispatch('submit').defaultPrevented, false);
    assert.equal(fields.first_name.value, 'María');
    assert.equal(fields.email.value, 'maria+alumni@gmail.com');
});

test('contact input removes typed letters and pasted formatting while preserving digits and the cursor', () => {
    const { form, fields } = createForm();
    const contact = fields.contact_number;
    contact.value = '0912abc3456789';
    contact.selectionStart = contact.selectionEnd = 7;
    contact.setSelectionRange = (start, end) => {
        contact.selectionStart = start;
        contact.selectionEnd = end;
    };
    contact.dispatch('input');
    assert.equal(contact.value, '09123456789');
    assert.equal(contact.selectionStart, 4);
    assert.equal(contact.selectionEnd, 4);

    contact.value = 'rfgjjdfbsjkd';
    contact.dispatch('input');
    assert.equal(contact.value, '');

    contact.value = '0912-345-6789';
    contact.dispatch('input');
    assert.equal(contact.value, '09123456789');
    contact.dispatch('blur');
    assert.equal(contact.value, '09123456789');
    assert.equal(form.dispatch('submit').defaultPrevented, false);
});

test('contact input caps typing and pasting at eleven digits and prevents incomplete submissions', () => {
    const { form, fields, feedback } = createForm();
    const contact = fields.contact_number;
    contact.value = '0912345678';
    contact.dispatch('input');
    assert.equal(form.dispatch('submit').defaultPrevented, true);
    assert.match(feedback.contact_number.textContent, /exactly 11 digits/);

    contact.value = '091234567890';
    contact.dispatch('input');
    assert.equal(contact.value, '09123456789');
    assert.equal(form.dispatch('submit').defaultPrevented, false);

    contact.value = '09abc12345678912345';
    contact.dispatch('input');
    assert.equal(contact.value, '09123456789');
    assert.equal(form.dispatch('submit').defaultPrevented, false);
});

test('server errors remain visible on initialization and clear when that field is corrected', () => {
    const error = 'This email address already has an account.';
    const { fields, feedback } = createForm({}, { email: error });
    assert.equal(feedback.email.textContent, error);
    assert.equal(feedback.email.hidden, false);
    fields.email.value = 'new.address@gmail.com';
    fields.email.dispatch('input');
    assert.equal(feedback.email.textContent, '');
    assert.equal(feedback.email.hidden, true);
    assert.equal(fields.email.attributes.get('aria-invalid'), 'false');
});

test('changing school level clears the old course and requires a matching selection', () => {
    const { form, fields, groups } = createForm();
    assert.equal(fields.course.value, 'BS Information Technology');
    fields.education_level.value = 'Senior High School';
    fields.education_level.dispatch('change');
    assert.equal(fields.course.value, '');
    assert.equal(groups[0].disabled, true);
    assert.equal(groups[1].disabled, false);
    assert.equal(form.dispatch('submit').defaultPrevented, true);
    fields.course.value = 'STEM';
    fields.course.dispatch('change');
    assert.equal(form.dispatch('submit').defaultPrevented, false);
    fields.education_level.value = '';
    fields.education_level.dispatch('change');
    assert.equal(fields.course.disabled, true);
});

test('a selected birthday is required before submission', () => {
    const { form, fields, feedback } = createForm({ birthday: '' });
    assert.equal(form.dispatch('submit').defaultPrevented, true);
    assert.equal(fields.birthday.focused, true);
    assert.equal(feedback.birthday.hidden, false);
    fields.birthday.value = '2027-01-01';
    fields.birthday.dispatch('change');
    assert.equal(form.dispatch('submit').defaultPrevented, true);
    fields.birthday.value = '2004-05-20';
    fields.birthday.dispatch('change');
    assert.equal(form.dispatch('submit').defaultPrevented, false);
});


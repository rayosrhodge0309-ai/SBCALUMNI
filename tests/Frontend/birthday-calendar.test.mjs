import test from 'node:test';
import assert from 'node:assert/strict';
import { birthdayMonth, calculateAge, maximumBirthDate } from '../../public/js/birthday-calendar.js';

test('age changes on the birthday without changing the stored birth date', () => {
    assert.equal(calculateAge('1950-10-17', '2026-10-16'), 75);
    assert.equal(calculateAge('1950-10-17', '2026-10-17'), 76);
    assert.equal(calculateAge('1950-10-17', '2027-10-17'), 77);
    assert.equal(calculateAge('2000-02-29', '2025-02-28'), 24);
    assert.equal(calculateAge('2000-02-29', '2025-03-01'), 25);
    assert.equal(calculateAge('2026-10-17', '2026-10-17'), 0);
    assert.equal(calculateAge('2026-10-18', '2026-10-17'), null);
    assert.equal(calculateAge('2001-02-29', '2026-10-17'), null);
    assert.equal(calculateAge('', '2026-10-17'), null);
});

test('calendar builds older months directly and handles leap years', () => {
    const may1950 = birthdayMonth(1950, 5, '2026-10-17').filter(Boolean);
    assert.equal(may1950.length, 31);
    assert.equal(may1950[19].value, '1950-05-20');
    assert.equal(may1950[19].disabled, false);
    assert.equal(birthdayMonth(2000, 2, '2026-10-17').filter(Boolean).length, 29);
    assert.equal(birthdayMonth(1900, 2, '2026-10-17').filter(Boolean).length, 28);
});

test('calendar disables future days and retains the explicit 2026 upper limit', () => {
    const october = birthdayMonth(2026, 10, maximumBirthDate('2026-10-17')).filter(Boolean);
    assert.equal(october[16].disabled, false);
    assert.equal(october[17].disabled, true);
    assert.equal(maximumBirthDate('2028-10-17'), '2026-12-31');
    assert.equal(maximumBirthDate('2026-10-17'), '2026-10-17');
    assert.equal(birthdayMonth(2027, 1, maximumBirthDate('2028-10-17')).filter(Boolean).every((day) => day.disabled), true);
});

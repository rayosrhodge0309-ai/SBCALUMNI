const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const isoDate = (year, month, day) => `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

export function isCalendarDate(value) {
    if (!/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value) || value.startsWith('0000')) return false;
    const date = new Date(`${value}T00:00:00Z`);
    return !Number.isNaN(date.getTime()) && date.toISOString().slice(0, 10) === value;
}

export function maximumBirthDate(today) {
    return today < '2026-12-31' ? today : '2026-12-31';
}

export function calculateAge(birthday, today) {
    if (!isCalendarDate(birthday) || !isCalendarDate(today) || birthday > today) return null;
    return Number(today.slice(0, 4)) - Number(birthday.slice(0, 4))
        - (today.slice(5) < birthday.slice(5) ? 1 : 0);
}

export function birthdayMonth(year, month, maximumDate) {
    const first = isoDate(year, month, 1);
    if (!isCalendarDate(first)) return [];
    const firstWeekday = new Date(`${first}T00:00:00Z`).getUTCDay();
    const cells = Array(firstWeekday).fill(null);
    for (let day = 1; day <= 31; day++) {
        const value = isoDate(year, month, day);
        if (!isCalendarDate(value)) break;
        cells.push({ day, value, disabled: value > maximumDate });
    }
    return cells;
}

export function initBirthdayCalendar(form) {
    const calendar = form.querySelector('[data-birthday-calendar]');
    if (!calendar) return;
    const input = form.querySelector('#birthday');
    const ageOutput = form.querySelector('#birthday-age');
    const month = calendar.querySelector('[data-calendar-month]');
    const year = calendar.querySelector('[data-calendar-year]');
    const days = calendar.querySelector('[data-calendar-days]');
    const title = calendar.querySelector('[data-calendar-title]');
    const doc = form.ownerDocument;
    const maximumDate = input.dataset.maximumDate || maximumBirthDate(form.dataset.today);
    const maximumYear = Number(maximumDate.slice(0, 4));
    let earliestYear = 1900;

    const option = (value, text) => {
        const element = doc.createElement('option');
        element.value = String(value);
        element.textContent = text;
        return element;
    };
    months.forEach((name, index) => month.append(option(index + 1, name)));

    const fillYears = (selectedYear) => {
        earliestYear = Math.min(earliestYear, selectedYear);
        year.replaceChildren();
        for (let value = maximumYear; value >= earliestYear; value--) {
            year.append(option(value, String(value)));
        }
        if (earliestYear > 1) year.append(option('earlier', `Before ${earliestYear}…`));
        year.value = String(selectedYear);
    };
    const close = () => {
        calendar.open = false;
        input.focus();
    };
    const updateAge = () => {
        const age = input.value <= maximumDate ? calculateAge(input.value, form.dataset.today) : null;
        ageOutput.textContent = age === null ? 'Age will appear after you select a valid birthday.'
            : `Age: ${age} ${age === 1 ? 'year' : 'years'} old`;
    };
    const render = () => {
        const selectedYear = Number(year.value);
        [...month.options].forEach((item) => {
            item.disabled = isoDate(selectedYear, Number(item.value), 1) > maximumDate;
        });
        if (month.selectedOptions[0].disabled) month.value = maximumDate.slice(5, 7).replace(/^0/, '');
        const selectedMonth = Number(month.value);
        title.textContent = `${months[selectedMonth - 1]} ${selectedYear}`;
        days.setAttribute('aria-label', `Choose a day in ${title.textContent}`);
        days.replaceChildren();
        birthdayMonth(selectedYear, selectedMonth, maximumDate).forEach((cell) => {
            if (cell === null) {
                const blank = doc.createElement('span');
                blank.setAttribute('aria-hidden', 'true');
                days.append(blank);
                return;
            }
            const button = doc.createElement('button');
            const selected = cell.value === input.value;
            button.type = 'button';
            button.textContent = String(cell.day);
            button.dataset.date = cell.value;
            button.disabled = cell.disabled;
            button.className = `btn btn-sm ${selected ? 'btn-success' : 'btn-outline-secondary'}`;
            button.setAttribute('aria-label', `${months[selectedMonth - 1]} ${cell.day}, ${selectedYear}`);
            button.setAttribute('aria-pressed', String(selected));
            button.addEventListener('click', () => {
                input.value = cell.value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                close();
            });
            button.addEventListener('keydown', (event) => {
                const offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
                const buttons = [...days.querySelectorAll('button:not(:disabled)')];
                const index = buttons.indexOf(button);
                const target = event.key === 'Home' ? buttons[0] : event.key === 'End' ? buttons.at(-1)
                    : buttons[index + offsets[event.key]];
                if (target) {
                    event.preventDefault();
                    target.focus();
                }
            });
            days.append(button);
        });
    };
    const sync = () => {
        const selected = isCalendarDate(input.value) && input.value <= maximumDate
            ? input.value : isoDate(Math.min(2000, maximumYear), Number(form.dataset.today.slice(5, 7)), 1);
        fillYears(Number(selected.slice(0, 4)));
        month.value = String(Number(selected.slice(5, 7)));
        render();
    };

    year.addEventListener('change', () => {
        if (year.value === 'earlier') {
            earliestYear = Math.max(1, earliestYear - 100);
            fillYears(earliestYear);
        }
        render();
    });
    month.addEventListener('change', render);
    calendar.addEventListener('toggle', () => {
        input.setAttribute('aria-expanded', String(calendar.open));
        if (calendar.open) sync();
    });
    calendar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });
    calendar.querySelector('[data-calendar-close]').addEventListener('click', close);
    input.addEventListener('click', () => { calendar.open = true; });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            calendar.open = true;
            month.focus();
        }
    });
    input.addEventListener('input', updateAge);
    input.addEventListener('change', () => {
        updateAge();
        if (calendar.open) sync();
    });
    sync();
    updateAge();
    calendar.hidden = false;
}

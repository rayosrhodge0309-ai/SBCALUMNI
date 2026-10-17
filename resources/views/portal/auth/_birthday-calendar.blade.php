<details id="birthday-calendar" class="birthday-calendar" data-birthday-calendar hidden>
    <summary>Choose month and year</summary>
    <div class="border rounded p-3 bg-white">
        <div class="row g-2 mb-3">
            <div class="col-7">
                <label for="birthday-month" class="form-label small">Month</label>
                <select id="birthday-month" class="form-select form-select-sm" data-calendar-month></select>
            </div>
            <div class="col-5">
                <label for="birthday-year" class="form-label small">Year</label>
                <select id="birthday-year" class="form-select form-select-sm" data-calendar-year></select>
            </div>
        </div>
        <div class="small fw-semibold mb-2" data-calendar-title aria-live="polite"></div>
        <div class="birthday-calendar-grid small text-secondary mb-1" aria-hidden="true">
            @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $weekday)
                <span>{{ $weekday }}</span>
            @endforeach
        </div>
        <div class="birthday-calendar-grid" data-calendar-days role="group" aria-label="Choose a day"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-3" data-calendar-close>Close calendar</button>
    </div>
</details>

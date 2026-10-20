(() => {
    const mobileClocks = document.querySelectorAll('[data-mobile-clock]');
    const calendars = document.querySelectorAll('[data-calendar]');
    calendars.forEach((calendar) => {
        const monthLabel = calendar.querySelector('[data-calendar-month]');
        const daysGrid = calendar.querySelector('[data-calendar-days]');
        const selectedLabel = calendar.querySelector('[data-calendar-selected]');
        const clock = calendar.querySelector('[data-live-clock]');
        const todayButton = calendar.querySelector('[data-calendar-today]');
        let todayDate = calendar.dataset.today;
        const serverNow = Number(calendar.dataset.now);
        const startedAt = Date.now();
        const monthFormatter = new Intl.DateTimeFormat('id-ID', {
            month: 'long',
            year: 'numeric',
            timeZone: 'Asia/Jakarta'
        });
        const dateFormatter = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC'
        });
        const jakartaDateFormatter = new Intl.DateTimeFormat('en-CA', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: 'Asia/Jakarta'
        });
        const clockFormatter = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
            timeZone: 'Asia/Jakarta'
        });
        let [todayYear, todayMonth, todayDay] = todayDate.split('-').map(Number);
        let displayedMonth = new Date(todayYear, todayMonth - 1, 1);
        let selectedDate = new Date(todayYear, todayMonth - 1, todayDay);

        const toDateKey = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const formatDateKey = (dateKey) => {
            const [year, month, day] = dateKey.split('-').map(Number);
            return dateFormatter.format(new Date(Date.UTC(year, month - 1, day, 12)));
        };

        const renderCalendar = () => {
            monthLabel.textContent = monthFormatter.format(displayedMonth);
            daysGrid.replaceChildren();

            const year = displayedMonth.getFullYear();
            const month = displayedMonth.getMonth();
            const firstDayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const totalCells = Math.ceil((firstDayOffset + daysInMonth) / 7) * 7;

            for (let index = 0; index < totalCells; index += 1) {
                const dayNumber = index - firstDayOffset + 1;
                if (dayNumber < 1 || dayNumber > daysInMonth) {
                    const emptyCell = document.createElement('span');
                    emptyCell.className = 'calendar-empty';
                    emptyCell.setAttribute('role', 'gridcell');
                    emptyCell.setAttribute('aria-hidden', 'true');
                    daysGrid.append(emptyCell);
                    continue;
                }

                const date = new Date(year, month, dayNumber);
                const dateKey = toDateKey(date);
                const dayButton = document.createElement('button');
                dayButton.type = 'button';
                dayButton.className = 'calendar-day';
                dayButton.textContent = String(dayNumber);
                dayButton.setAttribute('role', 'gridcell');
                dayButton.setAttribute('aria-label', formatDateKey(dateKey));

                if (dateKey === todayDate) {
                    dayButton.classList.add('is-today');
                    dayButton.setAttribute('aria-current', 'date');
                }
                if (dateKey === toDateKey(selectedDate)) {
                    dayButton.classList.add('is-selected');
                    dayButton.setAttribute('aria-pressed', 'true');
                } else {
                    dayButton.setAttribute('aria-pressed', 'false');
                }

                dayButton.addEventListener('click', () => {
                    selectedDate = date;
                    selectedLabel.textContent = dateKey === todayDate
                        ? `Hari ini: ${formatDateKey(dateKey)}`
                        : `Dipilih: ${formatDateKey(dateKey)}`;
                    renderCalendar();
                });
                daysGrid.append(dayButton);
            }
        };

        const updateClock = () => {
            const now = new Date(serverNow + Date.now() - startedAt);
            clock.textContent = clockFormatter.format(now);
            clock.dateTime = now.toISOString();
            mobileClocks.forEach((mobileClock) => {
                mobileClock.textContent = clockFormatter.format(now);
                mobileClock.dateTime = now.toISOString();
            });

            const parts = Object.fromEntries(
                jakartaDateFormatter.formatToParts(now).map(({ type, value }) => [type, value])
            );
            const nextTodayDate = `${parts.year}-${parts.month}-${parts.day}`;
            if (nextTodayDate !== todayDate) {
                const previousTodayDate = todayDate;
                const wasCurrentMonth = displayedMonth.getFullYear() === todayYear
                    && displayedMonth.getMonth() === todayMonth - 1;
                todayDate = nextTodayDate;
                [todayYear, todayMonth, todayDay] = todayDate.split('-').map(Number);
                if (wasCurrentMonth) {
                    displayedMonth = new Date(todayYear, todayMonth - 1, 1);
                }
                if (toDateKey(selectedDate) === previousTodayDate) {
                    selectedDate = new Date(todayYear, todayMonth - 1, todayDay);
                    selectedLabel.textContent = `Hari ini: ${formatDateKey(todayDate)}`;
                }
                renderCalendar();
            }
        };

        calendar.querySelector('[data-calendar-prev]').addEventListener('click', () => {
            displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() - 1, 1);
            renderCalendar();
        });
        calendar.querySelector('[data-calendar-next]').addEventListener('click', () => {
            displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
            renderCalendar();
        });
        todayButton.addEventListener('click', () => {
            displayedMonth = new Date(todayYear, todayMonth - 1, 1);
            selectedDate = new Date(todayYear, todayMonth - 1, todayDay);
            selectedLabel.textContent = `Hari ini: ${formatDateKey(todayDate)}`;
            renderCalendar();
        });

        selectedLabel.textContent = `Hari ini: ${formatDateKey(todayDate)}`;
        renderCalendar();
        updateClock();
        window.setInterval(updateClock, 1000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                updateClock();
            }
        });
    });

    const overlay = document.querySelector('[data-page-transition]');
    if (!overlay) {
        return;
    }

    const label = overlay.querySelector('[data-transition-label]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let isNavigating = false;

    const showTransition = (message) => {
        if (isNavigating) {
            return;
        }

        isNavigating = true;
        label.textContent = message;
        overlay.classList.add('is-active');
        overlay.setAttribute('aria-hidden', 'false');
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (
            !link ||
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            link.hasAttribute('download') ||
            (link.target && link.target !== '_self')
        ) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        if (
            destination.origin !== window.location.origin ||
            destination.href === window.location.href ||
            destination.hash && destination.pathname === window.location.pathname && destination.search === window.location.search
        ) {
            return;
        }

        if (reducedMotion) {
            return;
        }

        event.preventDefault();
        showTransition('Membuka halaman...');
        window.setTimeout(() => window.location.assign(destination.href), 140);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (
            !(form instanceof HTMLFormElement) ||
            event.defaultPrevented ||
            form.method.toLowerCase() !== 'post' ||
            (form.target && form.target !== '_self')
        ) {
            return;
        }

        const message = form.dataset.transitionMessage || 'Memproses...';
        showTransition(message);
    });
})();

import Calendar from '@toast-ui/calendar';

/**
 * TOAST UI Calendar için wrapper sınıfı.
 * Alpine.js x-data içinde kullanılacak.
 */
export default class CalendarManager {
    constructor(element, options = {}) {
        this.element = element;
        this.instance = null;
        this.options = options;

        // Callback'ler
        this.onUpdate = null;
        this.onSelect = null;
        this.onClick = null;
    }

    init() {
        if (this.instance) return this.instance;

        const config = {
            defaultView: 'week',
            useCreationPopup: false,
            useDetailPopup: false,
            usageStatistics: false,
            isReadOnly: false,
            week: {
                startDayOfWeek: 1, // Pazartesi
                taskView: false,
                eventView: true, // Keep true for proper height, hide allday via CSS
                hourStart: 6,
                hourEnd: 22,
            },
            month: {
                startDayOfWeek: 1,
            },
            ...this.options,
        };

        this.instance = new Calendar(this.element, config);
        this.instance.render(); // CRITICAL: Force render after creation
        this.attachEvents();

        return this.instance;
    }

    attachEvents() {
        this.instance.on('beforeUpdateEvent', (e) => {
            if (this.onUpdate) this.onUpdate(e);
        });

        this.instance.on('selectDateTime', (e) => {
            if (this.onSelect) this.onSelect(e);
        });

        this.instance.on('clickEvent', (e) => {
            if (this.onClick) this.onClick(e);
        });
    }

    updateEvents(events) {
        if (!this.instance) return;
        this.instance.clear();
        this.instance.createEvents(events);
    }

    next() {
        this.instance?.next();
    }

    prev() {
        this.instance?.prev();
    }

    today() {
        this.instance?.today();
    }

    changeView(view) {
        this.instance?.changeView(view);
    }

    getDateRange() {
        if (!this.instance) return { start: new Date(), end: new Date() };
        return {
            start: this.instance.getDateRangeStart().toDate(),
            end: this.instance.getDateRangeEnd().toDate(),
        };
    }

    destroy() {
        if (this.instance) this.instance.destroy();
    }
}

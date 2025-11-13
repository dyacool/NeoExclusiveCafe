/**
 * Date Calendar Component for Today's Products
 * Allows selection of multiple dates for product availability
 */

class DateCalendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.selectedDates = new Set();
        this.currentDate = new Date();
        this.currentMonth = this.currentDate.getMonth();
        this.currentYear = this.currentDate.getFullYear();
        
        // Options
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Set to start of day for proper comparison
        
        this.options = {
            minDate: today, // Default to today (start of day)
            maxDate: new Date(new Date().setMonth(new Date().getMonth() + 6)), // 6 months ahead
            dateFormat: 'YYYY-MM-DD',
            allowPastDates: false,
            maxSelections: null, // null = unlimited
            disableToday: false, // Disable selection of current day
            ...options
        };
        
        // Register this instance globally for onclick access
        if (!window.calendarInstances) {
            window.calendarInstances = {};

        }
        window.calendarInstances[containerId] = this;

        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Calendar container not found');
            return;
        }
        
        this.render();
        this.bindEvents();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="date-calendar">
                <div class="calendar-header">
                    <button type="button" class="calendar-nav-btn" id="prevMonth_${this.container.id}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                    </button>
                    <div class="calendar-title">
                        <span class="month-year">${this.getMonthName(this.currentMonth)} ${this.currentYear}</span>
                    </div>
                    <button type="button" class="calendar-nav-btn" id="nextMonth_${this.container.id}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </button>
                </div>
                <div class="calendar-weekdays">
                    <div class="weekday">Sun</div>
                    <div class="weekday">Mon</div>
                    <div class="weekday">Tue</div>
                    <div class="weekday">Wed</div>
                    <div class="weekday">Thu</div>
                    <div class="weekday">Fri</div>
                    <div class="weekday">Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays">
                    ${this.generateCalendarDays()}
                </div>
                <div class="selected-dates-summary">
                    <span class="selected-count">${this.selectedDates.size} date(s) selected</span>
                    <button type="button" class="clear-dates-btn" onclick="window.calendarInstances['${this.container.id}'].clearAllDates()">Clear All</button>
                </div>
            </div>
        `;
        
        this.addCalendarStyles();
    }
    
    generateCalendarDays() {
        const firstDay = new Date(this.currentYear, this.currentMonth, 1);
        const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        let daysHtml = '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < 42; i++) { // 6 weeks * 7 days
            const currentDay = new Date(startDate);
            currentDay.setDate(startDate.getDate() + i);
            
            const isCurrentMonth = currentDay.getMonth() === this.currentMonth;
            const isToday = currentDay.getTime() === today.getTime();
            const isPast = currentDay < today && !this.options.allowPastDates;
            const isSelected = this.selectedDates.has(this.formatDate(currentDay));
            const isTodayDisabled = isToday && this.options.disableToday;
            
            // Ensure proper date-only comparison
            const currentDayOnly = new Date(currentDay);
            currentDayOnly.setHours(0, 0, 0, 0);
            
            const isDisabled = isPast || isTodayDisabled || 
                             (this.options.minDate && currentDayOnly < this.options.minDate) || 
                             (this.options.maxDate && currentDayOnly > this.options.maxDate);
            
            // Debug logging for today's date
            if (isToday) {
                console.log('Today debug:', {
                    currentDay: currentDay.toDateString(),
                    currentDayOnly: currentDayOnly.toDateString(),
                    minDate: this.options.minDate ? this.options.minDate.toDateString() : 'none',
                    isPast,
                    isTodayDisabled,
                    isDisabled,
                    'currentDayOnly < minDate': this.options.minDate && currentDayOnly < this.options.minDate
                });
            }
            
            let classes = 'calendar-day';
            if (!isCurrentMonth) classes += ' other-month';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            if (isDisabled) classes += ' disabled';
            
            // Get quantity for this date if it exists
            const dateStr = this.formatDate(currentDay);
            const quantity = (window.sdoQuantities && window.sdoQuantities[dateStr]) || '';
            
            daysHtml += `
                <div class="${classes}" 
                     data-date="${dateStr}"
                     data-quantity="${quantity ? 'Qty: ' + quantity : ''}"
                     ${!isDisabled ? `onclick="window.calendarInstances['${this.container.id}'].toggleDate(this)"` : ''}>
                    ${currentDay.getDate()}
                </div>
            `;
        }
        
        return daysHtml;
    }
    
    bindEvents() {
        console.log(`[${this.container.id}] Binding events...`);
        const prevBtn = this.container.querySelector(`#prevMonth_${this.container.id}`);
        const nextBtn = this.container.querySelector(`#nextMonth_${this.container.id}`);
        
        console.log(`[${this.container.id}] Prev button found:`, !!prevBtn);
        console.log(`[${this.container.id}] Next button found:`, !!nextBtn);
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                console.log(`[${this.container.id}] Previous month clicked`);
                this.previousMonth();
            });
            console.log(`[${this.container.id}] Previous button event listener added`);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                console.log(`[${this.container.id}] Next month clicked`);
                this.nextMonth();
            });
            console.log(`[${this.container.id}] Next button event listener added`);
        }
    }
    
    toggleDate(dayElement) {
        const date = dayElement.getAttribute('data-date');
        
        if (dayElement.classList.contains('disabled')) {
            return;
        }
        
        // Check if we should open quantity modal (for SDO products)
        if (this.options.enableQuantityModal) {
            this.openQuantityModal(date, dayElement);
            return;
        }
        
        if (this.selectedDates.has(date)) {
            this.selectedDates.delete(date);
            dayElement.classList.remove('selected');
        } else {
            // Check max selections limit
            if (this.options.maxSelections && this.selectedDates.size >= this.options.maxSelections) {
                alert(`You can only select up to ${this.options.maxSelections} dates.`);
                return;
            }
            
            this.selectedDates.add(date);
            dayElement.classList.add('selected');
        }
        
        this.updateSelectedSummary();
        this.onDateSelectionChange();
    }
    
    openQuantityModal(date, dayElement) {
        const dateObj = new Date(date + 'T00:00:00');
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric' 
        });
        
        // Get current quantity for this date
        const currentQuantity = (window.sdoQuantities && window.sdoQuantities[date]) || 0;
        const isSelected = this.selectedDates.has(date);
        
        // Create modal HTML
        const modalHtml = `
            <div class="sdo-quantity-modal-overlay" id="sdoQuantityModalOverlay">
                <div class="sdo-quantity-modal">
                    <div class="sdo-quantity-modal-header">
                        <h3>Set Quantity for ${formattedDate}</h3>
                        <button type="button" class="sdo-modal-close" onclick="window.calendarInstances['${this.container.id}'].closeQuantityModal()">&times;</button>
                    </div>
                    <div class="sdo-quantity-modal-body">
                        <label for="sdoModalQuantityInput">Quantity Available:</label>
                        <input 
                            type="number" 
                            id="sdoModalQuantityInput" 
                            value="${currentQuantity}" 
                            min="0" 
                            step="1"
                            placeholder="Enter quantity"
                            autofocus
                        />
                        <p class="sdo-modal-hint">Set quantity to 0 to remove this date</p>
                    </div>
                    <div class="sdo-quantity-modal-footer">
                        <button type="button" class="btn-modal-cancel" onclick="window.calendarInstances['${this.container.id}'].closeQuantityModal()">Cancel</button>
                        <button type="button" class="btn-modal-save" onclick="window.calendarInstances['${this.container.id}'].saveQuantityFromModal('${date}')">Save</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer.firstElementChild);
        
        // Focus on input
        setTimeout(() => {
            const input = document.getElementById('sdoModalQuantityInput');
            if (input) {
                input.focus();
                input.select();
            }
        }, 100);
        
        // Handle Enter key
        document.getElementById('sdoModalQuantityInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.saveQuantityFromModal(date);
            }
        });
    }
    
    saveQuantityFromModal(date) {
        const input = document.getElementById('sdoModalQuantityInput');
        const quantity = parseInt(input.value) || 0;
        
        console.log('Saving quantity for date:', date, 'quantity:', quantity);
        
        // Initialize sdoQuantities if not exists
        if (!window.sdoQuantities) {
            window.sdoQuantities = {};
            console.log('Initialized window.sdoQuantities');
        }
        
        if (quantity > 0) {
            // Add/update date with quantity
            window.sdoQuantities[date] = quantity;
            console.log('Added/updated quantity:', date, '=', quantity);
            if (!this.selectedDates.has(date)) {
                this.selectedDates.add(date);
            }
        } else {
            // Remove date if quantity is 0
            delete window.sdoQuantities[date];
            this.selectedDates.delete(date);
            console.log('Removed date:', date);
        }
        
        console.log('Current sdoQuantities:', window.sdoQuantities);
        
        // Update calendar display
        this.render();
        this.bindEvents();
        this.updateSelectedSummary();
        this.onDateSelectionChange();
        
        // Close modal
        this.closeQuantityModal();
    }
    
    closeQuantityModal() {
        const modal = document.getElementById('sdoQuantityModalOverlay');
        if (modal) {
            modal.remove();
        }
    }
    
    previousMonth() {
        console.log(`[${this.container.id}] Previous month: ${this.currentMonth}/${this.currentYear}`);
        this.currentMonth--;
        if (this.currentMonth < 0) {
            this.currentMonth = 11;
            this.currentYear--;
        }
        console.log(`[${this.container.id}] After previous: ${this.currentMonth}/${this.currentYear}`);
        this.render();
        this.bindEvents(); // Re-bind events after render
    }
    
    nextMonth() {
        console.log(`[${this.container.id}] Next month: ${this.currentMonth}/${this.currentYear}`);
        this.currentMonth++;
        if (this.currentMonth > 11) {
            this.currentMonth = 0;
            this.currentYear++;
        }
        console.log(`[${this.container.id}] After next: ${this.currentMonth}/${this.currentYear}`);
        this.render();
        this.bindEvents(); // Re-bind events after render
    }
    
    clearAllDates() {
        this.selectedDates.clear();
        this.render();
        this.onDateSelectionChange();
    }
    
    getSelectedDates() {
        return Array.from(this.selectedDates).sort();
    }
    
    setSelectedDates(dates) {
        this.selectedDates = new Set(dates);
        this.render();
        this.onDateSelectionChange();
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    getMonthName(monthIndex) {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[monthIndex];
    }
    
    updateSelectedSummary() {
        const summaryElement = this.container.querySelector('.selected-count');
        if (summaryElement) {
            summaryElement.textContent = `${this.selectedDates.size} date(s) selected`;
        }
    }
    
    onDateSelectionChange() {
        // Override this method or set a callback
        if (this.options.onSelectionChange) {
            this.options.onSelectionChange(this.getSelectedDates());
        }
        
        // Dispatch custom event
        const event = new CustomEvent('dateSelectionChange', {
            detail: { selectedDates: this.getSelectedDates() }
        });
        this.container.dispatchEvent(event);
    }
    
    addCalendarStyles() {
        // Add styles if not already added
        if (document.querySelector('#dateCalendarStyles')) return;
        
        const style = document.createElement('style');
        style.id = 'dateCalendarStyles';
        style.textContent = `
            .date-calendar {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 16px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 320px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .calendar-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            
            .calendar-nav-btn {
                background: none;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
            }
            
            .calendar-nav-btn:hover {
                background: #f5f5f5;
                border-color: #999;
            }
            
            .calendar-title {
                font-weight: 600;
                font-size: 16px;
                color: #333;
            }
            
            .calendar-weekdays {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
                margin-bottom: 8px;
            }
            
            .weekday {
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                color: #666;
                padding: 8px 4px;
            }
            
            .calendar-days {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
                margin-bottom: 16px;
            }
            
            .calendar-day {
                aspect-ratio: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s;
                position: relative;
            }
            
            .calendar-day:hover:not(.disabled) {
                background: #e3f2fd;
            }
            
            .calendar-day.other-month {
                color: #ccc;
            }
            
            .calendar-day.today {
                background: #2196f3;
                color: white;
                font-weight: 600;
            }
            
            .calendar-day.today.disabled {
                background: #f5f5f5;
                color: #ccc;
                border: 1px dashed #ddd;
                font-weight: 400;
                position: relative;
            }
            
            .calendar-day.today.disabled::after {
                content: '✕';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 12px;
                color: #999;
            }
            
            .calendar-day.selected {
                background: #4caf50 !important;
                color: white;
                font-weight: 600;
                position: relative;
            }
            
            .calendar-day.selected::after {
                content: attr(data-quantity);
                position: absolute;
                bottom: 2px;
                right: 2px;
                font-size: 9px;
                background: rgba(0, 0, 0, 0.3);
                padding: 1px 3px;
                border-radius: 2px;
            }
            
            .calendar-day.disabled {
                color: #ccc;
                cursor: not-allowed;
                background: #f9f9f9;
            }
            
            .selected-dates-summary {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 12px;
                border-top: 1px solid #eee;
                font-size: 14px;
            }
            
            .selected-count {
                color: #666;
            }
            
            .clear-dates-btn {
                background: none;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 4px 8px;
                cursor: pointer;
                font-size: 12px;
                color: #666;
                transition: all 0.2s;
            }
            
            .clear-dates-btn:hover {
                background: #f5f5f5;
                color: #333;
            }
            
            /* SDO Quantity Modal Styles */
            .sdo-quantity-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .sdo-quantity-modal {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                width: 90%;
                max-width: 400px;
                animation: slideUp 0.3s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            .sdo-quantity-modal-header {
                padding: 20px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .sdo-quantity-modal-header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: #111827;
            }
            
            .sdo-modal-close {
                background: none;
                border: none;
                font-size: 28px;
                color: #6b7280;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s;
            }
            
            .sdo-modal-close:hover {
                background: #f3f4f6;
                color: #111827;
            }
            
            .sdo-quantity-modal-body {
                padding: 24px 20px;
            }
            
            .sdo-quantity-modal-body label {
                display: block;
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                margin-bottom: 8px;
            }
            
            .sdo-quantity-modal-body input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 16px;
                transition: all 0.2s;
                box-sizing: border-box;
            }
            
            .sdo-quantity-modal-body input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            .sdo-modal-hint {
                margin-top: 8px;
                font-size: 12px;
                color: #6b7280;
            }
            
            .sdo-quantity-modal-footer {
                padding: 16px 20px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                justify-content: flex-end;
                gap: 12px;
            }
            
            .btn-modal-cancel,
            .btn-modal-save {
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                border: none;
            }
            
            .btn-modal-cancel {
                background: #f3f4f6;
                color: #374151;
            }
            
            .btn-modal-cancel:hover {
                background: #e5e7eb;
            }
            
            .btn-modal-save {
                background: #3b82f6;
                color: white;
            }
            
            .btn-modal-save:hover {
                background: #2563eb;
            }
        `;
        document.head.appendChild(style);
    }
}

// Global calendar instance for easy access
let calendar = null;

// Initialize calendar function
function initializeDateCalendar(containerId, options = {}) {
    calendar = new DateCalendar(containerId, options);
    return calendar;
}

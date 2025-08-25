/**
 * Modal Calendar Handler for Product List Edit Modal
 * Handles calendar initialization and visibility for Today's products
 */

// Global calendar instances
let todaysProductEditCalendar = null;
let availableTodayEditCalendar = null;

/**
 * Initialize calendars for the edit modal
 */
function initializeEditModalCalendars() {
    console.log('=== initializeEditModalCalendars DEBUG ===');
    console.log('todaysProductEditCalendar exists:', !!todaysProductEditCalendar);
    console.log('availableTodayEditCalendar exists:', !!availableTodayEditCalendar);
    
    const todaysContainer = document.getElementById('todaysProductCalendar');
    const availableTodayContainer = document.getElementById('availableTodayCalendar');
    
    console.log('todaysProductCalendar container exists:', !!todaysContainer);
    console.log('availableTodayCalendar container exists:', !!availableTodayContainer);
    
    // Only initialize if calendars don't already exist
    if (!todaysProductEditCalendar && todaysContainer) {
        console.log('Creating todaysProductEditCalendar...');
        try {
            todaysProductEditCalendar = new DateCalendar('todaysProductCalendar', {
                onSelectionChange: function(selectedDates) {
                    console.log('Today\'s product calendar selection changed:', selectedDates);
                    const hiddenInput = document.getElementById('todaysProductDates');
                    if (hiddenInput) {
                        hiddenInput.value = selectedDates.join(',');
                        console.log('Updated hidden input:', hiddenInput.value);
                    }
                },
                maxSelections: null // Allow unlimited selections
            });
            console.log('todaysProductEditCalendar created successfully:', todaysProductEditCalendar);
        } catch (error) {
            console.error('Error creating todaysProductEditCalendar:', error);
        }
    } else {
        console.log('Skipping todaysProductEditCalendar creation');
    }
    
    // Only initialize if calendars don't already exist
    if (!availableTodayEditCalendar && availableTodayContainer) {
        console.log('Creating availableTodayEditCalendar...');
        try {
            availableTodayEditCalendar = new DateCalendar('availableTodayCalendar', {
                onSelectionChange: function(selectedDates) {
                    console.log('Available today calendar selection changed:', selectedDates);
                    const hiddenInput = document.getElementById('availableTodayDates');
                    if (hiddenInput) {
                        hiddenInput.value = selectedDates.join(',');
                        console.log('Updated hidden input:', hiddenInput.value);
                    }
                },
                maxSelections: null // Allow unlimited selections
            });
            console.log('availableTodayEditCalendar created successfully:', availableTodayEditCalendar);
        } catch (error) {
            console.error('Error creating availableTodayEditCalendar:', error);
        }
    } else {
        console.log('Skipping availableTodayEditCalendar creation');
    }
}

/**
 * Set selected dates for Today's Product calendar
 */
function setTodaysProductDates(dates) {
    console.log('=== setTodaysProductDates DEBUG ===');
    console.log('Called with dates:', dates);
    console.log('Date type:', typeof dates);
    console.log('Is array:', Array.isArray(dates));
    console.log('Calendar instance exists:', !!todaysProductEditCalendar);
    console.log('Calendar instance:', todaysProductEditCalendar);
    
    if (todaysProductEditCalendar && dates && dates.length > 0) {
        const cleanDates = dates.filter(d => d.trim());
        console.log('Clean dates after filter:', cleanDates);
        
        // Check if calendar has the required methods
        console.log('clearAllDates method exists:', typeof todaysProductEditCalendar.clearAllDates === 'function');
        console.log('setSelectedDates method exists:', typeof todaysProductEditCalendar.setSelectedDates === 'function');
        
        try {
            // Clear existing selections first
            todaysProductEditCalendar.clearAllDates();
            console.log('Cleared existing dates');
            
            // Set new selections
            todaysProductEditCalendar.setSelectedDates(cleanDates);
            console.log('Set new dates');
            
            // Update hidden input
            const hiddenInput = document.getElementById('todaysProductDates');
            if (hiddenInput) {
                hiddenInput.value = cleanDates.join(',');
                console.log('Updated hidden input value:', hiddenInput.value);
            } else {
                console.error('Hidden input todaysProductDates not found!');
            }
            
            console.log('Dates set successfully');
        } catch (error) {
            console.error('Error setting dates:', error);
        }
    } else {
        console.log('Cannot set dates:');
        console.log('- Calendar exists:', !!todaysProductEditCalendar);
        console.log('- Dates provided:', !!dates);
        console.log('- Dates length:', dates ? dates.length : 'N/A');
    }
}

/**
 * Set selected dates for Available Today calendar (regular products)
 */
function setAvailableTodayDates(dates) {
    if (availableTodayEditCalendar && dates && dates.length > 0) {
        // Clear existing selections first
        availableTodayEditCalendar.clearAllDates();
        // Set new selections
        availableTodayEditCalendar.setSelectedDates(dates.filter(d => d.trim()));
        // Update hidden input
        document.getElementById('availableTodayDates').value = dates.join(',');
    }
}

/**
 * Handle status change in edit modal
 */
function handleEditStatusChange() {
    const statusSelect = document.getElementById('editProductStatus');
    const regularDaysContainer = document.getElementById('regularAvailableDaysContainer');
    const todaysCalendarContainer = document.getElementById('todaysProductCalendarContainer');
    const isAvailableTodayContainer = document.getElementById('isAvailableTodayContainer');
    const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
    const availtodayOptionsContainer = document.getElementById('editAvailtodayOptions');
    
    if (!statusSelect) return;
    
    const selectedValue = statusSelect.value;
    
    if (selectedValue === '1' || selectedValue === '2') { // Pick Up or Delivery
        // Show regular days and isAvailableToday option
        if (regularDaysContainer) regularDaysContainer.style.display = 'block';
        if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
        if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'block';
        if (availtodayOptionsContainer) availtodayOptionsContainer.style.display = 'none';
        
    } else if (selectedValue === '3') { // Today's Product
        // For Today's Product: Show calendar and availtoday options
        if (regularDaysContainer) regularDaysContainer.style.display = 'none';
        if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'block';
        if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'none';
        if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
        if (availtodayOptionsContainer) availtodayOptionsContainer.style.display = 'block'; // Show availtoday options
        
        // Initialize Today's product calendar if not already done
        if (!todaysProductEditCalendar && document.getElementById('todaysProductCalendar')) {
            todaysProductEditCalendar = new DateCalendar('todaysProductCalendar', {
                onSelectionChange: function(selectedDates) {
                    document.getElementById('todaysProductDates').value = selectedDates.join(',');
                }
            });
        }
        
    } else {
        // Hide all
        if (regularDaysContainer) regularDaysContainer.style.display = 'none';
        if (todaysCalendarContainer) todaysCalendarContainer.style.display = 'none';
        if (isAvailableTodayContainer) isAvailableTodayContainer.style.display = 'none';
        if (availableTodayCalendarContainer) availableTodayCalendarContainer.style.display = 'none';
        if (availtodayOptionsContainer) availtodayOptionsContainer.style.display = 'none';
    }
}

/**
 * Handle isAvailableToday radio button change
 */
function handleIsAvailableTodayChange() {
    const isAvailableTodayRadio = document.getElementById('isAvailableToday');
    const availableTodayCalendarContainer = document.getElementById('availableTodayCalendarContainer');
    
    if (!isAvailableTodayRadio || !availableTodayCalendarContainer) return;
    
    if (isAvailableTodayRadio.checked) {
        availableTodayCalendarContainer.style.display = 'block';
        
        // Initialize Available Today calendar if not already done
        if (!availableTodayEditCalendar && document.getElementById('availableTodayCalendar')) {
            availableTodayEditCalendar = new DateCalendar('availableTodayCalendar', {
                onSelectionChange: function(selectedDates) {
                    document.getElementById('availableTodayDates').value = selectedDates.join(',');
                }
            });
        }
    } else {
        availableTodayCalendarContainer.style.display = 'none';
        // Clear the hidden input
        const availableTodayDatesInput = document.getElementById('availableTodayDates');
        if (availableTodayDatesInput) {
            availableTodayDatesInput.value = '';
        }
        // Clear calendar selection
        if (availableTodayEditCalendar) {
            availableTodayEditCalendar.clearAllDates();
        }
    }
}

/**
 * Set selected dates for Today's product calendar
 */
function setTodaysProductDates(dates) {
    if (todaysProductEditCalendar && dates && dates.length > 0) {
        todaysProductEditCalendar.setSelectedDates(dates);
    }
}

/**
 * Set selected dates for Available Today calendar
 */
function setAvailableTodayDates(dates) {
    if (availableTodayEditCalendar && dates && dates.length > 0) {
        availableTodayEditCalendar.setSelectedDates(dates);
    }
}

/**
 * Clear all calendar selections
 */
function clearAllCalendarSelections() {
    if (todaysProductEditCalendar) {
        todaysProductEditCalendar.clearAllDates();
    }
    if (availableTodayEditCalendar) {
        availableTodayEditCalendar.clearAllDates();
    }
    
    // Clear hidden inputs
    const todaysProductDatesInput = document.getElementById('todaysProductDates');
    const availableTodayDatesInput = document.getElementById('availableTodayDates');
    
    if (todaysProductDatesInput) todaysProductDatesInput.value = '';
    if (availableTodayDatesInput) availableTodayDatesInput.value = '';
}

/**
 * Make radio button toggleable (can be unselected)
 */
function makeRadioToggleable(radioId) {
    const radio = document.getElementById(radioId);
    if (!radio) return;
    
    let wasChecked = radio.checked;
    
    radio.addEventListener('click', function(e) {
        if (wasChecked && this.checked) {
            // If it was already checked and clicked again, uncheck it
            this.checked = false;
            // Trigger change event manually
            const changeEvent = new Event('change', { bubbles: true });
            this.dispatchEvent(changeEvent);
        }
        wasChecked = this.checked;
    });
    
    // Update wasChecked when the radio is changed programmatically
    radio.addEventListener('change', function() {
        setTimeout(() => {
            wasChecked = this.checked;
        }, 0);
    });
}

/**
 * Initialize event listeners for the edit modal
 */
function initializeEditModalEventListeners() {
    // Status change listener
    const statusSelect = document.getElementById('editProductStatus');
    if (statusSelect) {
        statusSelect.addEventListener('change', handleEditStatusChange);
    }
    
    // isAvailableToday radio button listener
    const isAvailableTodayRadio = document.getElementById('isAvailableToday');
    if (isAvailableTodayRadio) {
        isAvailableTodayRadio.addEventListener('change', handleIsAvailableTodayChange);
        // Make it toggleable
        makeRadioToggleable('isAvailableToday');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize event listeners
    initializeEditModalEventListeners();
    
    // Initialize calendars when modal is opened
    const modal = document.getElementById('editModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const modalStyle = modal.style.display;
                    if (modalStyle === 'flex' || modalStyle === 'block') {
                        // Modal is being shown, initialize calendars
                        setTimeout(() => {
                            initializeEditModalCalendars();
                            handleEditStatusChange(); // Apply initial visibility
                        }, 100);
                    }
                }
            });
        });
        
        observer.observe(modal, { 
            attributes: true, 
            attributeFilter: ['style'] 
        });
    }
});

// Export functions for use in product-list.js
window.modalCalendarHandler = {
    initializeEditModalCalendars: initializeEditModalCalendars,
    handleEditStatusChange: handleEditStatusChange,
    handleIsAvailableTodayChange: handleIsAvailableTodayChange,
    setTodaysProductDates: setTodaysProductDates,
    setAvailableTodayDates: setAvailableTodayDates
};

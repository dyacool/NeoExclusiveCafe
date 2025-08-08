// Enhanced Calendar with Admin Features
let currentDate = new Date();
let dateLimits = {};
let showCompletedOrders = false;

// Initialize calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar(currentDate);
    refreshDefaultLimit();
    setupEventListeners();
});

function setupEventListeners() {
    // Navigation buttons
    document.getElementById('prev').onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
    };
    
    document.getElementById('next').onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
    };

    // Modal close functionality
    const orderModal = document.getElementById('orderModal');
    const confirmationModal = document.getElementById('confirmationModal');

    // Close button handlers
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.onclick = function() {
            orderModal.style.display = 'none';
            confirmationModal.style.display = 'none';
        };
    });

    // Window click handler for overlay close
    window.onclick = function(event) {
        if (event.target === orderModal || event.target === confirmationModal) {
            orderModal.style.display = 'none';
            confirmationModal.style.display = 'none';
        }
    };

    // Cancel button handler
    const cancelBtn = document.getElementById('cancelComplete');
    if (cancelBtn) {
        cancelBtn.onclick = function() {
            confirmationModal.style.display = 'none';
        };
    }

    // Confirm button handler
    const confirmBtn = document.getElementById('confirmComplete');
    if (confirmBtn) {
        confirmBtn.onclick = function() {
            const orderId = this.dataset.orderId;
            if (orderId) {
                completeOrder(orderId);
                confirmationModal.style.display = 'none';
                orderModal.style.display = 'none';
            }
        };
    }
}

function renderCalendar(date) {
    const daysContainer = document.querySelector('.days');
    const monthYear = document.getElementById('monthYear');
    daysContainer.innerHTML = '';
    monthYear.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

    // Add empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        daysContainer.innerHTML += `<div class="day empty"></div>`;
    }

    // Add days of the month
    for (let i = 1; i <= daysInMonth; i++) {
        const dayDate = new Date(date.getFullYear(), date.getMonth(), i);
        const dateStr = dayDate.toISOString().split('T')[0];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        let dayClass = 'day';
        let dayContent = i;
        let clickHandler = '';
        
        // Check if it's today
        if (dayDate.getTime() === today.getTime()) {
            dayClass += ' today';
            dayContent += '<div class="today-indicator">Today</div>';
            // No click handler for today - make it non-interactable
        } else if (dayDate < today) {
            // Check if it's a past date
            dayClass += ' past-date';
            dayContent += '<div class="past-indicator">Past</div>';
        } else {
            // Check if date has orders or limits
            clickHandler = `onclick="showDateLimit('${dateStr}')"`;
            
            // Check if date is not accepting orders
            if (dateLimits[dateStr] && (dateLimits[dateStr].limit === 0 || dateLimits[dateStr].status === 'not_accepting')) {
                dayClass += ' not-accepting-orders';
                dayContent += '<div class="not-accepting-overlay">✕</div>';
            }
        }
        
        daysContainer.innerHTML += `<div class="day ${dayClass}" data-date="${dateStr}" ${clickHandler}>${dayContent}</div>`;
    }
    
    // Load orders for this month
    loadOrdersForMonth(date);
}

function loadOrdersForMonth(date) {
    const startDate = new Date(date.getFullYear(), date.getMonth(), 1);
    const endDate = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    
    console.log('Loading orders for month:', {
        start: startDate.toISOString().split('T')[0],
        end: endDate.toISOString().split('T')[0],
        showCompleted: showCompletedOrders
    });
    
    fetch(`get-orders.php?start=${startDate.toISOString().split('T')[0]}&end=${endDate.toISOString().split('T')[0]}&showCompleted=${showCompletedOrders}`)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(orders => {
            console.log('Orders received:', orders);
            displayOrdersOnCalendar(orders);
        })
        .catch(error => {
            console.error('Error loading orders:', error);
        });
}

function displayOrdersOnCalendar(orders) {
    // Clear existing order indicators
    document.querySelectorAll('.day .order-indicator').forEach(indicator => {
        indicator.remove();
    });
    
    orders.forEach(order => {
        try {
            // Get the date from the order - it could be in 'start' field or 'date' field
            let orderDateStr = order.start || order.date;
            
            if (!orderDateStr) {
                console.warn('Order missing date:', order);
                return;
            }
            
            // Handle different date formats
            let orderDate;
            if (typeof orderDateStr === 'string') {
                // If it's already a date string in YYYY-MM-DD format
                if (orderDateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    orderDate = new Date(orderDateStr + 'T12:00:00');
                } else if (orderDateStr.includes('T')) {
                    // If it's already in ISO format
                    orderDate = new Date(orderDateStr);
                } else {
                    // Try to parse as regular date
                    orderDate = new Date(orderDateStr);
                }
            } else {
                orderDate = new Date(orderDateStr);
            }
            
            // Check if the date is valid
            if (isNaN(orderDate.getTime())) {
                console.warn('Invalid date for order:', order, 'Date string:', orderDateStr);
                return;
            }
            
            const dateStr = orderDate.toISOString().split('T')[0];
            const dayElement = document.querySelector(`[data-date="${dateStr}"]`);
            
            if (dayElement) {
                const orderIndicator = document.createElement('div');
                
                // Get status from extendedProps or use a default
                const status = (order.extendedProps && order.extendedProps.status) || 'pending';
                
                orderIndicator.className = `order-indicator ${status.toLowerCase()}`;
                orderIndicator.innerHTML = `#${order.id}`;
                orderIndicator.onclick = (e) => {
                    e.stopPropagation();
                    showOrderDetails(order.id);
                };
                dayElement.appendChild(orderIndicator);
            } else {
                console.warn('Day element not found for date:', dateStr);
            }
        } catch (error) {
            console.error('Error processing order:', order, 'Error:', error);
        }
    });
}

function showDateLimit(date) {
    console.log('showDateLimit input date:', date);
    
    // Hide the complete order button if present
    var completeOrderBtn = document.getElementById('completeOrderBtn');
    if (completeOrderBtn) {
        completeOrderBtn.style.display = 'none';
    }
    
    const modal = document.getElementById('orderModal');
    const orderInfo = document.getElementById('orderInfo');
    
    // Convert date for display
    const displayDate = new Date(date + 'T12:00:00').toLocaleDateString();
    
    orderInfo.innerHTML = `
        <h3 style="text-align: center">Set Order Limit for ${displayDate}</h3>
        <p style="font-size: 0.8em; color: #666; margin-bottom: 15px; text-align: center">Date: ${date}</p>
        <div id="dateLimitContainer" class="date-limit-controls">
            <div class="limit-input-group">
                <input type="number" id="dateLimit" min="0" class="limit-input">
                <button onclick="updateDateLimit('${date}')" class="update-btn">Update Limit</button>
            </div>
            <div class="not-accepting-group">
                <button onclick="setNotAcceptingOrders('${date}')" class="not-accepting-btn">Not Accepting Orders</button>
            </div>
        </div>
    `;
    modal.style.display = 'block';

    // Fetch current limit for the date
    fetch('get-date-limits.php?date=' + date)
        .then(response => response.text())
        .then(text => {
            const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
            try {
                const data = JSON.parse(jsonStr);
                console.log('Date limit API response for', date, ':', data);
                if (data.success) {
                    const limit = data.dates && data.dates[0] ? data.dates[0].limit : data.default_limit;
                    document.getElementById('dateLimit').value = limit;
                    console.log('Set limit input to:', limit);
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e, 'Response:', jsonStr);
                document.getElementById('dateLimitContainer').innerHTML = 'Error loading limit settings. Please try refreshing the page.';
            }
        })
        .catch(error => {
            console.error('Error fetching default limit:', error);
            document.getElementById('dateLimitContainer').innerHTML = 'Error loading limit settings. Please try refreshing the page.';
        });
}

function setNotAcceptingOrders(date) {
    if (confirm('Are you sure you want to set this date to not accept orders?')) {
        console.log('setNotAcceptingOrders input date:', date);
        
        fetch('update-limit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: 'date',
                date: date,
                limit: 0,
                status: 'not_accepting'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw update response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed update response for date', date, ':', data);
                
                if (data.success) {
                    // Update the dateLimits object
                    dateLimits[date] = {
                        limit: 0,
                        is_full: true,
                        active_orders: 0,
                        remaining_slots: 0,
                        status: 'not_accepting'
                    };
                    
                    // Update the calendar display
                    renderCalendar(currentDate);
                    
                    // Close modal
                    document.getElementById('orderModal').style.display = 'none';
                    
                    alert('Date set to not accept orders successfully!');
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            } catch (e) {
                console.error('Error updating date limit:', e);
                alert('Error updating date limit: ' + e.message);
            }
        })
        .catch(error => {
            console.error('Error updating limit:', error);
            alert('Error updating limit. Please try again.');
        });
    }
}

function updateDateLimit(date) {
    const limit = document.getElementById('dateLimit').value;
    if (limit === '') {
        alert('Please enter a valid limit');
        return;
    }

    console.log('updateDateLimit input date:', date);
    const limitValue = parseInt(limit);
    
    fetch('update-limit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: 'date',
            date: date,
            limit: limitValue
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update limit response for date', date, ':', data);
        if (data.success) {
            // Update the dateLimits object
            if (limitValue === 0) {
                dateLimits[date] = {
                    limit: 0,
                    is_full: false,
                    active_orders: 0,
                    status: 'not_accepting'
                };
            } else {
                dateLimits[date] = {
                    limit: limitValue,
                    is_full: false,
                    active_orders: 0,
                    status: 'accepting'
                };
            }
            
            // Update the calendar display
            renderCalendar(currentDate);
            
            alert('Date limit updated successfully!');
            document.getElementById('orderModal').style.display = 'none';
        } else {
            alert('Error updating limit: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error updating limit:', error);
        alert('Error updating limit. Please try again.');
    });
}

function updateDailyLimit() {
    const limit = document.getElementById('dailyLimit').value;
    if (!limit || parseInt(limit) <= 0) {
        alert('Please enter a valid limit greater than 0');
        return;
    }

    fetch('update-limit.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: 'daily',
            limit: parseInt(limit)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Daily limit updated successfully!');
            // Refresh the calendar
            renderCalendar(currentDate);
            // Refresh the default limit display
            refreshDefaultLimit();
        } else {
            alert('Error updating limit: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error updating limit:', error);
        alert('Error updating limit. Please try again.');
    });
}

function showOrderDetails(orderId) {
    fetch('get-order-details.php?id=' + orderId)
        .then(response => response.json())
        .then(order => {
            const modal = document.getElementById('orderModal');
            const orderInfo = document.getElementById('orderInfo');
            const completeOrderBtn = document.getElementById('completeOrderBtn');
            
            // Format display date based on order type
            const displayDate = order.order_type === 'Pick-up' ? 
                `<p><strong>Pickup Date:</strong> ${order.pickup_date || 'N/A'}</p>` : 
                `<p><strong>Delivery Date:</strong> ${order.delivery_date || 'N/A'}</p>`;
            
            // Build order items HTML if available
            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                itemsHtml = `
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                // Clear any existing items first
                let processedItems = new Set();
                order.items.forEach(item => {
                    // Check if this item has already been processed
                    if (!processedItems.has(item.product_name)) {
                        const subtotal = (item.price * item.quantity).toFixed(2);
                        itemsHtml += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                <td>₱${subtotal}</td>
                            </tr>
                        `;
                        processedItems.add(item.product_name);
                    }
                });
                
                itemsHtml += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="total-label">Total Amount:</td>
                                <td class="total-value">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                `;
            }
            
            // Add status badge
            const statusBadge = `
                <div class="status-badge ${order.status.toLowerCase()}">
                    ${order.status}
                </div>
            `;
            
            orderInfo.innerHTML = `
                <div class="order-details-grid">
                    <div class="order-details-section">
                        <h3>Order Information ${statusBadge}</h3>
                        <p><strong>Order #:</strong> ${order.order_id}</p>
                        <p><strong>Order Date:</strong> ${order.order_date}</p>
                        <p><strong>Delivery Mode:</strong> ${order.order_type}</p>
                        ${displayDate}
                        <p><strong>Time:</strong> ${order.pickup_time}</p>
                        <p><strong>Payment Method:</strong> ${order.payment_method || 'N/A'}</p>
                    </div>
                    
                    <div class="order-details-section">
                        <h3>Customer Information</h3>
                        <p><strong>Name:</strong> ${order.customer_name}</p>
                        <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                        <p><strong>Contact:</strong> ${order.customer_contact || 'N/A'}</p>
                        <p><strong>Address:</strong> ${order.customer_address || 'N/A'}</p>
                        ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ''}
                    </div>
                </div>
                
                ${itemsHtml}
            `;
            
            // Show/hide complete order button based on status
            if (order.status === 'Pending') {
                completeOrderBtn.style.display = 'block';
                completeOrderBtn.onclick = () => showCompletionConfirmation(order.order_id);
            } else {
                completeOrderBtn.style.display = 'none';
            }
            
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            alert('Error loading order details. Please try again.');
        });
}

function showCompletionConfirmation(orderId) {
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmBtn = document.getElementById('confirmComplete');
    const cancelBtn = document.getElementById('cancelComplete');
    
    // Store the order ID for later use
    confirmBtn.dataset.orderId = orderId;
    
    // Show the confirmation modal
    confirmationModal.style.display = 'block';
    
    // Handle confirmation
    confirmBtn.onclick = function() {
        completeOrder(this.dataset.orderId);
        confirmationModal.style.display = 'none';
        // Also close the order details modal
        document.getElementById('orderModal').style.display = 'none';
    };
    
    // Handle cancellation
    cancelBtn.onclick = function() {
        confirmationModal.style.display = 'none';
    };
}

function completeOrder(orderId) {
    fetch('get-done-orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Refresh the calendar to show updated order status
            renderCalendar(currentDate);
            
            // Show success message
            alert(data.message || 'Order marked as completed successfully!');
            
            // Close the order details modal
            document.getElementById('orderModal').style.display = 'none';
        } else {
            alert('Error updating order: ' + (data.message || data.error || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error completing order:', error);
        alert('Error completing order. Please try again.');
    });
}

// Function to refresh the default limit display
function refreshDefaultLimit() {
    fetch('get-date-limits.php?get_default=true')
        .then(response => response.text())
        .then(text => {
            // Clean the response by removing any HTML comments
            const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
            try {
                const data = JSON.parse(jsonStr);
                if (data.success && data.default_limit !== undefined) {
                    document.getElementById('dailyLimit').value = data.default_limit;
                } else {
                    console.error('Invalid response format:', data);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e, 'Response:', jsonStr);
            }
        })
        .catch(error => console.error('Error fetching default limit:', error));
}

// Function to toggle completed orders display
function toggleCompletedOrders() {
    showCompletedOrders = !showCompletedOrders;
    const toggleBtn = document.getElementById('toggleCompletedBtn');
    
    if (showCompletedOrders) {
        toggleBtn.textContent = 'Hide Completed Orders';
        toggleBtn.style.backgroundColor = '#f44336'; // Red color when showing completed
    } else {
        toggleBtn.textContent = 'Show Completed Orders';
        toggleBtn.style.backgroundColor = '#4CAF50'; // Green color when hiding completed
    }
    
    // Refresh the calendar to show/hide completed orders
    renderCalendar(currentDate);
}
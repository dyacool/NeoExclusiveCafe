function openSavedInfoModal() {
    const modal = document.getElementById('savedInfoModal');
    if (modal) {
        modal.classList.add('show');
        showLoading();
        setTimeout(() => renderSavedEntries(), 100);
    }
}

function closeSavedInfoModal() {
    const modal = document.getElementById('savedInfoModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function showLoading() {
    const container = document.getElementById('savedEntriesList');
    if (container) {
        container.innerHTML = '<div class="saved-info-loading"><div class="saved-info-loading-spinner"></div></div>';
    }
}

function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="btn-spinner"></span> Loading...';
        button.disabled = true;
    } else {
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.disabled = false;
    }
}

function renderSavedEntries() {
    const container = document.getElementById('savedEntriesList');
    if (!container || !window.savedInfoManager) return;
    
    const entries = window.savedInfoManager.entries;
    
    let html = '';
    
    if (entries.length < 3) {
        html += renderNewEntryForm();
    }
    
    if (entries.length === 0) {
        html += `
            <div class="no-entries-message">
                <div class="no-entries-icon">📋</div>
                <p>No saved information yet</p>
                <p style="font-size: 14px; color: #888;">Fill the form above to save your first entry!</p>
            </div>
        `;
    } else {
        entries.forEach(entry => {
            html += renderEntryCard(entry);
        });
    }
    
    container.innerHTML = html;
    
    attachModalEventListeners();
    updateSavedInfoCount();
}

function updateSavedInfoCount() {
    if (!window.savedInfoManager) return;
    
    const count = window.savedInfoManager.entries.length;
    const userNameDisplay = document.getElementById('user-name');
    
    if (userNameDisplay && count > 0) {
        const nameText = userNameDisplay.textContent.trim();
        const badge = ` <span class="saved-info-badge">${count} saved</span>`;
        
        if (!nameText.includes('saved')) {
            const cleanName = nameText.replace(/<span.*?<\/span>/g, '').trim();
            userNameDisplay.innerHTML = cleanName + badge;
        }
    }
}

function renderNewEntryForm() {
    return `
        <div class="new-entry-form">
            <div class="entry-header">
                <h3>➕ Add New Information</h3>
            </div>
            <div class="entry-details">
                <div class="entry-field">
                    <label>Label (Optional):</label>
                    <input type="text" id="new-label" class="entry-input" placeholder="e.g., Home, Office, Mom's House" maxlength="50">
                </div>
                <div class="entry-field">
                    <label>First Name: *</label>
                    <input type="text" id="new-first-name" class="entry-input" required maxlength="100">
                </div>
                <div class="entry-field">
                    <label>Last Name: *</label>
                    <input type="text" id="new-last-name" class="entry-input" required maxlength="100">
                </div>
                <div class="entry-field">
                    <label>Email: *</label>
                    <input type="email" id="new-email" class="entry-input" required maxlength="255" readonly>
                    <small class="field-hint">Email cannot be changed (linked to your account)</small>
                </div>
                <div class="entry-field">
                    <label>Contact No.: *</label>
                    <input type="tel" id="new-phone" class="entry-input" required 
                           pattern="09\\d{9}" 
                           placeholder="09xxxxxxxxx (11 digits)" 
                           maxlength="11" 
                           minlength="11"
                           inputmode="numeric">
                    <small class="field-hint">Enter 11-digit phone number starting with 09</small>
                </div>
                <div class="entry-field">
                    <label>Delivery Location: *</label>
                    <select id="new-delivery-location" class="entry-input entry-select" required>
                        <option value="">Choose your delivery location</option>
                    </select>
                </div>
                <div class="entry-field">
                    <label>Complete Address: *</label>
                    <textarea id="new-complete-address" class="entry-input entry-textarea" rows="3" required placeholder="Enter your complete address (house number, street, subdivision, etc.)"></textarea>
                    <small class="field-hint">Please provide specific details like house/building number, street name, subdivision, landmarks, etc.</small>
                </div>
            </div>
            <div class="entry-actions">
                <button id="saveNewEntryBtn" class="btn-save-new">💾 Save New Entry</button>
            </div>
        </div>
    `;
}

function renderEntryCard(entry) {
    const icon = window.savedInfoManager.getIconForLabel(entry.label);
    const label = entry.label || `Info ${entry.id}`;
    const isPrimary = entry.is_primary === 1;
    
    return `
        <div class="saved-entry-card" data-entry-id="${entry.id}">
            <div class="entry-header">
                <h3>${icon} ${label}</h3>
                ${isPrimary ? '<span class="primary-badge">Primary</span>' : ''}
            </div>
            <div class="entry-details">
                <div class="entry-field">
                    <label>Label:</label>
                    <input type="text" class="entry-input" data-field="label" value="${entry.label || ''}" placeholder="e.g., Home, Office">
                </div>
                <div class="entry-field">
                    <label>First Name:</label>
                    <input type="text" class="entry-input" data-field="first_name" value="${entry.first_name}" required>
                </div>
                <div class="entry-field">
                    <label>Last Name:</label>
                    <input type="text" class="entry-input" data-field="last_name" value="${entry.last_name}" required>
                </div>
                <div class="entry-field">
                    <label>Email:</label>
                    <input type="email" class="entry-input" data-field="email" value="${entry.email}" required readonly>
                    <small class="field-hint">Email cannot be changed</small>
                </div>
                <div class="entry-field">
                    <label>Contact No.:</label>
                    <input type="tel" class="entry-input" data-field="phone" value="${entry.phone}" required pattern="(\\+63|0)9\\d{9}">
                </div>
                <div class="entry-field">
                    <label>Delivery Location:</label>
                    <select class="entry-input entry-select" data-field="delivery_location_id" required>
                        <option value="">Select location...</option>
                    </select>
                </div>
                <div class="entry-field">
                    <label>Complete Address:</label>
                    <textarea class="entry-input entry-textarea" data-field="complete_address" rows="2" required>${entry.complete_address}</textarea>
                </div>
                <div class="entry-field-readonly">
                    <strong>Delivery Fee:</strong> ₱${parseFloat(entry.delivery_fee).toFixed(2)}
                </div>
            </div>
            <div class="entry-actions">
                <button class="btn-load" data-entry-id="${entry.id}">✓ Load This</button>
                <button class="btn-save-changes" data-entry-id="${entry.id}">💾 Save Changes</button>
                <button class="btn-delete" data-entry-id="${entry.id}">🗑️ Delete</button>
                <button class="btn-set-primary" data-entry-id="${entry.id}" ${isPrimary ? 'disabled' : ''}>
                    ${isPrimary ? '⭐ Primary' : 'Set as Primary'}
                </button>
            </div>
        </div>
    `;
}

function attachModalEventListeners() {
    populateDeliveryLocationDropdowns();
    populateNewEntryLocationDropdown();
    populateNewEntryEmail();
    
    const saveNewEntryBtn = document.getElementById('saveNewEntryBtn');
    console.log('Save New Entry Button:', saveNewEntryBtn);
    
    if (saveNewEntryBtn) {
        saveNewEntryBtn.addEventListener('click', async function() {
            console.log('Save New Entry button clicked');
            
            const locationSelect = document.getElementById('new-delivery-location');
            const selectedOption = locationSelect.options[locationSelect.selectedIndex];
            const locationId = selectedOption ? parseInt(selectedOption.dataset.locationId) : null;
            
            const newData = {
                label: document.getElementById('new-label').value.trim() || null,
                first_name: document.getElementById('new-first-name').value.trim(),
                last_name: document.getElementById('new-last-name').value.trim(),
                email: document.getElementById('new-email').value.trim(),
                phone: document.getElementById('new-phone').value.trim(),
                delivery_location_id: locationId,
                complete_address: document.getElementById('new-complete-address').value.trim()
            };
            
            console.log('Form data collected:', newData);
            
            if (!newData.first_name || !newData.last_name || !newData.email || 
                !newData.phone || !newData.delivery_location_id || isNaN(newData.delivery_location_id) || !newData.complete_address) {
                console.log('Validation failed: Missing required fields');
                console.log('Missing fields:', {
                    first_name: !newData.first_name,
                    last_name: !newData.last_name,
                    email: !newData.email,
                    phone: !newData.phone,
                    delivery_location_id: !newData.delivery_location_id || isNaN(newData.delivery_location_id),
                    complete_address: !newData.complete_address
                });
                alert('❌ Please fill in all required fields (*)\nMake sure to select a delivery location.');
                return;
            }
            
            if (!newData.phone.match(/^09\d{9}$/)) {
                console.log('Validation failed: Invalid phone format');
                alert('❌ Invalid phone number format. Please enter 11 digits starting with 09');
                return;
            }
            
            if (!newData.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                console.log('Validation failed: Invalid email format');
                alert('❌ Invalid email format');
                return;
            }
            
            console.log('Validation passed, sending to API...');
            
            try {
                const apiUrl = window.savedInfoManager.apiBasePath + 'save-customer-info.php';
                console.log('API URL:', apiUrl);
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newData)
                });
                
                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    alert('✅ New entry saved successfully!');
                    await window.savedInfoManager.loadEntries();
                    renderSavedEntries();
                } else {
                    console.error('Save failed:', data.error);
                    alert('❌ ' + (data.error || 'Failed to save new entry'));
                }
            } catch (error) {
                console.error('Error saving new entry:', error);
                alert('❌ An error occurred while saving: ' + error.message);
            }
        });
    } else {
        console.error('Save New Entry button not found!');
    }
    
    document.querySelectorAll('.btn-load').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = parseInt(this.dataset.entryId);
            const entry = window.savedInfoManager.getEntryById(entryId);
            console.log('Load button clicked for entry:', entry);
            
            if (entry) {
                window.savedInfoManager.fillForm(entry);
                
                const fullAddress = `${entry.complete_address}, ${entry.delivery_location}`;
                const deliveryAddressInput = document.getElementById('delivery_address');
                if (deliveryAddressInput) {
                    deliveryAddressInput.value = fullAddress;
                    console.log('Set delivery_address to:', fullAddress);
                }
                
                const shippingFeeElement = document.getElementById('shipping_fee');
                if (shippingFeeElement) {
                    shippingFeeElement.textContent = '₱' + parseFloat(entry.delivery_fee).toFixed(2);
                    console.log('Set shipping fee to:', entry.delivery_fee);
                }
                
                closeSavedInfoModal();
                alert('✅ Information loaded successfully!');
            } else {
                console.error('Entry not found for ID:', entryId);
            }
        });
    });
    
    document.querySelectorAll('.btn-save-changes').forEach(btn => {
        btn.addEventListener('click', async function() {
            const entryId = parseInt(this.dataset.entryId);
            const card = this.closest('.saved-entry-card');
            
            const updatedData = {
                id: entryId,
                label: card.querySelector('[data-field="label"]').value.trim() || null,
                first_name: card.querySelector('[data-field="first_name"]').value.trim(),
                last_name: card.querySelector('[data-field="last_name"]').value.trim(),
                email: card.querySelector('[data-field="email"]').value.trim(),
                phone: card.querySelector('[data-field="phone"]').value.trim(),
                delivery_location_id: parseInt(card.querySelector('[data-field="delivery_location_id"]').value),
                complete_address: card.querySelector('[data-field="complete_address"]').value.trim()
            };
            
            if (!updatedData.first_name || !updatedData.last_name || !updatedData.email || 
                !updatedData.phone || !updatedData.delivery_location_id || !updatedData.complete_address) {
                alert('Please fill in all required fields');
                return;
            }
            
            try {
                const response = await fetch(window.savedInfoManager.apiBasePath + 'save-customer-info.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Changes saved successfully!');
                    await window.savedInfoManager.loadEntries();
                    renderSavedEntries();
                } else {
                    alert('❌ ' + (data.error || 'Failed to save changes'));
                }
            } catch (error) {
                console.error('Error saving changes:', error);
                alert('❌ An error occurred while saving');
            }
        });
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const entryId = parseInt(this.dataset.entryId);
            const success = await window.savedInfoManager.deleteEntry(entryId);
            if (success) {
                renderSavedEntries();
            }
        });
    });
    
    document.querySelectorAll('.btn-set-primary').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;
            
            const entryId = parseInt(this.dataset.entryId);
            const success = await window.savedInfoManager.setPrimary(entryId);
            if (success) {
                renderSavedEntries();
            }
        });
    });
    
    const addNewBtn = document.getElementById('addNewEntryBtn');
    if (addNewBtn) {
        addNewBtn.addEventListener('click', function() {
            closeSavedInfoModal();
        });
    }
}

function populateDeliveryLocationDropdowns() {
    const mainLocationSelect = document.getElementById('delivery_location');
    if (!mainLocationSelect || mainLocationSelect.options.length <= 1) return;
    
    document.querySelectorAll('.entry-select[data-field="delivery_location_id"]').forEach(select => {
        const entryId = select.closest('.saved-entry-card').dataset.entryId;
        const entry = window.savedInfoManager.getEntryById(parseInt(entryId));
        
        select.innerHTML = mainLocationSelect.innerHTML;
        
        if (entry) {
            for (let option of select.options) {
                if (option.dataset.locationId == entry.delivery_location_id) {
                    option.selected = true;
                    break;
                }
            }
        }
    });
}

function populateNewEntryLocationDropdown() {
    const mainLocationSelect = document.getElementById('delivery_location');
    const newLocationSelect = document.getElementById('new-delivery-location');
    
    console.log('Populating new entry location dropdown');
    console.log('Main location select:', mainLocationSelect);
    console.log('New location select:', newLocationSelect);
    console.log('Main location options count:', mainLocationSelect ? mainLocationSelect.options.length : 0);
    
    if (!mainLocationSelect || !newLocationSelect || mainLocationSelect.options.length <= 1) {
        console.warn('Cannot populate dropdown - missing elements or no options');
        return;
    }
    
    newLocationSelect.innerHTML = mainLocationSelect.innerHTML;
    console.log('New location dropdown populated with', newLocationSelect.options.length, 'options');
}

function populateNewEntryEmail() {
    const newEmailInput = document.getElementById('new-email');
    if (!newEmailInput) return;
    
    const emailField = document.getElementById('customer_email') || document.getElementById('email');
    const userEmailDisplay = document.getElementById('user-email');
    
    let userEmail = '';
    
    if (emailField && emailField.value) {
        userEmail = emailField.value;
    } else if (userEmailDisplay) {
        userEmail = userEmailDisplay.textContent.trim();
    }
    
    if (userEmail && userEmail !== 'Email not available') {
        newEmailInput.value = userEmail;
        console.log('Populated new entry email with:', userEmail);
    } else {
        console.warn('Could not find user email');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('savedInfoModal');
    const closeBtn = modal ? modal.querySelector('.saved-info-close-btn') : null;
    const loadContactsBtn = document.getElementById('loadContactsBtn');
    
    if (loadContactsBtn) {
        loadContactsBtn.addEventListener('click', openSavedInfoModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSavedInfoModal);
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeSavedInfoModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSavedInfoModal();
        }
    });
});

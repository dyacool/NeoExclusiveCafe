class SavedInfoManager {
    constructor() {
        this.entries = [];
        this.currentEntryId = null;
        this.apiBasePath = '../../../backend/api/';
    }

    async loadEntries() {
        try {
            const response = await fetch(this.apiBasePath + 'get-saved-info.php');
            const data = await response.json();
            
            if (data.success) {
                this.entries = data.entries;
                this.populateSelector();
                this.autofillPrimary();
                
                if (this.entries.length === 0) {
                    const userNameDisplay = document.getElementById('user-name');
                    const firstNameField = document.getElementById('first_name');
                    const lastNameField = document.getElementById('last_name');
                    
                    if (userNameDisplay && firstNameField && lastNameField) {
                        const accountName = `${firstNameField.value} ${lastNameField.value}`;
                        userNameDisplay.textContent = accountName;
                    }
                }
                
                if (typeof updateSavedInfoCount === 'function') {
                    updateSavedInfoCount();
                }
                return true;
            } else {
                console.error('Failed to load saved entries:', data.error);
                return false;
            }
        } catch (error) {
            console.error('Error loading saved entries:', error);
            return false;
        }
    }

    populateSelector() {
        const selector = document.getElementById('savedInfoSelector');
        if (!selector) return;
        
        selector.innerHTML = '<option value="">-- Enter new information --</option>';
        
        this.entries.forEach(entry => {
            const option = document.createElement('option');
            option.value = entry.id;
            option.textContent = this.getEntryLabel(entry);
            option.dataset.entryData = JSON.stringify(entry);
            selector.appendChild(option);
        });
    }

    getEntryLabel(entry) {
        const icon = this.getIconForLabel(entry.label);
        const label = entry.label || `Info ${entry.id}`;
        const primary = entry.is_primary ? ' (Primary)' : '';
        return `${icon} ${label}${primary}`;
    }

    getIconForLabel(label) {
        if (!label) return '📍';
        const lowerLabel = label.toLowerCase();
        if (lowerLabel.includes('home')) return '🏠';
        if (lowerLabel.includes('office') || lowerLabel.includes('work')) return '🏢';
        if (lowerLabel.includes('mom') || lowerLabel.includes('dad') || lowerLabel.includes('parent')) return '👨‍👩‍👧';
        return '📍';
    }

    autofillPrimary() {
        const primary = this.entries.find(e => e.is_primary === 1);
        if (primary) {
            this.fillForm(primary);
            this.updateNameDisplay(primary);
            const selector = document.getElementById('savedInfoSelector');
            if (selector) {
                selector.value = primary.id;
            }
        }
    }
    
    updateNameDisplay(entry) {
        const userNameDisplay = document.getElementById('user-name');
        if (userNameDisplay && entry) {
            const fullName = `${entry.first_name} ${entry.last_name}`;
            const count = this.entries.length;
            const badge = count > 0 ? ` <span class="saved-info-badge">${count} saved</span>` : '';
            userNameDisplay.innerHTML = fullName + badge;
        }
    }

    fillForm(entry) {
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        if (firstNameField) firstNameField.value = entry.first_name;
        if (lastNameField) lastNameField.value = entry.last_name;
        
        const emailField = document.getElementById('customer_email') || document.getElementById('email');
        if (emailField) emailField.value = entry.email;
        
        const phoneField = document.getElementById('contact_number') || document.getElementById('phone');
        if (phoneField) phoneField.value = entry.phone;
        
        const locationSelect = document.getElementById('delivery_location');
        if (locationSelect) {
            const locationValue = this.getLocationValue(entry.delivery_location_id);
            if (locationValue) {
                locationSelect.value = locationValue;
                const changeEvent = new Event('change', { bubbles: true });
                locationSelect.dispatchEvent(changeEvent);
            }
        }
        
        const addressField = document.getElementById('complete_address') || document.getElementById('delivery_address');
        if (addressField) addressField.value = entry.complete_address;
        
        this.updateNameDisplay(entry);
        
        this.currentEntryId = entry.id;
    }

    getLocationValue(locationId) {
        const locationSelect = document.getElementById('delivery_location');
        if (!locationSelect) return '';
        
        const options = locationSelect.querySelectorAll('option');
        for (let option of options) {
            if (option.dataset.locationId == locationId) {
                return option.value;
            }
        }
        return '';
    }

    async saveCurrentInfo(label = null, setAsPrimary = false) {
        const formData = this.getFormData();
        if (!formData) return false;
        
        formData.label = label;
        formData.set_as_primary = setAsPrimary;
        
        try {
            const response = await fetch(this.apiBasePath + 'save-customer-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadEntries();
                return true;
            } else {
                alert(data.error || 'Failed to save information');
                return false;
            }
        } catch (error) {
            console.error('Error saving info:', error);
            alert('An error occurred while saving');
            return false;
        }
    }

    getFormData() {
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        const emailField = document.getElementById('customer_email') || document.getElementById('email');
        const phoneField = document.getElementById('contact_number') || document.getElementById('phone');
        const addressField = document.getElementById('complete_address') || document.getElementById('delivery_address');
        
        const first_name = firstNameField ? firstNameField.value.trim() : '';
        const last_name = lastNameField ? lastNameField.value.trim() : '';
        const email = emailField ? emailField.value.trim() : '';
        const phone = phoneField ? phoneField.value.trim() : '';
        const complete_address = addressField ? addressField.value.trim() : '';
        const delivery_location_id = this.getSelectedLocationId();
        
        if (!first_name || !last_name || !email || !phone || !delivery_location_id || !complete_address) {
            return null;
        }
        
        return {
            first_name,
            last_name,
            email,
            phone,
            delivery_location_id,
            complete_address
        };
    }

    getSelectedLocationId() {
        const locationSelect = document.getElementById('delivery_location');
        if (!locationSelect) return null;
        
        const selectedOption = locationSelect.options[locationSelect.selectedIndex];
        return selectedOption && selectedOption.dataset.locationId ? parseInt(selectedOption.dataset.locationId) : null;
    }

    async deleteEntry(entryId) {
        if (!confirm('Are you sure you want to delete this saved information?')) {
            return false;
        }
        
        try {
            const response = await fetch(this.apiBasePath + 'delete-saved-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: entryId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadEntries();
                return true;
            } else {
                alert(data.error || 'Failed to delete entry');
                return false;
            }
        } catch (error) {
            console.error('Error deleting entry:', error);
            alert('An error occurred while deleting');
            return false;
        }
    }

    async setPrimary(entryId) {
        try {
            const response = await fetch(this.apiBasePath + 'set-primary-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: entryId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadEntries();
                return true;
            } else {
                alert(data.error || 'Failed to set primary');
                return false;
            }
        } catch (error) {
            console.error('Error setting primary:', error);
            alert('An error occurred');
            return false;
        }
    }

    getEntryById(entryId) {
        return this.entries.find(e => e.id === parseInt(entryId));
    }
    
    async autoSaveOnFirstCheckout() {
        if (this.entries.length > 0) {
            return false;
        }
        
        const formData = this.getFormData();
        if (!formData) {
            return false;
        }
        
        formData.label = 'My First Address';
        formData.set_as_primary = true;
        
        try {
            const response = await fetch(this.apiBasePath + 'save-customer-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Auto-saved first checkout information');
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error auto-saving:', error);
            return false;
        }
    }
    
    async loadPrimaryInfoAutomatically() {
        try {
            const response = await fetch(this.apiBasePath + 'get-primary-info.php');
            const data = await response.json();
            
            if (data.success && data.entry) {
                this.fillForm(data.entry);
                console.log('Automatically loaded primary customer info');
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error loading primary info:', error);
            return false;
        }
    }
}

function showUserInfoLoading() {
    const userInfoSection = document.querySelector('.section-card.user-information');
    if (userInfoSection && !userInfoSection.querySelector('.user-info-loading-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'user-info-loading-overlay';
        overlay.innerHTML = '<div class="saved-info-loading-spinner"></div>';
        userInfoSection.appendChild(overlay);
    }
    
    const userNameDisplay = document.getElementById('user-name');
    if (userNameDisplay) {
        userNameDisplay.textContent = '...';
    }
}

function hideUserInfoLoading() {
    const overlay = document.querySelector('.user-info-loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    showUserInfoLoading();
    
    window.savedInfoManager = new SavedInfoManager();
    
    // Load entries first, which will auto-fill primary if it exists
    window.savedInfoManager.loadEntries().then(() => {
        hideUserInfoLoading();
    }).catch(() => {
        hideUserInfoLoading();
    });
    
    setupEventListeners();
});

function setupEventListeners() {
    const savedInfoSelector = document.getElementById('savedInfoSelector');
    if (savedInfoSelector) {
        savedInfoSelector.addEventListener('change', function(e) {
            if (e.target.value) {
                const entry = JSON.parse(e.target.selectedOptions[0].dataset.entryData);
                window.savedInfoManager.fillForm(entry);
            }
        });
    }
    
    const saveThisInfoCheckbox = document.getElementById('saveThisInfo');
    const infoLabelInput = document.getElementById('infoLabel');
    const saveNowBtn = document.getElementById('saveNowBtn');
    
    if (saveThisInfoCheckbox && infoLabelInput) {
        saveThisInfoCheckbox.addEventListener('change', function(e) {
            infoLabelInput.style.display = e.target.checked ? 'block' : 'none';
            if (saveNowBtn) {
                saveNowBtn.style.display = e.target.checked ? 'block' : 'none';
            }
        });
    }
    
    if (saveNowBtn) {
        saveNowBtn.addEventListener('click', async function() {
            const label = infoLabelInput ? infoLabelInput.value.trim() : null;
            const success = await window.savedInfoManager.saveCurrentInfo(label, false);
            
            if (success) {
                alert('✅ Information saved successfully!');
                if (saveThisInfoCheckbox) saveThisInfoCheckbox.checked = false;
                if (infoLabelInput) {
                    infoLabelInput.value = '';
                    infoLabelInput.style.display = 'none';
                }
                saveNowBtn.style.display = 'none';
            }
        });
    }
    
    const manageSavedInfoBtn = document.getElementById('manageSavedInfoBtn');
    if (manageSavedInfoBtn) {
        manageSavedInfoBtn.addEventListener('click', function() {
            if (typeof openSavedInfoModal === 'function') {
                openSavedInfoModal();
            }
        });
    }
}

window.autoSaveCheckoutInfo = async function() {
    if (window.savedInfoManager) {
        return await window.savedInfoManager.autoSaveOnFirstCheckout();
    }
    return false;
};

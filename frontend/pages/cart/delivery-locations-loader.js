/**
 * Delivery Locations Loader
 * Dynamically loads delivery locations from the database into the location modal
 */

// Load delivery locations when the page loads
document.addEventListener('DOMContentLoaded', function() {
    loadDeliveryLocations();
});

/**
 * Fetch delivery locations from the API and populate the select dropdown
 */
async function loadDeliveryLocations() {
    const deliveryLocationSelect = document.getElementById('delivery_location');
    
    if (!deliveryLocationSelect) {
        console.error('Delivery location select element not found');
        return;
    }
    
    try {
        // Build API URL based on current location
        const currentPath = window.location.pathname;
        let apiUrl;
        
        // Check if we're in a project subdirectory (like /NeoCafe/)
        if (currentPath.includes('/NeoCafe/')) {
            // Extract the base path (e.g., /NeoCafe/)
            const basePath = currentPath.substring(0, currentPath.indexOf('/NeoCafe/') + 9);
            apiUrl = basePath + 'backend/api/get-delivery-locations.php';
        } else if (currentPath.includes('/frontend/pages/cart/')) {
            // Use relative path from cart directory
            apiUrl = '../../../backend/api/get-delivery-locations.php';
        } else {
            // Default to absolute path from root
            apiUrl = '/backend/api/get-delivery-locations.php';
        }
        
        console.log('Current page:', currentPath);
        console.log('Fetching from:', apiUrl);
        
        const response = await fetch(apiUrl);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get the response text first to check if it's valid JSON
        const responseText = await response.text();
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        console.log('First 500 chars of response:', responseText.substring(0, 500));
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Invalid JSON response. Full response:', responseText);
            console.error('Parse error:', parseError.message);
            throw new Error('Server returned invalid response. Check console for details.');
        }
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load delivery locations');
        }
        
        // Clear existing options except the first one (placeholder)
        while (deliveryLocationSelect.options.length > 1) {
            deliveryLocationSelect.remove(1);
        }
        
        // Group locations by city
        const grouped = data.grouped;
        
        // Add optgroups and options
        for (const city in grouped) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = city;
            
            grouped[city].forEach(location => {
                const option = document.createElement('option');
                option.value = location.value;
                option.textContent = location.display_text;
                option.dataset.deliveryFee = location.delivery_fee;
                option.dataset.locationId = location.id;
                optgroup.appendChild(option);
            });
            
            deliveryLocationSelect.appendChild(optgroup);
        }
        
        console.log('Delivery locations loaded successfully:', data.locations.length, 'locations');
        
    } catch (error) {
        console.error('Error loading delivery locations:', error);
        
        // Show error message to user
        const errorOption = document.createElement('option');
        errorOption.value = '';
        errorOption.textContent = 'Error loading locations. Please refresh the page.';
        errorOption.disabled = true;
        deliveryLocationSelect.appendChild(errorOption);
    }
}

/**
 * Get the delivery fee for a selected location
 * @param {string} locationValue - The value of the selected location
 * @returns {number} The delivery fee
 */
function getDeliveryFee(locationValue) {
    const deliveryLocationSelect = document.getElementById('delivery_location');
    const selectedOption = deliveryLocationSelect.querySelector(`option[value="${locationValue}"]`);
    
    if (selectedOption && selectedOption.dataset.deliveryFee) {
        return parseFloat(selectedOption.dataset.deliveryFee);
    }
    
    return 0;
}

// Export functions for use in other scripts
window.deliveryLocationsLoader = {
    loadDeliveryLocations,
    getDeliveryFee
};

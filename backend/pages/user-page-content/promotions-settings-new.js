// Promotions Management System
class PromotionsManager {
    constructor() {
        this.currentPage = 1;
        this.itemsPerPage = 12;
        this.totalPages = 1;
        this.allPromotions = [];
        this.filteredPromotions = [];
        this.selectedPromotions = [];
        this.filters = {};
        this.searchTerm = '';
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPromotions();
    }

    bindEvents() {
        // Header action buttons
        const newBtn = document.getElementById('supply-order-new-btn');
        const viewBtn = document.getElementById('view-supply-order-btn');
        const reactivateBtn = document.getElementById('reactivate-voucher-btn');
        const filterBtn = document.getElementById('filter-btn');

        if (newBtn) newBtn.addEventListener('click', () => this.openAddModal());
        if (viewBtn) viewBtn.addEventListener('click', () => this.viewSelectedPromotion());
        if (reactivateBtn) reactivateBtn.addEventListener('click', () => this.reactivateSelectedPromotion());
        if (filterBtn) filterBtn.addEventListener('click', () => this.toggleFilter());

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchTerm = e.target.value.toLowerCase();
                this.filterAndPaginate();
            });
        }

        // Filter events
        const applyFiltersBtn = document.getElementById('apply-filters-btn');
        const resetFiltersBtn = document.getElementById('reset-filters-btn');
        
        if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', () => this.applyFilters());
        if (resetFiltersBtn) resetFiltersBtn.addEventListener('click', () => this.resetFilters());

        // Modal events
        this.bindModalEvents();

        // Table row selection
        this.bindTableEvents();
    }

    bindModalEvents() {
        // Add Modal
        const addModal = document.getElementById('addModal');
        const addCloseBtn = addModal?.querySelector('.close');
        
        if (addCloseBtn) {
            addCloseBtn.addEventListener('click', () => this.closeModal('addModal'));
        }

        // Reactivate Modal
        const reactivateModal = document.getElementById('reactivate-voucher-modal');
        const reactivateCloseBtn = document.getElementById('reactivate-voucher-modal-close');
        const reactivateSubmitBtn = document.getElementById('reactivate-voucher-submit');
        
        if (reactivateCloseBtn) {
            reactivateCloseBtn.addEventListener('click', () => this.closeModal('reactivate-voucher-modal'));
        }
        
        if (reactivateSubmitBtn) {
            reactivateSubmitBtn.addEventListener('click', (e) => this.submitReactivation(e));
        }

        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        });

        // Add coupon form
        const addForm = document.getElementById('addCouponForm');
        if (addForm) {
            addForm.addEventListener('submit', (e) => this.addCoupon(e));
        }

        // Form enhancements
        this.bindFormEvents();
    }

    bindFormEvents() {
        // Discount type change
        const discountType = document.getElementById('discountType');
        if (discountType) {
            discountType.addEventListener('change', () => this.toggleDiscountValue());
        }

        // Usage limit toggles
        const unlimitedUsage = document.getElementById('unlimitedUsage');
        const unlimitedPerUser = document.getElementById('unlimitedPerUser');
        
        if (unlimitedUsage) {
            unlimitedUsage.addEventListener('change', () => this.toggleUsageLimit());
        }
        
        if (unlimitedPerUser) {
            unlimitedPerUser.addEventListener('change', () => this.togglePerUserLimit());
        }

        // Date validation
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', () => {
                endDate.min = startDate.value;
                if (endDate.value && endDate.value < startDate.value) {
                    endDate.value = startDate.value;
                }
            });
        }
    }

    bindTableEvents() {
        const tableBody = document.getElementById('promotions-table-body');
        if (tableBody) {
            tableBody.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                if (row && row.dataset.promotionId) {
                    this.toggleRowSelection(row);
                    this.updateActionButtons();
                }
            });
        }
    }

    async loadPromotions() {
        try {
            const response = await fetch('./promotions_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=getAllPromotions'
            });

            const data = await response.json();
            if (data.success) {
                this.allPromotions = data.data || [];
                this.filterAndPaginate();
            } else {
                this.showError('Failed to load promotions: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading promotions:', error);
            this.showError('Network error while loading promotions');
        }
    }

    filterAndPaginate() {
        // Apply search filter
        this.filteredPromotions = this.allPromotions.filter(promo => {
            if (this.searchTerm) {
                const searchFields = [
                    promo.title,
                    promo.code,
                    promo.type,
                    promo.status
                ].join(' ').toLowerCase();
                
                if (!searchFields.includes(this.searchTerm)) {
                    return false;
                }
            }
            
            // Apply other filters
            return this.matchesFilters(promo);
        });

        // Calculate pagination
        this.totalPages = Math.ceil(this.filteredPromotions.length / this.itemsPerPage);
        if (this.currentPage > this.totalPages && this.totalPages > 0) {
            this.currentPage = this.totalPages;
        }

        this.renderTable();
        this.renderPagination();
    }

    matchesFilters(promo) {
        // Implement filter logic here
        if (this.filters.voucher_type && promo.type !== this.filters.voucher_type) {
            return false;
        }
        
        if (this.filters.status && promo.status !== this.filters.status) {
            return false;
        }
        
        if (this.filters.applies_to && promo.applicable_to !== this.filters.applies_to) {
            return false;
        }
        
        // Value range filter
        if (this.filters.value_min || this.filters.value_max) {
            const value = parseFloat(promo.value) || 0;
            if (this.filters.value_min && value < parseFloat(this.filters.value_min)) {
                return false;
            }
            if (this.filters.value_max && value > parseFloat(this.filters.value_max)) {
                return false;
            }
        }
        
        // Date range filter
        if (this.filters.validity_from || this.filters.validity_to) {
            const activationDate = new Date(promo.activation_date);
            const expirationDate = new Date(promo.expiration_date);
            
            if (this.filters.validity_from) {
                const fromDate = new Date(this.filters.validity_from);
                if (expirationDate < fromDate) {
                    return false;
                }
            }
            
            if (this.filters.validity_to) {
                const toDate = new Date(this.filters.validity_to);
                if (activationDate > toDate) {
                    return false;
                }
            }
        }
        
        return true;
    }

    renderTable() {
        const tableBody = document.getElementById('promotions-table-body');
        if (!tableBody) return;

        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const pagePromotions = this.filteredPromotions.slice(startIndex, endIndex);

        if (pagePromotions.length === 0) {
            tableBody.innerHTML = `
                <tr class="no-results">
                    <td colspan="11">No promotions found. ${this.searchTerm || Object.keys(this.filters).length > 0 ? 'Try adjusting your search or filters.' : 'Click "New Coupon" to get started.'}</td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = pagePromotions.map(promo => `
            <tr data-promotion-id="${promo.id}" class="${this.selectedPromotions.includes(promo.id) ? 'selected' : ''}">
                <td>${promo.id}</td>
                <td><span class="promotion-title">${this.escapeHtml(promo.title)}</span></td>
                <td>${this.formatMethod(promo.application_method)}</td>
                <td><code>${this.escapeHtml(promo.code)}</code></td>
                <td>${this.formatDiscount(promo)}</td>
                <td>${this.formatRestrictions(promo)}</td>
                <td>${this.formatUsage(promo)}</td>
                <td>${this.formatValidPeriod(promo)}</td>
                <td>${this.formatSaleChannel(promo.applicable_to)}</td>
                <td>${this.formatStatus(promo.status)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-action btn-view" onclick="promotionsManager.viewPromotion(${promo.id})" title="View Details">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderPagination() {
        const paginationContainer = document.getElementById('pagination-container');
        if (!paginationContainer || this.totalPages <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '';
        
        for (let i = 1; i <= this.totalPages; i++) {
            paginationHTML += `
                <a href="#" class="pagination-link ${i === this.currentPage ? 'active' : ''}" 
                   onclick="promotionsManager.goToPage(${i}); return false;">
                    ${i}
                </a>
            `;
        }

        paginationContainer.innerHTML = paginationHTML;
    }

    // Utility methods for formatting
    formatMethod(method) {
        switch (method) {
            case 'voucher_code': return 'Voucher Code';
            case 'automatic_discount': return 'Automatic Discount';
            default: return method;
        }
    }

    formatDiscount(promo) {
        if (promo.type === 'free_shipping') {
            return '<span class="status-badge upcoming">Free Shipping Only</span>';
        } else if (promo.type === 'percentage') {
            return `${promo.value}%`;
        } else if (promo.type === 'fixed') {
            return `₱${parseFloat(promo.value).toFixed(2)}`;
        }
        return promo.value || 'N/A';
    }

    formatRestrictions(promo) {
        const restrictions = [];
        if (promo.min_purchase && parseFloat(promo.min_purchase) > 0) {
            restrictions.push(`Min: ₱${parseFloat(promo.min_purchase).toFixed(2)}`);
        }
        return restrictions.length > 0 ? restrictions.join(', ') : 'None';
    }

    formatUsage(promo) {
        const global = promo.usage_limit === null ? '∞' : promo.usage_limit;
        const perUser = promo.usage_limit_per_user === null ? '∞' : promo.usage_limit_per_user;
        return `${global} global, ${perUser} per user`;
    }

    formatValidPeriod(promo) {
        const start = new Date(promo.activation_date).toLocaleDateString();
        const end = new Date(promo.expiration_date).toLocaleDateString();
        return `${start} - ${end}`;
    }

    formatSaleChannel(applicableTo) {
        switch (applicableTo) {
            case 'all': return 'All Products';
            case 'delivery': return 'Delivery Only';
            case 'pickup': return 'Pickup Only';
            case 'special': return 'Special Products';
            default: return applicableTo;
        }
    }

    formatStatus(status) {
        const statusClass = status.toLowerCase();
        const statusText = status.charAt(0).toUpperCase() + status.slice(1);
        return `<span class="status-badge ${statusClass}">${statusText}</span>`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Navigation methods
    goToPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.renderTable();
            this.renderPagination();
        }
    }

    // Selection methods
    toggleRowSelection(row) {
        const promotionId = parseInt(row.dataset.promotionId);
        const index = this.selectedPromotions.indexOf(promotionId);
        
        if (index === -1) {
            this.selectedPromotions.push(promotionId);
            row.classList.add('selected');
        } else {
            this.selectedPromotions.splice(index, 1);
            row.classList.remove('selected');
        }
    }

    updateActionButtons() {
        const reactivateBtn = document.getElementById('reactivate-voucher-btn');
        const viewBtn = document.getElementById('view-supply-order-btn');
        
        if (reactivateBtn) {
            // Enable reactivate only if exactly one expired promotion is selected
            const selectedPromo = this.getSelectedPromotion();
            reactivateBtn.disabled = !(selectedPromo && selectedPromo.status === 'expired');
        }
    }

    getSelectedPromotion() {
        if (this.selectedPromotions.length === 1) {
            return this.allPromotions.find(p => p.id === this.selectedPromotions[0]);
        }
        return null;
    }

    // Filter methods
    toggleFilter() {
        const filterContainer = document.getElementById('filterContainer');
        const filterBtn = document.getElementById('filter-btn');
        
        if (filterContainer) {
            const isVisible = filterContainer.style.display !== 'none';
            filterContainer.style.display = isVisible ? 'none' : 'block';
            filterBtn.classList.toggle('active', !isVisible);
        }
    }

    applyFilters() {
        this.filters = {
            voucher_type: document.getElementById('voucher-type-filter')?.value || '',
            status: document.getElementById('status-filter')?.value || '',
            applies_to: document.getElementById('applies-to-filter')?.value || '',
            value_min: document.getElementById('value-min')?.value || '',
            value_max: document.getElementById('value-max')?.value || '',
            usage_limit_type: document.getElementById('usage-limit-type')?.value || '',
            validity_from: document.getElementById('validity-from')?.value || '',
            validity_to: document.getElementById('validity-to')?.value || ''
        };

        // Remove empty filters
        Object.keys(this.filters).forEach(key => {
            if (!this.filters[key]) {
                delete this.filters[key];
            }
        });

        this.currentPage = 1;
        this.filterAndPaginate();
        this.toggleFilter();
    }

    resetFilters() {
        this.filters = {};
        
        // Reset form values
        const filterForm = document.querySelector('.filter-content');
        if (filterForm) {
            const inputs = filterForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.value = '';
            });
        }

        this.currentPage = 1;
        this.filterAndPaginate();
        this.toggleFilter();
    }

    // Modal methods
    openAddModal() {
        const modal = document.getElementById('addModal');
        if (modal) {
            modal.style.display = 'flex';
            this.resetAddForm();
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            if (modalId === 'addModal') {
                this.resetAddForm();
            } else if (modalId === 'reactivate-voucher-modal') {
                this.resetReactivateForm();
            }
        }
    }

    resetAddForm() {
        const form = document.getElementById('addCouponForm');
        if (form) {
            form.reset();
            this.toggleDiscountValue();
            this.toggleUsageLimit();
            this.togglePerUserLimit();
        }
    }

    resetReactivateForm() {
        const form = document.getElementById('reactivate-voucher-form');
        if (form) {
            form.reset();
            delete form.dataset.voucherId;
        }
    }

    // Form interaction methods
    toggleDiscountValue() {
        const discountType = document.getElementById('discountType');
        const discountValueGroup = document.getElementById('discountValueGroup');
        const discountValue = document.getElementById('discountValue');
        
        if (discountType && discountValueGroup && discountValue) {
            if (discountType.value === 'free_shipping') {
                discountValueGroup.style.display = 'none';
                discountValue.required = false;
                discountValue.value = '';
            } else {
                discountValueGroup.style.display = 'block';
                discountValue.required = true;
            }
        }
    }

    toggleUsageLimit() {
        const unlimited = document.getElementById('unlimitedUsage');
        const limitGroup = document.getElementById('usageLimitGroup');
        const limitInput = document.getElementById('usageLimit');
        
        if (unlimited && limitGroup && limitInput) {
            if (unlimited.checked) {
                limitGroup.style.display = 'none';
                limitInput.required = false;
                limitInput.value = '';
            } else {
                limitGroup.style.display = 'block';
                limitInput.required = true;
            }
        }
    }

    togglePerUserLimit() {
        const unlimited = document.getElementById('unlimitedPerUser');
        const limitGroup = document.getElementById('perUserLimitGroup');
        const limitInput = document.getElementById('perUserLimit');
        
        if (unlimited && limitGroup && limitInput) {
            if (unlimited.checked) {
                limitGroup.style.display = 'none';
                limitInput.required = false;
                limitInput.value = '';
            } else {
                limitGroup.style.display = 'block';
                limitInput.required = true;
            }
        }
    }

    // Action methods
    async addCoupon(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        formData.append('action', 'addCoupon');
        
        try {
            const response = await fetch('./promotions_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Coupon added successfully!');
                this.closeModal('addModal');
                await this.loadPromotions();
            } else {
                this.showError('Failed to add coupon: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error adding coupon:', error);
            this.showError('Network error while adding coupon');
        }
    }

    viewSelectedPromotion() {
        const selected = this.getSelectedPromotion();
        if (!selected) {
            this.showWarning('Please select one promotion to view.');
            return;
        }
        
        this.viewPromotion(selected.id);
        this.selectedPromotions = [];
        this.renderTable();
        this.updateActionButtons();
    }

    viewPromotion(promotionId) {
        const promotion = this.allPromotions.find(p => p.id === promotionId);
        if (!promotion) return;

        const html = `
            <div style="text-align: left; font-family: Inter, sans-serif;">
                <div style="margin-bottom: 1rem;">
                    <strong>Code:</strong> ${this.escapeHtml(promotion.code)}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Type:</strong> ${this.formatMethod(promotion.application_method)}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Discount:</strong> ${this.formatDiscount(promotion)}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Status:</strong> ${promotion.status}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Valid Period:</strong> ${this.formatValidPeriod(promotion)}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Min Purchase:</strong> ${promotion.min_purchase && parseFloat(promotion.min_purchase) > 0 ? '₱' + parseFloat(promotion.min_purchase).toFixed(2) : 'None'}
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>Usage Limit:</strong> ${this.formatUsage(promotion)}
                </div>
                <div>
                    <strong>Applies To:</strong> ${this.formatSaleChannel(promotion.applicable_to)}
                </div>
            </div>
        `;

        Swal.fire({
            title: promotion.title,
            html: html,
            width: '500px',
            showConfirmButton: true,
            confirmButtonText: 'Close',
            customClass: {
                popup: 'promotion-view-popup'
            }
        });
    }

    reactivateSelectedPromotion() {
        const selected = this.getSelectedPromotion();
        if (!selected || selected.status !== 'expired') {
            this.showWarning('Please select one expired promotion to reactivate.');
            return;
        }

        // Set up reactivate modal
        const today = new Date().toISOString().split('T')[0];
        const nextWeek = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        
        const activationInput = document.getElementById('reactivate-activation-date');
        const expirationInput = document.getElementById('reactivate-expiration-date');
        const form = document.getElementById('reactivate-voucher-form');
        
        if (activationInput && expirationInput && form) {
            activationInput.value = today;
            expirationInput.value = nextWeek;
            activationInput.min = today;
            expirationInput.min = today;
            form.dataset.voucherId = selected.id;
            
            // Date validation
            activationInput.addEventListener('change', () => {
                expirationInput.min = activationInput.value;
                if (expirationInput.value < activationInput.value) {
                    expirationInput.value = activationInput.value;
                }
            });
        }

        const modal = document.getElementById('reactivate-voucher-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    async submitReactivation(event) {
        event.preventDefault();
        
        const form = document.getElementById('reactivate-voucher-form');
        const voucherId = form?.dataset.voucherId;
        const activationDate = document.getElementById('reactivate-activation-date')?.value;
        const expirationDate = document.getElementById('reactivate-expiration-date')?.value;
        
        if (!voucherId || !activationDate || !expirationDate) {
            this.showError('All fields are required.');
            return;
        }
        
        if (activationDate > expirationDate) {
            this.showError('Activation date cannot be after expiration date.');
            return;
        }

        const submitBtn = document.getElementById('reactivate-voucher-submit');
        const loader = document.getElementById('reactivate-voucher-loader-overlay');
        
        if (submitBtn) submitBtn.disabled = true;
        if (loader) loader.style.display = 'flex';

        try {
            const formData = new FormData();
            formData.append('action', 'reactivate_voucher');
            formData.append('voucher_id', voucherId);
            formData.append('activation_date', activationDate);
            formData.append('expiration_date', expirationDate);

            const response = await fetch('./promotions_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Promotion reactivated successfully!');
                this.closeModal('reactivate-voucher-modal');
                this.selectedPromotions = [];
                await this.loadPromotions();
            } else {
                this.showError('Failed to reactivate promotion: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error reactivating promotion:', error);
            this.showError('Network error while reactivating promotion');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (loader) loader.style.display = 'none';
        }
    }

    // Notification methods
    showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Success', message, 'success');
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', message, 'error');
        } else {
            alert('Error: ' + message);
        }
    }

    showWarning(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Warning', message, 'warning');
        } else {
            alert('Warning: ' + message);
        }
    }
}

// Initialize the promotions manager when DOM is loaded
let promotionsManager;

document.addEventListener('DOMContentLoaded', function() {
    promotionsManager = new PromotionsManager();
});

// Global functions for backward compatibility
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    if (promotionsManager) {
        promotionsManager.closeModal(modalId);
    }
}

function addCoupon(event) {
    if (promotionsManager) {
        return promotionsManager.addCoupon(event);
    }
}

function toggleDiscountValue() {
    if (promotionsManager) {
        promotionsManager.toggleDiscountValue();
    }
}

function toggleUsageLimit() {
    if (promotionsManager) {
        promotionsManager.toggleUsageLimit();
    }
}

function togglePerUserLimit() {
    if (promotionsManager) {
        promotionsManager.togglePerUserLimit();
    }
}
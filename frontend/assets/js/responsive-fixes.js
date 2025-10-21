/**
 * Responsive Fixes for NeoCafe Frontend
 * This script applies responsive design fixes to all pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix for images exceeding container width
    const images = document.querySelectorAll('img:not(.img-fluid)');
    images.forEach(img => {
        if (!img.classList.contains('no-responsive')) {
            img.classList.add('img-fluid');
        }
    });

    // Add responsive class to tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });

    // Fix for iframe responsiveness
    const iframes = document.querySelectorAll('iframe');
    iframes.forEach(iframe => {
        if (!iframe.classList.contains('no-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'responsive-iframe';
            iframe.parentNode.insertBefore(wrapper, iframe);
            wrapper.appendChild(iframe);
        }
    });

    // Add responsive navigation toggle for mobile
    const navToggle = document.querySelector('.navbar-toggler');
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-target'));
            if (target) {
                target.classList.toggle('show');
            }
        });
    }

    // Handle window resize events
    let resizeTimer;
    window.addEventListener('resize', function() {
        document.body.classList.add('resize-animation-stopper');
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            document.body.classList.remove('resize-animation-stopper');
        }, 400);
    });
});

// Add responsive utility classes
function addResponsiveClasses() {
    // Add touch class for touch devices
    if ('ontouchstart' in window || navigator.maxTouchPoints) {
        document.documentElement.classList.add('touch-device');
    } else {
        document.documentElement.classList.add('no-touch-device');
    }
}

// Run on load
addResponsiveClasses();

// Expose functions to global scope if needed
window.ResponsiveFixes = {
    init: addResponsiveClasses
};

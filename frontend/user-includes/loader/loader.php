<!-- Loader Screen -->
<div id="pageLoader" class="page-loader">
    <div class="loader-spinner">
        <div class="spinner"></div>
        <p class="loader-text">Loading...</p>
    </div>
</div>

<style>
    .page-loader {
        position: fixed;
        top: 80px; /* Start below navbar */
        left: 0;
        width: 100%;
        height: calc(100vh - 80px); /* Height minus navbar */
        background-color: rgba(26, 74, 40, 0.95); /* Semi-transparent primary color */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9998; /* Below navbar but above content */
        opacity: 1;
        visibility: visible;
        transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        box-sizing: border-box;
        /* Ensure loader appears immediately on page load/refresh */
        pointer-events: auto;
    }

    .page-loader.fade-out {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .loader-spinner {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .spinner {
        width: 60px;
        height: 60px;
        margin: 0 auto 20px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loader-text {
        color: #ffffff;
        font-size: 18px;
        font-weight: 500;
        margin: 0;
        letter-spacing: 1px;
        text-align: center;
    }

    /* Responsive adjustments for all breakpoints */
    @media (max-width: 1024px) {
        .page-loader {
            top: 70px; /* Adjust for mobile navbar height */
            height: calc(100vh - 70px);
        }
        
        .spinner {
            width: 55px;
            height: 55px;
        }
        
        .loader-text {
            font-size: 17px;
        }
    }

    @media (max-width: 768px) {
        .page-loader {
            top: 65px;
            height: calc(100vh - 65px);
        }

        .spinner {
            width: 50px;
            height: 50px;
        }

        .loader-text {
            font-size: 16px;
        }
    }

    @media (max-width: 425px) {
        .page-loader {
            top: 60px;
            height: calc(100vh - 60px);
        }

        .spinner {
            width: 45px;
            height: 45px;
            border-width: 3px;
        }

        .loader-text {
            font-size: 15px;
            letter-spacing: 0.5px;
        }
    }

    @media (max-width: 375px) {
        .page-loader {
            top: 55px;
            height: calc(100vh - 55px);
        }

        .spinner {
            width: 40px;
            height: 40px;
        }

        .loader-text {
            font-size: 14px;
        }
    }

    @media (max-width: 320px) {
        .page-loader {
            top: 50px;
            height: calc(100vh - 50px);
        }

        .spinner {
            width: 35px;
            height: 35px;
        }

        .loader-text {
            font-size: 13px;
        }
    }
</style>

<script>
    // Show loader immediately when page starts loading
    document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            // Ensure loader is visible during page load
            loader.style.display = 'flex';
            loader.style.opacity = '1';
            loader.style.visibility = 'visible';
            loader.classList.remove('fade-out');
        }
    });

    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500); // Match transition duration
        }
    });

    // Fallback: Hide loader after 5 seconds if load event doesn't fire
    setTimeout(function() {
        const loader = document.getElementById('pageLoader');
        if (loader && !loader.classList.contains('fade-out')) {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
    }, 5000);

    // Show loader on page refresh/navigation
    window.addEventListener('beforeunload', function() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.style.display = 'flex';
            loader.style.opacity = '1';
            loader.style.visibility = 'visible';
            loader.classList.remove('fade-out');
        }
    });
</script>

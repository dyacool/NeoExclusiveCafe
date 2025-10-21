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
        top: 70px; /* Adjust based on your navbar height */
        left: 0;
        width: 100%;
        height: calc(100vh - 70px);
        background-color: rgba(26, 74, 40, 0.95); /* Semi-transparent primary color */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 1;
        transition: opacity 0.5s ease-out;
    }

    .page-loader.fade-out {
        opacity: 0;
        pointer-events: none;
    }

    .loader-spinner {
        text-align: center;
    }

    .spinner {
        width: 50px;
        height: 50px;
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
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .page-loader {
            top: 60px; /* Smaller navbar on mobile */
            height: calc(100vh - 60px);
        }

        .spinner {
            width: 40px;
            height: 40px;
        }

        .loader-text {
            font-size: 16px;
        }
    }
</style>

<script>
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

    // Fallback: Hide loader after 3 seconds if load event doesn't fire
    setTimeout(function() {
        const loader = document.getElementById('pageLoader');
        if (loader && loader.style.display !== 'none') {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
    }, 3000);
</script>

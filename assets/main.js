document.addEventListener('DOMContentLoaded', () => {
    // Select the toggle button and the navigation links container
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            // Toggle the visibility class
            navLinks.classList.toggle('nav-active');
            
            // Check if the menu is now active
            if (navLinks.classList.contains('nav-active')) {
                // Menu is open, inject the X mark
                mobileToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            } else {
                // Menu is closed, inject the hamburger bars
                mobileToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            }
        });
    }
});
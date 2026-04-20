document.addEventListener('DOMContentLoaded', () => {
    // Prevent Double-Binding: If we already initialized the menu, stop here!
    if (window.navMenuInitialized) return;
    window.navMenuInitialized = true;

    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', (e) => {
            e.preventDefault(); // Stop any default button behavior
            
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
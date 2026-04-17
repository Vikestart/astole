// Automatically remove error/success messages after a few seconds
$(document).ready(function() {
  $('nav > ol').addClass('nav-mobile-animated');
});
// Mobile menu
$('#nav-mobile-toggle').click(function(){
    $('nav > ol').toggleClass('nav-mobile-active');
    $('#nav-mobile-toggle').toggleClass('nav-mobile-item-active');
    var icon = $('#nav-mobile-toggle-icon');
    var icon_fa_icon = icon.attr('data-icon');
    setTimeout(
    function() {
      if (icon_fa_icon === "bars") {
          icon.attr('data-icon', 'times');
      } else {
          icon.attr('data-icon', 'bars');
      }
    }, 125);
});
// Mobile menu
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
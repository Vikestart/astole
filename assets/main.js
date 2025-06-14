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

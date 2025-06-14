// Automatically remove error/success messages after a few seconds
$(document).ready(function() {
  $('nav > ol').addClass('nav-mobile-animated');
  $('.msgbox').each(function(i, obj) {
    $(this).delay($(this).attr('data-expire')).fadeOut(500);
  });
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
// Sign in form loading
function resetSubmitButton() {
  var btnhtml = $('button[type="submit"].submit-active').attr('data-html');
  $('button[type="submit"].submit-active').html(btnhtml);
  $('button[type="submit"].submit-active').attr('data-html', '');
  $('button[type="submit"].submit-active').removeClass('submit-active');
  $(this).closest('form').removeClass('submit-active');
}
$('form input').click(function(){
  if ($(this).closest('form').hasClass('submit-active')) {
    resetSubmitButton();
  }
});
$('button[type="submit"]').click(function(){
  $(this).attr('data-html', $(this).html());
  $(this).html('<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>');
  $(this).addClass('submit-active');
  $(this).parent('form').addClass('submit-active');
  setTimeout(
  function() {
    resetSubmitButton();
  }, 5000);
});

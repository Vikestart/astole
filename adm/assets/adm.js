// Automatically remove error/success messages after a few seconds
$(document).ready(function() {
  $('nav > ol').addClass('nav-mobile-animated');
  $('.msgbox').each(function(i, obj) {
    $(this).delay($(this).attr('data-expire')).fadeOut(500);
  });

  // New code for password generation
  const passwordField = document.getElementById('user_pass_field');
  const generatePassBtn = document.getElementById('generate_pass_btn');

  if (generatePassBtn && passwordField) {
      generatePassBtn.addEventListener('click', function() {
          passwordField.value = generateStrongPassword(15); // Generate a 12-character password
          // Optionally, change type to text temporarily to show password
          passwordField.type = 'text';
          // You might want to add a way to toggle it back to 'password' type
      });
  }

  function generateStrongPassword(length) {
    const lower = "abcdefghijklmnopqrstuvwxyz";
    const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const symbols = "!@#$%^&*()_+~`|}{[]:;?><,./-=";
    const allChars = lower + upper + numbers + symbols;

    let password = "";
    
    // Ensure at least one of each character type for strength
    password += lower.charAt(Math.floor(Math.random() * lower.length));
    password += upper.charAt(Math.floor(Math.random() * upper.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
    password += symbols.charAt(Math.floor(Math.random() * symbols.length));

    // Fill the rest of the password length with random characters
    for (let i = password.length; i < length; i++) {
        password += allChars.charAt(Math.floor(Math.random() * allChars.length));
    }

    // Shuffle the password to ensure the required characters are not always at the beginning
    return password.split('').sort(() => 0.5 - Math.random()).join('');
  }

  $('#deleteUserBtn').on('click', function(e) {
    e.preventDefault(); // Prevent the default link behavior

    var userId = $(this).data('user-id');
    var confirmDelete = confirm("Are you sure you want to delete this user? This action cannot be undone.");

    if (confirmDelete) {
      window.location.href = "process-user.php?a=del&u=" + userId;
    }
  });

}); // This closes the main $(document).ready() function that wraps the initial parts of the script.

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

// Button handling
$('.bth').click(function(){
  $(this).blur();
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
  $(this).blur();
});
(function ($, Drupal) {
  $('#reserves-more').click(function () {
    $('#reserves li:hidden').slice(0, 250).show();
      if ($('#reserves li').length == $('#reserves li:visible').length) {
        $('#reserves-more').hide();
        $('#reserves-less').show();
      }
  });
  $('#reserves-less').click(function () {
    $('#reserves li').slice(10, $('#reserves li').length).hide();
    $('#reserves-more').show();
    $('#reserves-less').hide();
  });
})(jQuery, Drupal);
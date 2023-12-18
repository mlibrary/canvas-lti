(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.fieldShowAllLoader = {
        attach: function (context) {
            $('.field-show-all-link', context).each(function() {
                var id = $(this).attr('id');
                var active = 1;
                $('#'+id, context).bind('click', function(e) {
                    var $parentFieldWrapper = $(this).parents('.field-show-all');
                    var parentWrapperClass = $(this).attr('data-field-class');
                    var itemLimit = drupalSettings.field_show_all[parentWrapperClass].limit;
                    var linkText = drupalSettings.field_show_all[parentWrapperClass].link_text;
                    var linkTextClose = drupalSettings.field_show_all[parentWrapperClass].link_text_close;
                    if (active) {
                        active = 0;
                        $('.field__items .element-invisible', $parentFieldWrapper).removeClass('element-invisible');
                        e.target.textContent = linkTextClose;
                    }
                    else {
                        active = 1;
                        $('.field__item', $parentFieldWrapper).addClass('element-invisible');
                        var count = 0;
                        $('.field__items .field__item', $parentFieldWrapper).each(function() {
                            if (count < itemLimit) {
                                $(this).removeClass('element-invisible');
                            }
                            else {
                                return false;
                            }
                            count++;
                        });
                        e.target.textContent = linkText;
                    }
                });
            });
        }
    }
}(jQuery, Drupal, drupalSettings));
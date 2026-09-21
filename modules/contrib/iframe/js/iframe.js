/**
 * @file
 * See Issue "autoresize" https://www.drupal.org/node/360549.
 */
(function ($) {
  Drupal.behaviors.iframeModule = {
    attach(context, settings) {
      $('iframe.autoresize').each(function () {
        const offsetHeight = 20;
        const thisIframe = $(this);
        let iframeWaitInterval;
        let elem;
        let msg = '';

        function resizeHeight(iframe) {
          if ($(iframe).length) {
            /* Iframe yet loaded ? */
            const iframeDoc =
              $(iframe)[0].contentDocument ||
              $(iframe)[0].contentWindow.document;
            let contentheight = 0;

            try {
              contentheight = $(iframeDoc).find('body').height();
            } catch (e) {
              elem = $(iframe)[0];
              msg = $(
                `<p><small>(${Drupal.t(
                  'Iframe URL is not from the same domain - autoresize not working.',
                )})</small></p>`,
              );
              $(elem).after(msg);
              clearInterval(iframeWaitInterval);
            }

            if (contentheight > 0) {
              clearInterval(iframeWaitInterval);
              try {
                const frameElement = $(iframe)[0].frameElement || $(iframe)[0];
                frameElement.style.height = `${contentheight + offsetHeight}px`;
                frameElement.scrolling = 'no';
              } catch (e) {
                /* here, ist not a correctable error */
              }
            }
          }
        }
        const delayedResize = function () {
          resizeHeight(thisIframe);
        };

        iframeWaitInterval = setInterval(delayedResize, 300);
      });
    },
  };
})(jQuery);

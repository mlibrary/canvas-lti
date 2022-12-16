<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Allow storage from an LTI.
 */
class AllowStorage extends ControllerBase {

  /**
   * Returns a form to allow site interaction on launch if needed.
  */
  public function content(Request $request) {
    $lti_context = $request->getSession()->get('lti_tool_provider_context');
    if ($lti_context) {
      //TODO: Finding the return_url below and comparing to platform_redirect_url (is that canvas only?) may be unecessary
      $payload = $lti_context->getPayload();
      $token = $payload->getToken();
      $claims = $token->getClaims();
      //TODO: launch_presentation appears to be optional in lti specs, but we may want to verify the return_url is the lti provider. Use something else?
      $launch_presentation = $claims->get('https://purl.imsglobal.org/spec/lti/claim/launch_presentation');
      $return_url = $launch_presentation['return_url'];

      if ($return_url) {
        $platform_redirect_url = $request->query->get('platform_redirect_url');
        if (!empty($platform_redirect_url) && strpos($platform_redirect_url, $return_url) !== FALSE) {
          $markup = new FormattableMarkup('<p>By clicking the button below, you will return to<br/>@redirect<br/>and allow session storage for<br/>@site</p><div id="allow-storage-js-button"></div>',
            [
              '@redirect' => $platform_redirect_url,
              '@site' => $request->getHost(),
            ]
          );
          $settings = [
            'redirect' => $platform_redirect_url,
          ];
          $build['#attached']['library'][] = 'lti_tool_provider/allow_storage_full';
          $build['#attached']['drupalSettings']['ltiToolProvider']['allowStorageFull'] = $settings;
          $build['#markup'] = $markup;

          return $build;
        }
      }
    }

    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
  }

}


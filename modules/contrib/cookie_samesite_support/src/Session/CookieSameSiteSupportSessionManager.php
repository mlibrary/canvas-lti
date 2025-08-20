<?php

namespace Drupal\cookie_samesite_support\Session;

use Drupal\Core\Session\SessionManager;
use Symfony\Component\HttpFoundation\Session\SessionUtils;

/**
 * Class CookieSameSiteSupportSessionManager.
 *
 * We override the save method to do custom actions post the session cookie is
 * set. Changes around how the browsers handle cookies when redirecting back
 * from another sites (like payment gateways) has forced to add hacks like
 * below. We set the cookie with SameSite=None for Secure ones
 * and we add them twice to support both new and old browsers.
 *
 * @see https://web.dev/samesite-cookie-recipes/#handling-incompatible-clients
 */
class CookieSameSiteSupportSessionManager extends SessionManager {

  /**
   * Suffix to use for legacy cookie name.
   */
  const LEGACY_SUFFIX = '-legacy';

  /**
   * {@inheritdoc}
   */
  public function save(): void {
    parent::save();

    $name = $this->getName();
    $original = SessionUtils::popSessionCookie($name, $this->getId());
    if ($original) {
      if (stripos($original, 'SameSite') === FALSE) {
        $original .= '; SameSite=None';
      }

      // Add the original cookie as per new browser expectations back.
      header($original, FALSE);

      // Add the legacy cookie.
      $legacy = str_replace($name, $name . self::LEGACY_SUFFIX, $original);
      $legacy = str_ireplace('; SameSite=None', '', $legacy);
      header($legacy, FALSE);
    }
  }

}

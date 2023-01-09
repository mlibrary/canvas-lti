<?php

namespace Drupal\cookie_samesite_support\Session;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\SessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the default session configuration generator.
 */
class CookieSameSiteSupportSessionConfiguration extends SessionConfiguration {

  /**
   * DateTime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * CookieSameSiteSupportSessionConfiguration constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   DateTime service.
   * @param array $options
   *   Options from parameters.
   */
  public function __construct(TimeInterface $time, array $options = []) {
    parent::__construct($options);
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(Request $request) {
    $this->setCookieFromLegacy($request);
    return parent::getName($request);
  }

  /**
   * Wrapper function to set original cookies from legacy cookies.
   *
   * Here we try to set the legacy cookies we set in
   * CookieSameSiteSupportSessionManager in original expected keys if for some
   * reason they are not available. We do this here as this is invoked before
   * starting the session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  protected function setCookieFromLegacy(Request $request) {
    static $processed = NULL;

    if ($processed) {
      return;
    }

    // Set the static variable first to ensure we don't call this function
    // recursively.
    $processed = TRUE;

    $cookies = $request->cookies->all();
    foreach ($cookies as $name => $value) {
      if (strpos($name, CookieSameSiteSupportSessionManager::LEGACY_SUFFIX) !== FALSE) {
        $expected = str_replace(CookieSameSiteSupportSessionManager::LEGACY_SUFFIX, '', $name);
        if (empty($cookies[$expected])) {
          $_COOKIE[$expected] = $value;
          $request->cookies->set($expected, $value);

          $options = $this->getOptions($request);

          // Set the cookie back so we have the original cookie back again.
          // If the user upgrades the browser and tries to checkout without
          // original cookie, we will face the same 500.
          $params = session_get_cookie_params();
          $expire = $params['lifetime']
            ? $this->time->getRequestTime() + $params['lifetime']
            : 0;

          if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            setcookie($expected, $value, [
              'expires' => $expire,
              'path' => $params['path'],
              'domain' => $options['cookie_domain'],
              'samesite' => 'None',
              'secure' => TRUE,
              'httponly' => $params['httponly'],
            ]);
          }
          else {
            setcookie($expected, $value, $expire, $params['path'], $options['cookie_domain'] . '; SameSite=None', TRUE, $params['httponly']);
          }
        }
      }
    }
  }

}

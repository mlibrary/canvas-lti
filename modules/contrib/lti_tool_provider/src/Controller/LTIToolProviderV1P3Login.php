<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\lti_tool_provider\Authentication\Provider\LTIToolProviderV1P3;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcInitiator;
use OAT\Library\Lti1p3Core\Security\Oidc\Server\OidcInitiationRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for lti_tool_provider module routes.
 */
class LTIToolProviderV1P3Login extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \Exception
   */
  public function route(Request $request): ResponseInterface {
    $registrationRepository = Drupal::service('lti_tool_provider.registration.repository');
    if (!($registrationRepository instanceof RegistrationRepositoryInterface)) {
      throw new Exception('Unable to retrieve registration service.');
    }

    $psr17Factory = new Psr17Factory();
    $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $psrRequest = $psrHttpFactory->createRequest($request);
    $handler = new OidcInitiationRequestHandler(new OidcInitiator($registrationRepository), NULL, $this->getLogger('lti_tool_provider'));
    return $handler->handle($psrRequest);
  }

  /**
   * Checks access for LTI routes.
   *
   * @return AccessResult
   *   The access result.
   */
  public function access(): AccessResult {
    $request = Drupal::request();
    return AccessResult::allowedIf(LTIToolProviderV1P3::isValidLoginRequest($request));
  }

}

<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\lti_tool_provider\Authentication\Provider\LTIToolProviderV1P3;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Exception;
use Nyholm\Psr7\Response;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\JwksExporter;
use OAT\Library\Lti1p3Core\Security\Jwks\Server\JwksRequestHandler;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepository;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for lti_tool_provider module routes.
 */
class LTIToolProviderV1P3Jwks extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function route(Request $request): ResponseInterface {
    try {
      $client_id = $request->request->get('client_id') ?: $request->get('client_id');

      $registrationRepository = Drupal::service('lti_tool_provider.registration.repository');
      if (!($registrationRepository instanceof RegistrationRepositoryInterface)) {
        throw new Exception('Registration repository missing.');
      }

      $registration = $registrationRepository->findByClientId($client_id);
      if (is_null($registration)) {
        throw new Exception("Missing registration for the client ID: $client_id.");
      }

      $toolChain = $registration->getToolKeyChain();
      $keyChainRepository = new KeyChainRepository([$toolChain]);

      $handler = new JwksRequestHandler(new JwksExporter($keyChainRepository));

      return $handler->handle($toolChain->getKeySetName());
    }
    catch (Exception $e) {
      $this->getLogger('lti_tool_provider')->warning($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage());
      return new Response(500);
    }
  }

  /**
   * Checks access for LTI routes.
   *
   * @return AccessResult
   *   The access result.
   */
  public function access(): AccessResult {
    $request = Drupal::request();
    return AccessResult::allowedIf(LTIToolProviderV1P3::isValidJwksRequest($request));
  }

}

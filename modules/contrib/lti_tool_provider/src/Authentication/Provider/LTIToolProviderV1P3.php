<?php

namespace Drupal\lti_tool_provider\Authentication\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Exception;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class LTIToolProviderV1P3 extends LTIToolProviderBase {

  /**
   * @var \OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface
   */
  private $registrationRepository;

  /**
   * @var \OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface
   */
  private $nonceRepository;

  /**
   * LTIToolProviderV1P3 constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface $nonceRepository
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface $registrationRepository
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    EventDispatcherInterface $eventDispatcher,
    NonceRepositoryInterface $nonceRepository,
    RegistrationRepositoryInterface $registrationRepository
  ) {
    parent::__construct($config_factory, $entity_type_manager, $logger_factory, $eventDispatcher);
    $this->nonceRepository = $nonceRepository;
    $this->registrationRepository = $registrationRepository;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  static function isValidLoginRequest(Request $request): bool {
    $iss = $request->request->get('iss') ?: $request->get('iss');
    $target_link_uri = $request->request->get('target_link_uri') ?: $request->get('target_link_uri');
    $login_hint = $request->request->get('login_hint') ?: $request->get('login_hint');
    $lti_message_hint = $request->request->get('lti_message_hint') ?: $request->get('lti_message_hint');

    $is_iss = is_string($iss) && strlen($iss) > 0;
    $is_target_link_uri = is_string($target_link_uri) && strlen($target_link_uri) > 0;
    $is_login_hint = is_string($login_hint) && strlen($login_hint) > 0;
    $is_lti_message_hint = is_string($lti_message_hint) && strlen($lti_message_hint) > 0;

    return $is_iss && $is_login_hint && $is_target_link_uri && $is_lti_message_hint;
  }

  /**
   * @param $request
   *
   * @return bool
   */
  static function isValidJwksRequest($request): bool {
    $client_id = $request->request->get('client_id') ?: $request->get('client_id');

    return is_string($client_id) && strlen($client_id) > 0;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   */
  public function applies(Request $request): bool {
    return LTIToolProviderV1P3::isValidLaunchRequest($request);
  }

  /**
   * @param $request
   *
   * @return bool
   */
  static function isValidLaunchRequest($request): bool {
    $id_token = $request->request->get('id_token') ?: $request->get('id_token');
    $state = $request->request->get('state') ?: $request->get('state');
    $is_id_token = is_string($id_token) && strlen($id_token) > 0;
    $is_state = is_string($state) && strlen($state) > 0;

    if ($is_id_token and $is_state) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *
   * @return \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   * @throws \Exception
   */
  function validate(ServerRequestInterface $request): LTIToolProviderContextInterface {
    $validator = new ToolLaunchValidator($this->registrationRepository, $this->nonceRepository);
    $result = $validator->validatePlatformOriginatingLaunch($request);

    if ($result->hasError()) {
      throw new Exception('Unable to validate platform launch.');
    }

    $payload = $result->getPayload();
    $registration = $result->getRegistration();
    $userIdentity = $payload->getUserIdentity();

    return new LTIToolProviderContext($userIdentity, $payload, $registration);
  }

}

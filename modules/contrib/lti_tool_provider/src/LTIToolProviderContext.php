<?php

namespace Drupal\lti_tool_provider;

use Drupal\Core\Url;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LTIToolProviderContext implements LTIToolProviderContextInterface {

  /**
   * @var string
   */
  private $version;

  /**
   * @var array
   */
  private $context;

  /**
   * @var \OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface
   */
  private $payload;

  /**
   * @var \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  private $registration;

  /**
   * @var \OAT\Library\Lti1p3Core\User\UserIdentity
   */
  private $userIdentity;

  /**
   * LTIToolProviderContext constructor.
   *
   * @param \OAT\Library\Lti1p3Core\User\UserIdentity $userIdentity
   * @param mixed $payload
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null $registration
   */
  public function __construct(UserIdentity $userIdentity, $payload, RegistrationInterface $registration = NULL) {
    if ($payload instanceof LtiMessagePayloadInterface && $registration instanceof RegistrationInterface) {
      $this->setRegistration($registration);
      $this->setPayload($payload);
      $this->setUserIdentity($userIdentity);
      $this->setVersion(LTIToolProviderContextInterface::V1P3);
    }
    else {
      $this->setContext($payload);
      $this->setVersion(LTIToolProviderContextInterface::V1P0);
      $this->setUserIdentity($userIdentity);
    }
  }

  static function sendError(string $message, ?LTIToolProviderContextInterface $context = NULL): void {
    if ($context instanceof LTIToolProviderContextInterface) {
      if ($context->getVersion() === LTIToolProviderContextInterface::V1P0) {
        $context_data = $context->getContext();
        if (isset($context_data['launch_presentation_return_url']) && !empty($context_data['launch_presentation_return_url'])) {
          $uri = $context_data['launch_presentation_return_url'];
        }
      }
      if ($context->getVersion() === LTIToolProviderContextInterface::V1P3) {
        $context_data = $context->getPayload();
        if (!empty($context_data->getLaunchPresentation()->getReturnUrl())) {
          $uri = $context_data->getLaunchPresentation()->getReturnUrl();
        }
      }
    }
    else {
      if (isset($_REQUEST['launch_presentation_return_url']) && !empty($_REQUEST['launch_presentation_return_url'])) {
        $uri = $_REQUEST['launch_presentation_return_url'];
      }
    }

    if (!isset($uri)) {
      return;
    }

    $url = Url::fromUri($uri)
      ->setOption('query', ['lti_errormsg' => $message,])
      ->setAbsolute(TRUE)
      ->toString();

    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * @return array
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * @param array $context
   */
  public function setContext(array $context): void {
    $this->context = $context;
  }

  /**
   * @return \OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface
   */
  public function getPayload(): LtiMessagePayloadInterface {
    return $this->payload;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface $payload
   */
  public function setPayload(LtiMessagePayloadInterface $payload): void {
    $this->payload = $payload;
  }

  /**
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  public function getRegistration(): RegistrationInterface {
    return $this->registration;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null $registration
   */
  public function setRegistration(?RegistrationInterface $registration): void {
    $this->registration = $registration;
  }

  /**
   * @return \OAT\Library\Lti1p3Core\User\UserIdentity
   */
  public function getUserIdentity(): UserIdentity {
    return $this->userIdentity;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\User\UserIdentity $userIdentity
   */
  public function setUserIdentity(UserIdentity $userIdentity): void {
    $this->userIdentity = $userIdentity;
  }

  /**
   * @return string
   */
  public function getVersion(): string {
    return $this->version;
  }

  /**
   * @param string $version
   */
  public function setVersion(string $version): void {
    $this->version = $version;
  }

}

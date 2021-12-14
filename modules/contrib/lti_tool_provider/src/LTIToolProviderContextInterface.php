<?php

namespace Drupal\lti_tool_provider;

use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

interface LTIToolProviderContextInterface {

  const V1P0 = 'V1P0';

  const V1P3 = 'V1P3';

  /**
   * @return array
   */
  public function getContext(): array;

  /**
   * @param array $context
   */
  public function setContext(array $context): void;

  /**
   * @return \OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface
   */
  public function getPayload(): LtiMessagePayloadInterface;

  /**
   * @param \OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface $payload
   */
  public function setPayload(LtiMessagePayloadInterface $payload): void;

  /**
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface
   */
  public function getRegistration(): RegistrationInterface;

  /**
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationInterface $registration
   */
  public function setRegistration(RegistrationInterface $registration): void;

  /**
   * @return \OAT\Library\Lti1p3Core\User\UserIdentity
   */
  public function getUserIdentity(): UserIdentity;

  /**
   * @param \OAT\Library\Lti1p3Core\User\UserIdentity $userIdentity
   */
  public function setUserIdentity(UserIdentity $userIdentity): void;

  /**
   * @return string
   */
  public function getVersion(): string;

  /**
   * @param string $version
   */
  public function setVersion(string $version): void;

}

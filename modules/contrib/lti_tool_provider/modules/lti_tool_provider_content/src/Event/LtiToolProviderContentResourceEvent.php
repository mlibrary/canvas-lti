<?php

namespace Drupal\lti_tool_provider_content\Event;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Drupal\Component\EventDispatcher\Event;

class LtiToolProviderContentResourceEvent extends Event {

  /**
   * @var array
   */
  private $properties;

  /**
   * @var \OAT\Library\Lti1p3Core\Registration\RegistrationInterface
   */
  private $registration;

  /**
   * @var string
   */
  private $return;

  /**
   * LtiToolProviderContentResourceEvent constructor.
   *
   * @param array $properties
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationInterface $registration
   * @param string $return
   */
  public function __construct(array $properties, RegistrationInterface $registration, string $return) {
    $this->setProperties($properties);
    $this->setRegistration($registration);
    $this->setReturn($return);
  }

  /**
   * @return array
   */
  public function getProperties(): array {
    return $this->properties;
  }

  /**
   * @param array $properties
   */
  public function setProperties(array $properties): void {
    $this->properties = $properties;
  }

  /**
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface
   */
  public function getRegistration(): RegistrationInterface {
    return $this->registration;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\Registration\RegistrationInterface $registration
   */
  public function setRegistration(RegistrationInterface $registration): void {
    $this->registration = $registration;
  }

  /**
   * @return string
   */
  public function getReturn(): string {
    return $this->return;
  }

  /**
   * @param string $return
   */
  public function setReturn(string $return): void {
    $this->return = $return;
  }

}

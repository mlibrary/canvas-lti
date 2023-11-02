<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\Component\EventDispatcher\Event;

class LtiToolProviderReturnEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var string
   */
  private $destination;

  /**
   * LtiToolProviderReturnEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param string $destination
   */
  public function __construct(LTIToolProviderContextInterface $context, string $destination) {
    $this->setContext($context);
    $this->setDestination($destination);
  }

  /**
   * @return \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  public function getContext(): LTIToolProviderContextInterface {
    return $this->context;
  }

  /**
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   */
  public function setContext(LTIToolProviderContextInterface $context) {
    $this->context = $context;
  }

  /**
   * @return string
   */
  public function getDestination(): string {
    return $this->destination;
  }

  /**
   * @param string $destination
   */
  public function setDestination(string $destination): void {
    $this->destination = $destination;
  }

}

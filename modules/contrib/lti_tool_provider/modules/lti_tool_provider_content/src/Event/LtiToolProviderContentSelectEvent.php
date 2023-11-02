<?php

namespace Drupal\lti_tool_provider_content\Event;

use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\Component\EventDispatcher\Event;

class LtiToolProviderContentSelectEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var string
   */
  private $destination;

  /**
   * @var string
   */
  private $return;

  /**
   * LtiToolProviderContentSelectEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param string $destination
   * @param string $return
   */
  public function __construct(LTIToolProviderContextInterface $context, string $destination, string $return) {
    $this->setContext($context);
    $this->setDestination($destination);
    $this->setReturn($return);
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

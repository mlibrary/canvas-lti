<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class LtiToolProviderCreateUserEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * LtiToolProviderProvisionUserEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param \Drupal\user\UserInterface $user
   */
  public function __construct(LTIToolProviderContextInterface $context, UserInterface $user) {
    $this->setContext($context);
    $this->setUser($user);
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
  private function setContext(LTIToolProviderContextInterface $context) {
    $this->context = $context;
  }

  /**
   * @return \Drupal\user\UserInterface
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   */
  public function setUser(UserInterface $user): void {
    $this->user = $user;
  }

}

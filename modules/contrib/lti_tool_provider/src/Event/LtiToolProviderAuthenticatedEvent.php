<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\user\UserInterface;
use Drupal\Component\EventDispatcher\Event;

class LtiToolProviderAuthenticatedEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var UserInterface
   */
  private $user;

  /**
   * LtiToolProviderAuthenticatedEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param UserInterface $user
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
  public function setContext(LTIToolProviderContextInterface $context) {
    $this->context = $context;
  }

  /**
   * @return UserInterface
   */
  public function getUser(): UserInterface {
    return $this->user;
  }

  /**
   * @param UserInterface $user
   */
  public function setUser(UserInterface $user): void {
    $this->user = $user;
  }

}

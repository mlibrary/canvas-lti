<?php

namespace Drupal\lti_tool_provider_content\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Symfony\Component\EventDispatcher\Event;

class LtiToolProviderContentLaunchEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var string
   */
  private $destination;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * LtiToolProviderContentLaunchEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param string $destination
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(LTIToolProviderContextInterface $context, string $destination, EntityInterface $entity) {
    $this->setContext($context);
    $this->setDestination($destination);
    $this->setEntity($entity);
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
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function setEntity(EntityInterface $entity): void {
    $this->entity = $entity;
  }

}

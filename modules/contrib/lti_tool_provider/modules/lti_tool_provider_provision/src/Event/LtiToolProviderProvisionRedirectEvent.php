<?php

namespace Drupal\lti_tool_provider_provision\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Implementation LtiToolProviderProvisionRedirectEvent class.
 */
class LtiToolProviderProvisionRedirectEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * @var string
   */
  private $destination;

  /**
   * LtiToolProviderProvisionRedirectEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $destination
   */
  public function __construct(LTIToolProviderContextInterface $context, EntityInterface $entity, string $destination) {
    $this->setContext($context);
    $this->setEntity($entity);
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

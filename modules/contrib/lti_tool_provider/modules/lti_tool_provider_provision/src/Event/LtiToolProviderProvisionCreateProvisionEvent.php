<?php

namespace Drupal\lti_tool_provider_provision\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Implementation LtiToolProviderProvisionCreateProvisionEvent class.
 */
class LtiToolProviderProvisionCreateProvisionEvent extends Event {

  /**
   * @var \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   */
  private $context;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * LtiToolProviderProvisionCreateProvisionEvent constructor.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(LTIToolProviderContextInterface $context, EntityInterface $entity) {
    $this->setContext($context);
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

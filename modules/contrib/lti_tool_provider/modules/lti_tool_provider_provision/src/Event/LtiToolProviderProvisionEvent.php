<?php

namespace Drupal\lti_tool_provider_provision\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lti_tool_provider\LtiToolProviderEvent;

class LtiToolProviderProvisionEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_PROVISION_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * @var EntityInterface
     */
    private $entity;

    /**
     * @var string
     */
    private $destination;

    /**
     * LtiToolProviderProvisionEvent constructor.
     * @param array $context
     * @param EntityInterface $entity
     * @param string $destination
     */
    public function __construct(array $context, EntityInterface $entity, string $destination)
    {
        $this->setContext($context);
        $this->setEntity($entity);
        $this->setDestination($destination);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /**
     * @return EntityInterface
     */
    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }

    /**
     * @param EntityInterface $entity
     */
    public function setEntity(EntityInterface $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }
}

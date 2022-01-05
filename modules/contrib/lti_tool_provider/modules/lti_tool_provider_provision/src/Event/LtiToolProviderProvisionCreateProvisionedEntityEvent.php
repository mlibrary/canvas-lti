<?php

namespace Drupal\lti_tool_provider_provision\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\lti_tool_provider\LtiToolProviderEvent;

class LtiToolProviderProvisionCreateProvisionedEntityEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_PROVISION_CREATE_PROVISIONED_ENTITY_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * @var EntityInterface
     */
    private $entity;

    /**
     * LtiToolProviderProvisionCreateProvisionedEntityEvent constructor.
     * @param array $context
     * @param EntityInterface $entity
     */
    public function __construct(array $context, EntityInterface $entity)
    {
        $this->setContext($context);
        $this->setEntity($entity);
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
}

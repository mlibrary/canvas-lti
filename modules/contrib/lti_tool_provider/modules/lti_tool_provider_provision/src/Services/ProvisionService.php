<?php

namespace Drupal\lti_tool_provider_provision\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\lti_tool_provider_provision\Entity\LtiToolProviderProvision;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionedEntityEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionSyncProvisionedEntityEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProvisionService
{
    /**
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ImmutableConfig
     */
    private $config;

    /**
     * ProvisionService constructor.
     * @param ConfigFactoryInterface $configFactory
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        EntityTypeManagerInterface $entityTypeManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configFactory = $configFactory;
        $this->entityTypeManager = $entityTypeManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $configFactory->get('lti_tool_provider_provision.settings');
    }

    /**
     * @param array $context
     * @return EntityInterface
     * @throws Exception
     */
    public function provision(array $context): EntityInterface
    {
        $entityType = $this->config->get('entity_type');
        $entityBundle = $this->config->get('entity_bundle');

        if ($entityType && $entityBundle && isset($context['consumer_id']) && !empty($context['consumer_id']) && isset($context['context_id']) && !empty($context['context_id']) && isset($context['resource_link_id']) && !empty($context['resource_link_id'])) {
            $provision = $this->getProvisionFromContext($context);

            if (!$provision) {
                $provision = LtiToolProviderProvision::create();

                if ($provision instanceof LtiToolProviderProvision) {
                    $provision->set('consumer_id', $context['consumer_id']);
                    $provision->set('context_id', $context['context_id']);
                    $provision->set('context_label', $context['context_label']);
                    $provision->set('context_title', $context['context_title']);
                    $provision->set('resource_link_id', $context['resource_link_id']);
                    $provision->set('resource_link_title', $context['resource_link_title']);
                    $provision->set('provision_type', $entityType);
                    $provision->set('provision_bundle', $entityBundle);
                }

                $event = new LtiToolProviderProvisionCreateProvisionEvent($context, $provision);
                LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $event);

                if ($event->isCancelled()) {
                    throw new Exception($event->getMessage());
                }

                $provision = $event->getEntity();
                $provision->save();
            }

            if ($provision instanceof LtiToolProviderProvision) {
                $entity = $provision->get('provision_id')->value ? $this->entityTypeManager->getStorage($provision->get('provision_type')->value)->load($provision->get('provision_id')->value) : null;

                if (!$entity) {
                    $entity = $this->createProvisionedEntity($context, $provision);
                }

                $entity = $this->syncProvisionedEntity($context, $entity);

                $entity->save();
                $provision->set('provision_id', $entity->id());
                $provision->save();

                return $entity;
            }
        }

        throw new Exception('Unable to provision entity.');
    }

    /**
     * @param array $context
     * @param EntityInterface|LtiToolProviderProvision $provision
     * @return EntityInterface
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     * @throws Exception
     */
    public function createProvisionedEntity(array $context, EntityInterface $provision): EntityInterface
    {
        $entityType = $provision->get('provision_type')->value;
        $entityBundle = $provision->get('provision_bundle')->value;

        $bundleType = $this->entityTypeManager->getDefinition($entityType)->getKey('bundle');
        $entity = $this->entityTypeManager->getStorage($entityType)->create([$bundleType => $entityBundle]);

        $event = new LtiToolProviderProvisionCreateProvisionedEntityEvent($context, $entity);
        LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $event);

        if ($event->isCancelled()) {
            throw new Exception($event->getMessage());
        }

        $entity = $event->getEntity();

        return $entity;
    }

    /**
     * @param array $context
     * @param EntityInterface $entity
     * @return EntityInterface
     * @throws Exception
     */
    public function syncProvisionedEntity(array $context, EntityInterface $entity): EntityInterface
    {
        $entityDefaults = $this->config->get('entity_defaults');

        if ($entityDefaults) {
            foreach ($entityDefaults as $name => $entityDefault) {
                if ($entity instanceof ContentEntityBase && isset($context[$entityDefault]) && !empty($context[$entityDefault])) {
                    $entity->set($name, $context[$entityDefault]);
                }
            }
        }

        $event = new LtiToolProviderProvisionSyncProvisionedEntityEvent($context, $entity);
        LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $event);

        if ($event->isCancelled()) {
            throw new Exception($event->getMessage());
        }

        $entity = $event->getEntity();

        return $entity;
    }

    /**
     * @param $context
     * @return EntityInterface|null
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function getProvisionFromContext($context): ?EntityInterface
    {
        $provision = $this->entityTypeManager->getStorage('lti_tool_provider_provision')
            ->loadByProperties(
                [
                    'consumer_id' => $context['consumer_id'],
                    'context_id' => $context['context_id'],
                    'resource_link_id' => $context['resource_link_id'],
                ]
            );

        if (count($provision)) {
            return reset($provision);
        }

        return null;
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface|null
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function getProvisionFromEntity(EntityInterface $entity): ?EntityInterface
    {
        $provision = $this->entityTypeManager->getStorage('lti_tool_provider_provision')
            ->loadByProperties(
                [
                    'provision_type' => $entity->getEntityTypeId(),
                    'provision_bundle' => $entity->bundle(),
                    'provision_id' => $entity->id(),
                ]
            );

        if (count($provision)) {
            return reset($provision);
        }

        return null;
    }
}

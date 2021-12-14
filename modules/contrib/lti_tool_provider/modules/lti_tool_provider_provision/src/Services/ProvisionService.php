<?php

namespace Drupal\lti_tool_provider_provision\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_provision\Entity\LtiToolProviderProvision;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionedEntityEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionEvents;
use Exception;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implementation ProvisionService class.
 *
 * @package Drupal\lti_tool_provider_provision\Services
 */
class ProvisionService {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * ProvisionService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
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
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @throws \Exception
   */
  public function provision(LTIToolProviderContextInterface $context): ?EntityInterface {
    if ($context->getVersion() === LTIToolProviderContextInterface::V1P0) {
      $context_data = $context->getContext();
      $consumer_id = $context_data['consumer_id'];
      $context_id = $context_data['context_id'];
      $context_label = $context_data['context_label'];
      $context_title = $context_data['context_title'];
      $resource_link_id = $context_data['resource_link_id'];
      $resource_link_title = $context_data['resource_link_title'];
      $entityType = $this->config->get('v1p0_entity_type');
      $entityBundle = $this->config->get('v1p0_entity_bundle');
    }
    else {
      $audience = $context->getPayload()->getClaim('aud');
      $consumer_id = $audience[0];
      $context_id = $context->getPayload()->getContext()->getIdentifier();
      $context_label = $context->getPayload()->getContext()->getLabel();
      $context_title = $context->getPayload()->getContext()->getTitle();
      $resource_link_id = $context->getPayload()
        ->getResourceLink()
        ->getIdentifier();
      $resource_link_title = $context->getPayload()
        ->getResourceLink()
        ->getTitle();
      $entityType = $this->config->get('v1p3_entity_type');
      $entityBundle = $this->config->get('v1p3_entity_bundle');
    }

    if ($entityType && $entityBundle && isset($consumer_id) && !empty($consumer_id) && isset($context_id) && !empty($context_id) && isset($resource_link_id) && !empty($resource_link_id)) {
      $provision = $this->getProvisionFromContext($context);

      if (!$provision) {
        $provision = LtiToolProviderProvision::create();

        if ($provision instanceof LtiToolProviderProvision) {
          $provision->set('consumer_id', $consumer_id);
          $provision->set('context_id', $context_id);
          $provision->set('context_label', $context_label);
          $provision->set('context_title', $context_title);
          $provision->set('resource_link_id', $resource_link_id);
          $provision->set('resource_link_title', $resource_link_title);
          $provision->set('provision_type', $entityType);
          $provision->set('provision_bundle', $entityBundle);
        }

        $createProvisionEvent = new LtiToolProviderProvisionCreateProvisionEvent($context, $provision);
        $this->eventDispatcher->dispatch(LtiToolProviderProvisionEvents::CREATE_PROVISION, $createProvisionEvent);

        $provision = $createProvisionEvent->getEntity();
        $provision->save();
      }

      if ($provision instanceof LtiToolProviderProvision) {
        $entity = $provision->get('provision_id')->value ? $this->entityTypeManager
          ->getStorage($provision->get('provision_type')->value)
          ->load($provision->get('provision_id')->value) : NULL;

        if (!$entity) {
          $entity = $this->createProvisionedEntity($context, $provision);
        }

        $entity = $this->setEntityDefaults($context, $entity);

        $entity->save();
        $provision->set('provision_id', $entity->id());
        $provision->save();

        return $entity;
      }
    }

    return NULL;
  }

  /**
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getProvisionFromContext(LTIToolProviderContextInterface $context): ?EntityInterface {
    try {
      $lti_version = $context->getVersion();

      $consumer_id = '';
      $context_id = '';
      $resource_link_id = '';

      if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
        $context_data = $context->getContext();
        $consumer_id = $context_data['consumer_id'];
        $context_id = $context_data['context_id'];
        $resource_link_id = $context_data['resource_link_id'];
      }

      if (($lti_version === LTIToolProviderContextInterface::V1P3)) {
        $audience = $context->getPayload()->getClaim('aud');
        $contextClaim = $context->getPayload()->getContext();
        $resourceLinkClaim = $context->getPayload()->getResourceLink();

        if (!is_array($audience) || !($contextClaim instanceof ContextClaim) || !($resourceLinkClaim instanceof ResourceLinkClaim)) {
          throw new Exception('Missing LTI claims.');
        }

        $consumer_id = $audience[0];
        $context_id = $contextClaim->getIdentifier();
        $resource_link_id = $resourceLinkClaim->getIdentifier();
      }

      if (!is_string($consumer_id) || !is_string($consumer_id) || !is_string($resource_link_id)) {
        throw new Exception('Missing LTI identifiers.');
      }

      $provision = $this->entityTypeManager->getStorage('lti_tool_provider_provision')
        ->loadByProperties(
          [
            'consumer_id' => $consumer_id,
            'context_id' => $context_id,
            'resource_link_id' => $resource_link_id,
          ]
        );

      if (!count($provision)) {
        throw new Exception('No provision found.');
      }

      return reset($provision);
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param \Drupal\Core\Entity\EntityInterface|LtiToolProviderProvision $provision
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function createProvisionedEntity(LTIToolProviderContextInterface $context, EntityInterface $provision): EntityInterface {
    $entityType = $provision->get('provision_type')->value;
    $entityBundle = $provision->get('provision_bundle')->value;

    $bundleType = $this->entityTypeManager->getDefinition($entityType)
      ->getKey('bundle');
    $entity = $this->entityTypeManager->getStorage($entityType)
      ->create([$bundleType => $entityBundle]);

    $event = new LtiToolProviderProvisionCreateProvisionedEntityEvent($context, $entity);
    $this->eventDispatcher->dispatch(LtiToolProviderProvisionEvents::CREATE_ENTITY, $event);

    return $event->getEntity();
  }

  /**
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *
   * @throws \Exception
   */
  public function setEntityDefaults(LTIToolProviderContextInterface $context, EntityInterface $entity): EntityInterface {
    $entityDefaults = [];
    $lti_version = $context->getVersion();

    if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
      $entityDefaults = $this->config->get('v1p0_entity_defaults');
    }

    if (($lti_version === LTIToolProviderContextInterface::V1P3)) {
      $entityDefaults = $this->config->get('v1p3_entity_defaults');
    }

    if ($entityDefaults && $entity instanceof ContentEntityBase) {
      foreach ($entityDefaults as $name => $entityDefault) {
        if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
          $context_data = $context->getContext();
          if (isset($context_data[$entityDefault]) && !empty($context_data[$entityDefault])) {
            $entity->set($name, $context_data[$entityDefault]);
          }
        }
        if ($lti_version === LTIToolProviderContextInterface::V1P3) {
          $keys = array_map('trim', explode('-', $entityDefault));
          if (count($keys) === 1) {
            $claims_data = $context->getPayload()->getClaim($keys[0]);
          }
          else {
            $claims_data = $context->getPayload()->getToken()->getClaims()->all();
            foreach ($keys as $key) {
              if (isset($claims_data[$key])) {
                $claims_data = $claims_data[$key];
              }
            }
          }
          if (isset($claims_data) && !empty($claims_data)) {
            $entity->set($name, $claims_data);
          }
        }
      }
    }

    return $entity;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProvisionFromEntity(EntityInterface $entity): ?EntityInterface {
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

    return NULL;
  }

}

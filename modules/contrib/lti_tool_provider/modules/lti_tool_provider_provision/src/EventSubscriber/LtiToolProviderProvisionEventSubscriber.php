<?php

namespace Drupal\lti_tool_provider_provision\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderLaunchEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionEvents;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionRedirectEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionSyncProvisionedEntityEvent;
use Drupal\lti_tool_provider_provision\Services\ProvisionService;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implementation LtiToolProviderProvisionEventSubscriber class.
 *
 * @package Drupal\lti_tool_provider_provision\EventSubscriber
 */
class LtiToolProviderProvisionEventSubscriber implements EventSubscriberInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var ProvisionService
   */
  private $provisionService;

  /**
   * LtiToolProviderProvisionEventSubscriber constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param ProvisionService $provisionService
   * @param EventDispatcherInterface $eventDispatcher
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ProvisionService $provisionService,
    EventDispatcherInterface $eventDispatcher
  ) {
    $this->configFactory = $configFactory;
    $this->provisionService = $provisionService;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LtiToolProviderEvents::LAUNCH => 'onLaunch',
      LtiToolProviderProvisionEvents::CREATE_PROVISION => 'onCreateProvision',
    ];
  }

  /**
   * @param LtiToolProviderLaunchEvent $event
   */
  public function onLaunch(LtiToolProviderLaunchEvent $event) {
    $is_entity_sync = FALSE;
    $is_entity_redirect = FALSE;
    $context = $event->getContext();
    $lti_version = $context->getVersion();

    if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
      $is_entity_sync = $this->configFactory
        ->get('lti_tool_provider_provision.settings')->get('v1p0_entity_sync');
      $is_entity_redirect = $this->configFactory
        ->get('lti_tool_provider_provision.settings')
        ->get('v1p0_entity_redirect');
    }
    if ($lti_version === LTIToolProviderContextInterface::V1P3) {
      $is_entity_sync = $this->configFactory
        ->get('lti_tool_provider_provision.settings')->get('v1p3_entity_sync');
      $is_entity_redirect = $this->configFactory
        ->get('lti_tool_provider_provision.settings')
        ->get('v1p3_entity_redirect');
    }

    try {
      if ($entity = $this->provisionService->provision($context)) {
        if ($is_entity_sync) {
          $entity = $this->provisionService->setEntityDefaults($context, $entity);

          $syncProvisionedEntityEvent = new LtiToolProviderProvisionSyncProvisionedEntityEvent($context, $entity);
          $this->eventDispatcher->dispatch(LtiToolProviderProvisionEvents::SYNC_ENTITY, $syncProvisionedEntityEvent);

          $entity = $syncProvisionedEntityEvent->getEntity();
          $entity->save();
        }

        $url = $entity->toUrl()->toString();
        $redirectEvent = new LtiToolProviderProvisionRedirectEvent($context, $entity, $url);
        $this->eventDispatcher->dispatch(LtiToolProviderProvisionEvents::REDIRECT, $redirectEvent);

        if ($is_entity_redirect) {
          $event->setDestination($redirectEvent->getDestination());
        }
      }
    }
    catch (Exception $e) {
      Drupal::logger('lti_tool_provider_provision')->error($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context);
    }
  }

  /**
   * @param LtiToolProviderProvisionCreateProvisionEvent $event
   *
   * @throws \Exception
   */
  public function onCreateProvision(LtiToolProviderProvisionCreateProvisionEvent $event) {
    $context = $event->getContext();
    $lti_version = $context->getVersion();

    $access = TRUE;
    $ltiRoles = [];
    $allowedRoles = [];
    $is_allowed_roles_enabled = FALSE;

    if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
      $context_data = $context->getContext();
      $is_allowed_roles_enabled = $this->configFactory
        ->get('lti_tool_provider_provision.settings')
        ->get('v1p0_allowed_roles_enabled');
      $ltiRoles = parse_roles($context_data['roles']);
      $allowedRoles = $this->configFactory->get('lti_tool_provider_provision.settings')
        ->get('v1p0_allowed_roles');
    }

    if (($lti_version === LTIToolProviderContextInterface::V1P3)) {
      $is_allowed_roles_enabled = $this->configFactory
        ->get('lti_tool_provider_provision.settings')
        ->get('v1p3_allowed_roles_enabled');
      $ltiRoles = $context->getPayload()->getRoles();
      $allowedRoles = $this->configFactory->get('lti_tool_provider_provision.settings')
        ->get('v1p3_allowed_roles');
    }

    if ($is_allowed_roles_enabled) {
      $access = FALSE;
      foreach ($ltiRoles as $ltiRole) {
        $ltiRole = $lti_version === LTIToolProviderContextInterface::V1P3 ? encode_key($ltiRole) : $ltiRole;

        if (isset($allowedRoles[$ltiRole]) && $allowedRoles[$ltiRole]) {
          $access = TRUE;
          break;
        }
      }
    }

    if (!$access) {
      throw new Exception('Unable to provision entity.');
    }
  }

}

<?php

namespace Drupal\lti_tool_provider_roles\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderProvisionUserEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_roles\Event\LtiToolProviderRolesEvents;
use Drupal\lti_tool_provider_roles\Event\LtiToolProviderRolesProvisionEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implementation LtiToolProviderRolesEventSubscriber class.
 *
 * @package Drupal\lti_tool_provider_roles\EventSubscriber
 */
class LtiToolProviderRolesEventSubscriber implements EventSubscriberInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * LtiToolProviderRolesEventSubscriber constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param EventDispatcherInterface $eventDispatcher
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EventDispatcherInterface $eventDispatcher
  ) {
    $this->configFactory = $configFactory;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LtiToolProviderEvents::PROVISION_USER => 'onProvisionUser',
    ];
  }

  /**
   * @param \Drupal\lti_tool_provider\Event\LtiToolProviderProvisionUserEvent $event
   */
  public function onProvisionUser(LtiToolProviderProvisionUserEvent $event) {
    $context = $event->getContext();
    $lti_version = $context->getVersion();

    $mapped_roles = [];
    $lti_roles = [];
    if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
      $context_data = $context->getContext();
      $mapped_roles = Drupal::config('lti_tool_provider_roles.settings')
        ->get('v1p0_mapped_roles');
      $lti_roles = parse_roles($context_data['roles']);
    }
    if ($lti_version === LTIToolProviderContextInterface::V1P3) {
      $mapped_roles = Drupal::config('lti_tool_provider_roles.settings')
        ->get('v1p3_mapped_roles');
      $lti_roles = $context->getPayload()->getRoles();
    }

    $user = $event->getUser();
    $user_roles = user_roles(TRUE);

    if ($user->getDisplayName() === 'ltiuser') {
      return;
    }

    if (!$mapped_roles || !count($mapped_roles)) {
      return;
    }

    foreach ($mapped_roles as $user_role => $lti_role) {
      if (array_key_exists($user_role, $user_roles)) {
        if (in_array($lti_role, $lti_roles)) {
          $user->addRole($user_role);
        }
        else {
          $user->removeRole($user_role);
        }
      }
    }

    try {
      $rolesEvent = new LtiToolProviderRolesProvisionEvent($context, $user);
      $this->eventDispatcher->dispatch($rolesEvent, LtiToolProviderRolesEvents::PROVISION);
      $rolesEvent->getUser()->save();
    }
    catch (Exception $e) {
      Drupal::logger('lti_tool_provider_roles')->error($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context);
    }
  }

}

<?php

namespace Drupal\lti_tool_provider_attributes\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderProvisionUserEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_attributes\Event\LtiToolProviderAttributesEvents;
use Drupal\lti_tool_provider_attributes\Event\LtiToolProviderAttributesProvisionEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implementation LtiToolProviderAttributesEventSubscriber class.
 *
 * @package Drupal\lti_tool_provider_attributes\EventSubscriber
 */
class LtiToolProviderAttributesEventSubscriber implements EventSubscriberInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * LtiToolProviderAttributesEventSubscriber constructor.
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
    $user = $event->getUser();

    $mapped_attributes = [];
    if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
      $mapped_attributes = $this->configFactory->get('lti_tool_provider_attributes.settings')
        ->get('v1p0_mapped_attributes');
    }
    if ($lti_version === LTIToolProviderContextInterface::V1P3) {
      $mapped_attributes = $this->configFactory->get('lti_tool_provider_attributes.settings')
        ->get('v1p3_mapped_attributes');
    }

    if ($user->getDisplayName() === 'ltiuser') {
      return;
    }
    if (!$mapped_attributes || !count($mapped_attributes)) {
      return;
    }

    foreach ($mapped_attributes as $user_attribute => $lti_attribute) {
      if (($lti_version === LTIToolProviderContextInterface::V1P0)) {
        $context_data = $context->getContext();
        if (isset($context_data[$lti_attribute]) && !empty($context_data[$lti_attribute])) {
          $user->set($user_attribute, $context_data[$lti_attribute]);
        }
      }
      if ($lti_version === LTIToolProviderContextInterface::V1P3) {
        $claim_data = $context->getPayload()->getClaim($lti_attribute);
        if (isset($claim_data) && !empty($claim_data)) {
          $user->set($user_attribute, $claim_data);
        }
      }
    }

    try {
      $attributesEvent = new LtiToolProviderAttributesProvisionEvent($context, $user);
      $this->eventDispatcher->dispatch(LtiToolProviderAttributesEvents::PROVISION, $attributesEvent);
      $attributesEvent->getUser()->save();
    }
    catch (Exception $e) {
      Drupal::logger('lti_tool_provider_attributes')->error($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context);
    }
  }

}

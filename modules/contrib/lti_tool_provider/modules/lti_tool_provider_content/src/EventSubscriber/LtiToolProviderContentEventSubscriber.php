<?php

namespace Drupal\lti_tool_provider_content\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentEvents;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentLaunchEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LtiToolProviderContentEventSubscriber implements EventSubscriberInterface {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * LtiToolProviderContentEventSubscriber constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LtiToolProviderContentEvents::LAUNCH => 'onLaunch',
    ];
  }

  /**
   * @param \Drupal\lti_tool_provider_content\Event\LtiToolProviderContentLaunchEvent $event
   */
  public function onLaunch(LtiToolProviderContentLaunchEvent $event) {
    $config = $this->configFactory->get('lti_tool_provider_content.settings');
    $sync = $config->get('sync');
    if ($sync) {
      $entitiesDefaults = $config->get('entity_defaults');
      $context = $event->getContext();
      $entity = $event->getEntity();
      $entityType = $entity->getEntityTypeId();
      $entityBundle = $entity->bundle();
      $entityDefaults = $entitiesDefaults["$entityType-$entityBundle"];
      try {
        if (is_array($entityDefaults) && $entity instanceof ContentEntityBase) {
          foreach ($entityDefaults as $entityDefault) {
            $keys = array_map('trim', explode('-', $entityDefault['lti_attribute']));
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
              $entity->set($entityDefault['name'], $claims_data);
            }
          }
        }
        $entity->save();
      }
      catch (Exception $e) {
        Drupal::logger('lti_tool_provider_content')->error($e->getMessage());
      }
    }
  }

}

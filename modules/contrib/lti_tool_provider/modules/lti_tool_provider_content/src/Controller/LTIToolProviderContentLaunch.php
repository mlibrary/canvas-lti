<?php

namespace Drupal\lti_tool_provider_content\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentEvents;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentLaunchEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LTIToolProviderContentLaunch extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @throws \Exception
   */
  public function route(Request $request): RedirectResponse {
    try {
      $context = $request->getSession()->get('lti_tool_provider_context');
      if (!($context instanceof LTIToolProviderContextInterface)) {
        throw new Exception('LTI context missing.');
      }

      $eventDispatcher = Drupal::service('event_dispatcher');
      if (!($eventDispatcher instanceof EventDispatcherInterface)) {
        throw new Exception('Event dispatcher missing.');
      }

      $custom = $context->getPayload()->getCustom();
      if (!is_array($custom) || empty($custom)) {
        throw new Exception('Invalid custom data.');
      }

      $entityType = $custom['entity_type'];
      if (!is_string($entityType) || empty($entityType)) {
        throw new Exception('Invalid entity type.');
      }

      $entityId = $custom['entity_id'];
      if (!is_string($entityId) || empty($entityId)) {
        throw new Exception('Invalid entity type.');
      }

      $entity = Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);
      $destination = $entity->toUrl()->toString();

      $event = new LtiToolProviderContentLaunchEvent($context, $destination, $entity);
      $eventDispatcher->dispatch(LtiToolProviderContentEvents::LAUNCH, $event);

      return new RedirectResponse($event->getDestination());
    }
    catch (Exception $e) {
      $this->getLogger('lti_tool_provider_content')->warning($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context ?? NULL);
      return new RedirectResponse('/', 500);
    }
  }

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $request = Drupal::request();

    $id_token = $request->request->get('id_token') ?: $request->get('id_token');
    $state = $request->request->get('state') ?: $request->get('state');
    $is_id_token = is_string($id_token) && strlen($id_token) > 0;
    $is_state = is_string($state) && strlen($state) > 0;

    return AccessResult::allowedIf($is_id_token && $is_state);
  }

}

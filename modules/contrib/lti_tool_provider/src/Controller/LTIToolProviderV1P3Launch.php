<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderLaunchEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LTIToolProviderV1P3Launch extends ControllerBase {

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

      $payload = $context->getPayload();
      $custom = $payload->getCustom();

      if ($request->query->has('platform_redirect_url')) {
        return new RedirectResponse(\Drupal::request()->getBaseUrl().'/allow-storage?platform_redirect_url='.$request->query->get('platform_redirect_url'));
      }

      $event = new LtiToolProviderLaunchEvent($context, $custom['destination'] ?? '/');
      $eventDispatcher->dispatch($event, LtiToolProviderEvents::LAUNCH);

      return new RedirectResponse($event->getDestination());
    }
    catch (Exception $e) {
      $this->getLogger('lti_tool_provider')->warning($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context ?? NULL);
      return new RedirectResponse('/', 500);
    }
  }

}

<?php

namespace Drupal\lti_tool_provider_content\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentEvents;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentSelectEvent;
use Exception;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LTIToolProviderContentSelect extends ControllerBase {

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

      $deepLinkingSettings = $context->getPayload()->getDeepLinkingSettings();
      if (!($deepLinkingSettings instanceof DeepLinkingSettingsClaim)) {
        throw new Exception('Deep linking settings not available.');
      }

      $event = new LtiToolProviderContentSelectEvent($context, '/lti/v1p3/content/list', $deepLinkingSettings->getDeepLinkingReturnUrl());
      $eventDispatcher->dispatch(LtiToolProviderContentEvents::SELECT, $event);

      $destination = Url::fromUserInput($event->getDestination(), [
        'query' => [
          'client_id' => $context->getPayload()->getClaim('aud')[0],
          'return' => $event->getReturn(),
        ],
        'absolute' => TRUE,
      ])->toString();

      return new RedirectResponse($destination);
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

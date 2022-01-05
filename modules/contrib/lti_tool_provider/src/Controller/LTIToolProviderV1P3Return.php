<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderReturnEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Exception;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LTIToolProviderV1P3Return extends ControllerBase {

  /**
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Exception
   */
  public function route() {
    try {
      $context = Drupal::request()->getSession()->get('lti_tool_provider_context');
      if (!($context instanceof LTIToolProviderContextInterface)) {
        throw new Exception('LTI context missing.');
      }

      $eventDispatcher = Drupal::service('event_dispatcher');
      if (!($eventDispatcher instanceof EventDispatcherInterface)) {
        throw new Exception('Event dispatcher missing.');
      }

      $killSwitch = Drupal::service('page_cache_kill_switch');
      if ($killSwitch instanceof KillSwitch) {
        $killSwitch->trigger();
      }

      $launchPresentation = $context->getPayload()->getLaunchPresentation();
      if (!($launchPresentation instanceof LaunchPresentationClaim)) {
        throw new Exception('No launch presentation data.');
      }

      $destination = $launchPresentation->getReturnUrl();
      if (!is_string($destination) || empty($destination)) {
        throw new Exception('No return url provided.');
      }

      $event = new LtiToolProviderReturnEvent($context, $destination);
      $eventDispatcher->dispatch(LtiToolProviderEvents::RETURN, $event);

      $this->userLogout();

      return new TrustedRedirectResponse($event->getDestination());
    }
    catch (Exception $e) {
      $this->getLogger('lti_tool_provider')->warning($e->getMessage());
      if (isset($context)) {
        LTIToolProviderContext::sendError($e->getMessage(), $context);
      }
      return new RedirectResponse('/', 500);
    }
  }

  /**
   * User log out method.
   */
  protected function userLogout() {
    user_logout();
  }

}

<?php

namespace Drupal\lti_tool_provider\EventSubscriber;

use Drupal;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Implementation RemoveXFrameOptionsSubscriber class.
 */
class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface {

  /**
   * @return array|mixed
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['RemoveXFrameOptions', -10];

    return $events;
  }

  /**
   * @param FilterResponseEvent $event
   *
   * @todo Only add ResponseEvent typing to $event once D8 is no longer
   *   supported.
   */
  public function RemoveXFrameOptions(FilterResponseEvent $event) {
    if (Drupal::config('lti_tool_provider.settings')->get('iframe')) {
      $session = $event->getRequest()->getSession();
      $context = $session->get('lti_tool_provider_context');
      if ($context instanceof LTIToolProviderContextInterface && Drupal::currentUser()->isAuthenticated()) {
        $response = $event->getResponse();
        $response->headers->remove('X-Frame-Options');
      }
    }
  }

}

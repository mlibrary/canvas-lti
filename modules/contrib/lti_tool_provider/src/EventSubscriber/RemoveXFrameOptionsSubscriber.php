<?php

namespace Drupal\lti_tool_provider\EventSubscriber;

use Drupal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface
{
    /**
     * @param FilterResponseEvent $event
     */
    public function RemoveXFrameOptions(FilterResponseEvent $event)
    {
        if (Drupal::config('lti_tool_provider.settings')->get('iframe')) {
            $session = $event->getRequest()->getSession();
            $context = $session->get('lti_tool_provider_context');

            if (!empty($context) && Drupal::currentUser()->isAuthenticated()) {
                $response = $event->getResponse();
                $response->headers->remove('X-Frame-Options');
            }
        }
    }

    /**
     * @return array|mixed
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::RESPONSE][] = array('RemoveXFrameOptions', -10);

        return $events;
    }
}

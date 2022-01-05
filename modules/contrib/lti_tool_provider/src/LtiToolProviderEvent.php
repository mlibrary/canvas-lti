<?php

namespace Drupal\lti_tool_provider;

use Drupal\Core\Url;
use Exception;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LtiToolProviderEvent extends Event
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_EVENT';

    /**
     * @var bool
     */
    private $cancelled = false;

    /**
     * @var string
     */
    private $message;

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function cancel(string $message = 'Launch has been cancelled.'): void
    {
        $this->cancelled = true;
        $this->message = $message;
        $this->stopPropagation();
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Dispatch an LTI Tool Provider event.
     *
     * @param EventDispatcherInterface $eventDispatcher
     *   The event dispatcher.
     * @param LtiToolProviderEvent $event
     *   The event to dispatch.
     * @throws Exception
     */
    static function dispatchEvent(EventDispatcherInterface $eventDispatcher, LtiToolProviderEvent &$event)
    {
        $event = $eventDispatcher->dispatch($event::EVENT_NAME, $event);
        if ($event instanceof LtiToolProviderEvent && $event->isCancelled()) {
            throw new Exception($event->getMessage());
        }
    }

    /**
     * Send an error back to the LMS.
     *
     * @param array $context
     *   The LTI context.
     * @param string $message
     *   The error message to send.
     */
    public function sendLtiError(array $context, string $message)
    {
        if (isset($context['launch_presentation_return_url']) && !empty($context['launch_presentation_return_url'])) {
            $url = Url::fromUri($context['launch_presentation_return_url'])
                ->setOption(
                    'query',
                    [
                        'lti_errormsg' => $message,
                    ]
                )
                ->setAbsolute(true)
                ->toString();

            $response = new RedirectResponse($url);
            $response->send();
        }
    }
}

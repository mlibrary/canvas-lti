<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LtiToolProviderEvent;

class LtiToolProviderLaunchRedirectEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_LAUNCH_REDIRECT_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * @var string
     */
    private $destination;

    /**
     * LtiToolProviderLaunchRedirectEvent constructor.
     * @param array $context
     * @param string $destination
     */
    public function __construct(array $context, string $destination)
    {
        $this->setContext($context);
        $this->setDestination($destination);
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }
}

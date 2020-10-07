<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LtiToolProviderEvent;

class LtiToolProviderLaunchEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_LAUNCH_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * LtiToolProviderLaunchEvent constructor.
     * @param array $context
     */
    public function __construct(array $context)
    {
        $this->setContext($context);
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
}

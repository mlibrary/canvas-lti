<?php

namespace Drupal\lti_tool_provider\Event;

use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\user\UserInterface;

class LtiToolProviderProvisionUserEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_PROVISION_USER_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * LtiToolProviderProvisionUserEvent constructor.
     * @param array $context
     * @param UserInterface $user
     */
    public function __construct(array $context, UserInterface $user)
    {
        $this->setContext($context);
        $this->setUser($user);
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
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }
}

<?php

namespace Drupal\lti_tool_provider_roles\Event;

use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\user\UserInterface;

class LtiToolProviderRolesEvent extends LtiToolProviderEvent
{
    const EVENT_NAME = 'LTI_TOOL_PROVIDER_ROLES_EVENT';

    /**
     * @var array
     */
    private $context;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * LtiToolProviderRolesEvent constructor.
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

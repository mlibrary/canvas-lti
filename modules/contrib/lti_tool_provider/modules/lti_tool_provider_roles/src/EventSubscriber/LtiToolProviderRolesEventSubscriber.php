<?php

namespace Drupal\lti_tool_provider_roles\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderAuthenticatedEvent;
use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\lti_tool_provider_roles\Event\LtiToolProviderRolesEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LtiToolProviderRolesEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * LtiToolProviderRolesEventSubscriber constructor.
     * @param ConfigFactoryInterface $configFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configFactory = $configFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LtiToolProviderAuthenticatedEvent::EVENT_NAME => 'onAuthenticated',
        ];
    }

    public function onAuthenticated(LtiToolProviderAuthenticatedEvent $event)
    {
        $mapped_roles = Drupal::config('lti_tool_provider_roles.settings')->get('mapped_roles');
        $context = $event->getContext();
        $user = $event->getUser();

        $user_roles = user_roles(true, null);
        $lti_roles = parse_roles($context['roles']);

        if ($user->getDisplayName() === 'ltiuser') {
            return;
        }

        if (!$mapped_roles || !count($mapped_roles)) {
            return;
        }

        foreach ($mapped_roles as $user_role => $lti_role) {
            if (array_key_exists($user_role, $user_roles)) {
                if (in_array($lti_role, $lti_roles)) {
                    $user->addRole($user_role);
                }
                else {
                    $user->removeRole($user_role);
                }
            }
        }

        try {
            $rolesEvent = new LtiToolProviderRolesEvent($context, $user);
            LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $rolesEvent);

            if ($rolesEvent->isCancelled()) {
                throw new Exception($event->getMessage());
            }

            $user->save();
        }
        catch (Exception $e) {
            Drupal::logger('lti_tool_provider_roles')->error($e->getMessage());
        }
    }
}

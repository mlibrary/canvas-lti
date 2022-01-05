<?php

namespace Drupal\lti_tool_provider_attributes\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderAuthenticatedEvent;
use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\lti_tool_provider_attributes\Event\LtiToolProviderAttributesEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LtiToolProviderAttributesEventSubscriber implements EventSubscriberInterface
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
     * LtiToolProviderAttributesEventSubscriber constructor.
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
    public static function getSubscribedEvents(): array
    {
        return [
            LtiToolProviderAuthenticatedEvent::EVENT_NAME => 'onAuthenticated',
        ];
    }

    /**
     * @param LtiToolProviderAuthenticatedEvent $event
     */
    public function onAuthenticated(LtiToolProviderAuthenticatedEvent $event)
    {
        $mapped_attributes = $this->configFactory->get('lti_tool_provider_attributes.settings')->get('mapped_attributes');
        $context = $event->getContext();
        $user = $event->getUser();

        if ($user->getDisplayName() === 'ltiuser') {
            return;
        }

        if (!$mapped_attributes || !count($mapped_attributes)) {
            return;
        }

        foreach ($mapped_attributes as $user_attribute => $lti_attribute) {
            if (isset($context[$mapped_attributes[$user_attribute]]) && !empty($context[$mapped_attributes[$user_attribute]])) {
                $user->set($user_attribute, $context[$mapped_attributes[$user_attribute]]);
            }
        }

        try {
            $attributesEvent = new LtiToolProviderAttributesEvent($context, $user);
            LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $attributesEvent);

            if ($attributesEvent->isCancelled()) {
                throw new Exception($event->getMessage());
            }

            $user->save();
        }
        catch (Exception $e) {
            Drupal::logger('lti_tool_provider_attributes')->error($e->getMessage());
        }
    }
}

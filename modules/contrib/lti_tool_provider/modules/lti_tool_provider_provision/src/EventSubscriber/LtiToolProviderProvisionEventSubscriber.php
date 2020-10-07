<?php

namespace Drupal\lti_tool_provider_provision\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderLaunchRedirectEvent;
use Drupal\lti_tool_provider\LtiToolProviderEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionEvent;
use Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionEvent;
use Drupal\lti_tool_provider_provision\Services\ProvisionService;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LtiToolProviderProvisionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var ProvisionService
     */
    private $provisionService;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * LtiToolProviderProvisionEventSubscriber constructor.
     * @param ConfigFactoryInterface $configFactory
     * @param ProvisionService $provisionService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        ProvisionService $provisionService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configFactory = $configFactory;
        $this->provisionService = $provisionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LtiToolProviderLaunchRedirectEvent::EVENT_NAME => 'onLaunch',
            LtiToolProviderProvisionCreateProvisionEvent::EVENT_NAME => 'onCreateProvision',
        ];
    }

    public function onLaunch(LtiToolProviderLaunchRedirectEvent $event)
    {
        $context = $event->getContext();

        try {
            if ($entity = $this->provisionService->provision($context)) {
                if ($this->configFactory->get('lti_tool_provider_provision.settings')->get('entity_sync')) {
                    $this->provisionService->syncProvisionedEntity($context, $entity);
                    $entity->save();
                }

                $provisionEvent = new LtiToolProviderProvisionEvent($context, $entity, $entity->toUrl()->toString());
                LtiToolProviderEvent::dispatchEvent($this->eventDispatcher, $provisionEvent);

                if ($provisionEvent->isCancelled()) {
                    throw new Exception($event->getMessage());
                }

                if ($this->configFactory->get('lti_tool_provider_provision.settings')->get('entity_redirect')) {
                    $event->setDestination($provisionEvent->getDestination());
                }
            }
        }
        catch (Exception $e) {
            $event->sendLtiError($context, $e->getMessage());
            Drupal::logger('lti_tool_provider_provision')->error($e->getMessage());
        }
    }

    public function onCreateProvision(LtiToolProviderProvisionCreateProvisionEvent $event)
    {
        $context = $event->getContext();
        $access = true;

        if ($this->configFactory->get('lti_tool_provider_provision.settings')->get('allowed_roles_enabled')) {
            $access = false;
            $ltiRoles = parse_roles($context['roles']);
            $allowedRoles = $this->configFactory->get('lti_tool_provider_provision.settings')->get('allowed_roles');
            foreach ($ltiRoles as $ltiRole) {
                if (isset($allowedRoles[$ltiRole]) && $allowedRoles[$ltiRole]) {
                    $access = true;
                    break;
                }
            }
        }

        if (!$access) {
            $event->cancel('Unable to provision entity.');
        }
    }
}

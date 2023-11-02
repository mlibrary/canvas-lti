<?php

namespace Drupal\lti_tool_provider_content\Controller;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentEvents;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentResourceEvent;
use Drupal\lti_tool_provider_content\Event\LtiToolProviderContentReturnEvent;
use Exception;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Resource\ResourceCollection;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchResponseBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LTIToolProviderContentReturn extends ControllerBase {

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function route(Request $request) {
    try {
      $client_id = $request->request->get('client_id') ?: $request->get('client_id');
      if (empty($client_id)) {
        throw new Exception('Client id missing.');
      }

      $return = $request->request->get('return') ?: $request->get('return');
      if (!is_string($return) || empty($return)) {
        throw new Exception('Return url missing.');
      }

      $entityType = $request->request->get('entityType') ?: $request->get('entityType');
      if (!is_string($entityType) || empty($entityType)) {
        throw new Exception('Entity type missing.');
      }

      $entityId = $request->request->get('entityId') ?: $request->get('entityId');
      if (!is_string($entityId) || empty($entityId)) {
        throw new Exception('Entity id missing.');
      }

      $eventDispatcher = Drupal::service('event_dispatcher');
      if (!($eventDispatcher instanceof EventDispatcherInterface)) {
        throw new Exception('Event dispatcher missing.');
      }

      $registrationRepository = Drupal::service('lti_tool_provider.registration.repository');
      if (!($registrationRepository instanceof RegistrationRepositoryInterface)) {
        throw new Exception('Registration repository missing.');
      }

      $registration = $registrationRepository->findByClientId($client_id);
      if (is_null($registration)) {
        throw new Exception("Missing registration for the client ID: $client_id.");
      }

      $properties = [
        'url' => Url::fromRoute('lti_tool_provider.content.launch', [], ['absolute' => TRUE])->toString(),
        'custom' => [
          'entity_type' => $entityType,
          'entity_id' => $entityId,
        ],
      ];

      $title = $request->request->get('title') ?: $request->get('title');
      if (!empty($title)) {
        $properties['title'] = $title;
      }

      $text = $request->request->get('text') ?: $request->get('text');
      if (!empty($text)) {
        $properties['text'] = $text;
      }

      $icon = $request->request->get('icon') ?: $request->get('icon');
      if (!empty($icon)) {
        $properties['icon'] = $icon;
      }

      $thumbnail = $request->request->get('thumbnail') ?: $request->get('thumbnail');
      if (!empty($thumbnail)) {
        $properties['thumbnail'] = $thumbnail;
      }

      $iframe = $request->request->get('iframe') ?: $request->get('iframe');
      if (!empty($iframe)) {
        $properties['iframe'] = $iframe;
      }

      $custom = $request->request->get('custom') ?: $request->get('custom');
      if (!empty($custom)) {
        $properties['custom'] = $custom;
      }

      $lineItem = $request->request->get('lineItem') ?: $request->get('lineItem');
      if (!empty($lineItem)) {
        $properties['lineItem'] = $lineItem;
      }

      $available = $request->request->get('available') ?: $request->get('available');
      if (!empty($available)) {
        $properties['available'] = $available;
      }

      $submission = $request->request->get('submission') ?: $request->get('submission');
      if (!empty($submission)) {
        $properties['submission'] = $submission;
      }

      $event = new LtiToolProviderContentResourceEvent($properties, $registration, $return);
      $eventDispatcher->dispatch($event, LtiToolProviderContentEvents::RESOURCE);

      $ltiResourceLink = new LtiResourceLink("$entityType-$entityId", $event->getProperties());
      $resourceCollection = new ResourceCollection();
      $resourceCollection->add($ltiResourceLink);

      $builder = new DeepLinkingLaunchResponseBuilder();
      $message = $builder->buildDeepLinkingLaunchResponse($resourceCollection, $event->getRegistration(), $event->getReturn());

      $event = new LtiToolProviderContentReturnEvent($message);
      $eventDispatcher->dispatch($event, LtiToolProviderContentEvents::RETURN);

      return new Response($event->getMessage()->toHtmlRedirectForm());
    }
    catch (LtiExceptionInterface $e) {
      $this->getLogger('lti_tool_provider_content')->warning($e->getMessage());
      return new RedirectResponse('/', 500);
    }
    catch (Exception $e) {
      $this->getLogger('lti_tool_provider_content')->warning($e->getMessage());
      return new RedirectResponse('/', 500);
    }
  }

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $request = Drupal::request();

    $client_id = $request->request->get('client_id') ?: $request->get('client_id');
    $return = $request->request->get('return') ?: $request->get('return');
    $entityType = $request->request->get('entityType') ?: $request->get('entityType');
    $entityId = $request->request->get('entityId') ?: $request->get('entityId');

    $is_client_id = is_string($client_id) && strlen($client_id) > 0;
    $is_return = is_string($return) && strlen($return) > 0;

    try {
      $entity = Drupal::entityTypeManager()->getStorage($entityType)->load($entityId);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return AccessResult::forbidden('Error fetching entity.');
    }

    return AccessResult::allowedIf($is_client_id && $is_return && $entity instanceof EntityInterface);
  }

}

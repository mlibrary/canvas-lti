<?php

namespace Drupal\lti_tool_provider\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\lti_tool_provider\Event\LtiToolProviderAuthenticatedEvent;
use Drupal\lti_tool_provider\Event\LtiToolProviderCreateUserEvent;
use Drupal\lti_tool_provider\Event\LtiToolProviderEvents;
use Drupal\lti_tool_provider\Event\LtiToolProviderProvisionUserEvent;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class LTIToolProviderBase implements AuthenticationProviderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * LTIToolProviderBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    EventDispatcherInterface $eventDispatcher
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory->get('lti_tool_provider');
    $this->eventDispatcher = $eventDispatcher;
  }

  public function authenticate(Request $request) {
    try {
      $context = $this->validate($this->convertToPsrRequest($request));
      $user = $this->provisionUser($context);

      $provisionUserEvent = new LtiToolProviderProvisionUserEvent($context, $user);
      $this->eventDispatcher->dispatch(LtiToolProviderEvents::PROVISION_USER, $provisionUserEvent);

      $authenticatedEvent = new LtiToolProviderAuthenticatedEvent($provisionUserEvent->getContext(), $provisionUserEvent->getUser());
      $this->eventDispatcher->dispatch(LtiToolProviderEvents::AUTHENTICATED, $authenticatedEvent);

      $this->userLoginFinalize($authenticatedEvent->getUser());

      $session = $request->getSession();
      $session->set('lti_tool_provider_context', $authenticatedEvent->getContext());

      return $authenticatedEvent->getUser();
    }
    catch (Exception $e) {
      $this->loggerFactory->warning($e->getMessage());
      LTIToolProviderContext::sendError($e->getMessage(), $context ?? NULL);
    }

    return NULL;
  }

  /**
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *
   * @return \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  abstract protected function validate(ServerRequestInterface $request): LTIToolProviderContextInterface;

  /**
   * Converts a symfony request to a PSR message request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Psr\Http\Message\ServerRequestInterface
   */
  public function convertToPsrRequest(Request $request): ServerRequestInterface {
    $psr17Factory = new Psr17Factory();
    $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    return $psrHttpFactory->createRequest($request);
  }

  /**
   * Provision a user that matches the LTI request context info.
   *
   * @param \Drupal\lti_tool_provider\LTIToolProviderContextInterface $context
   *
   * @return \Drupal\user\UserInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  protected function provisionUser(LTIToolProviderContextInterface $context): UserInterface {
    $version=$context->getVersion();
    if ($version == 'V1P3') {
      $payload = $context->getPayload();
      $claims = $payload->getToken()->getClaims();
      $name = $claims->all()['sub'];
      $iss = $claims->all()['iss'];
      $domain = parse_url($iss, PHP_URL_HOST);
      $mail = $name.'@'.$domain;
    }
    else {
      $full_context = $context->getContext();
      $name = $context->getUserIdentity()->getName();
      $mail = $context->getUserIdentity()->getEmail();
    }
    if ((isset($full_context['lis_person_name_full']) && $full_context['lis_person_name_full'] == 'Test Student') &&
       (isset($full_context['roles']) && $full_context['roles'] == 'Learner')) {
      $name = 'Test Student';
      $mail = 'teststudent@umich.edu';
    }

    if (empty($name)) {
      throw new Exception('Name not available for user provisioning.');
    }

    if (empty($mail)) {
      throw new Exception('Email not available for user provisioning.');
    }

    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name, 'status' => 1]);
    $user = reset($users);
    if ($user instanceof UserInterface) {
      return $user;
    }

    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $mail, 'status' => 1]);
    $user = reset($users);
    if ($user instanceof UserInterface) {
      return $user;
    }

    $user = User::create();
    $user->setUsername($name);
    $user->setEmail($mail);
    $user->setPassword(user_password());
    $user->enforceIsNew();
    $user->activate();

    $createUserEvent = new LtiToolProviderCreateUserEvent($context, $user);
    $this->eventDispatcher->dispatch(LtiToolProviderEvents::CREATE_USER, $createUserEvent);

    $user = $createUserEvent->getUser();
    $user->save();

    return $user;
  }

  /**
   * Finalize login.
   *
   * @param \Drupal\user\UserInterface $user
   */
  protected function userLoginFinalize(UserInterface $user) {
    user_login_finalize($user);
  }

}

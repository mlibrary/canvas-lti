<?php

namespace Drupal\lti_tool_provider\Services;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\lti_tool_provider\Entity\LtiToolProviderConsumer;
use Exception;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyChain;
use OAT\Library\Lti1p3Core\Tool\Tool;

/**
 * Implementation LTIDatabase class.
 *
 * @package Drupal\lti_tool_provider
 */
class LTIToolProviderRegistrationRepository implements RegistrationRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * LTIService constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $configFactory->get('system.site');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param string $identifier
   *
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  public function find(string $identifier): ?RegistrationInterface {
    return NULL;
  }

  /**
   * @return RegistrationInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findAll(): array {
    $registrations = [];

    $consumers = $this->entityTypeManager
      ->getStorage('lti_tool_provider_consumer')
      ->loadMultiple();

    foreach ($consumers as $consumer) {
      if ($consumer instanceof LtiToolProviderConsumer) {
        $registrations[] = $this->getRegistrationFromConsumer($consumer);
      }
    }

    return $registrations;
  }

  /**
   * @param \Drupal\lti_tool_provider\Entity\LtiToolProviderConsumer $consumer
   *
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface
   */
  private function getRegistrationFromConsumer(LtiToolProviderConsumer $consumer): RegistrationInterface {
    $client_id = $consumer->get('client_id')->getValue()[0]['value'];
    $platform_name = $consumer->get('consumer')->getValue()[0]['value'];
    $platform_id = $consumer->get('platform_id')->getValue()[0]['value'];
    $deployment_id = $consumer->get('deployment_id')->getValue()[0]['value'];
    $auth_login_url = $consumer->get('auth_login_url')->getValue()[0]['value'];
    $auth_token_url = $consumer->get('auth_token_url')->getValue()[0]['value'];
    $key_set_url = $consumer->get('key_set_url')->getValue()[0]['value'];
    $public_key_id = $consumer->get('public_key')->getValue()[0]['target_id'];
    $private_key_id = $consumer->get('private_key')->getValue()[0]['target_id'];
    $tool_id = Drupal\Core\Url::fromRoute('<front>', [], ['absolute' => TRUE, 'language' => Drupal::languageManager()->getCurrentLanguage()])->toString();
    $tool_name = $this->config->get('name');
    $tool_launch_url = Drupal\Core\Url::fromRoute('lti_tool_provider.v1p3.launch', [], ['absolute' => TRUE, 'language' => Drupal::languageManager()->getCurrentLanguage()])->toString();
    $tool_login_url = Drupal\Core\Url::fromRoute('lti_tool_provider.v1p3.login', [], ['absolute' => TRUE, 'language' => Drupal::languageManager()->getCurrentLanguage()])->toString();

    $platform = new Platform(
      $client_id,
      $platform_name,
      $platform_id,
      $auth_login_url,
      $auth_token_url,
    );

    $tool = new Tool(
      $tool_id,
      $tool_name,
      $tool_launch_url,
      $tool_login_url,
      $tool_launch_url,
      NULL,
    );

    $public_key = Drupal::service('key.repository')->getKey($public_key_id);
    $private_key = Drupal::service('key.repository')->getKey($private_key_id);
    $public_key_value = $public_key->getKeyValue();
    $private_key_value = $private_key->getKeyValue();
    $platformKeyChain = NULL;
    $toolKeyChain = new KeyChain($public_key->id(), $public_key->label(), new Key(str_replace('\n', "\n", $public_key_value)), new Key(str_replace('\n', "\n", $private_key_value)));

    return new Registration(
      $consumer->id(),
      $client_id,
      $platform,
      $tool,
      [$deployment_id],
      $platformKeyChain,
      $toolKeyChain,
      $key_set_url,
    );
  }

  /**
   * @param string $clientId
   *
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  public function findByClientId(string $clientId): ?RegistrationInterface {
    try {
      $consumers = $this->entityTypeManager
        ->getStorage('lti_tool_provider_consumer')
        ->loadByProperties(['client_id' => $clientId]);

      $consumer = reset($consumers);

      if (!($consumer instanceof LtiToolProviderConsumer)) {
        throw new Exception("Client not found.");
      }

      return $this->getRegistrationFromConsumer($consumer);
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * @param string $issuer
   * @param string|null $clientId
   *
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  public function findByPlatformIssuer(string $issuer, string $clientId = NULL): ?RegistrationInterface {
    try {
      if (!isset($issuer)) {
        throw new Exception("No issuer provided.");
      }

      $properties = ['platform_id' => $issuer];
      if ($clientId) {
        $properties['client_id'] = $clientId;
      }

      $consumers = $this->entityTypeManager
        ->getStorage('lti_tool_provider_consumer')
        ->loadByProperties($properties);

      $consumer = reset($consumers);

      if (!($consumer instanceof LtiToolProviderConsumer)) {
        throw new Exception("Consumer not found.");
      }

      return $this->getRegistrationFromConsumer($consumer);
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * @param string $issuer
   * @param string|null $clientId
   *
   * @return \OAT\Library\Lti1p3Core\Registration\RegistrationInterface|null
   */
  public function findByToolIssuer(string $issuer, string $clientId = NULL): ?RegistrationInterface {
    return NULL;
  }

}

<?php

namespace Drupal\lti_tool_provider\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;

/**
 * Implementation LTIDatabase class.
 *
 * @package Drupal\lti_tool_provider
 */
class LTIToolProviderNonceRepository implements NonceRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LTIService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param string $value
   *
   * @return \OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function find(string $value): ?NonceInterface {
    $nonce = $this->entityTypeManager->getStorage('lti_tool_provider_nonce')
      ->getQuery()
      ->condition('nonce', $value, '=')
      ->execute();

    if ($nonce instanceof \Drupal\lti_tool_provider\Entity\Nonce) {
      $value = $nonce->get('nonce')->getValue()[0]['value'];
      $timestamp = $nonce->get('timestamp')->getValue()[0]['value'];
      return new Nonce($value, $timestamp);
    }

    return NULL;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface $nonce
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(NonceInterface $nonce): void {
    $this->entityTypeManager->getStorage('lti_tool_provider_nonce')->create(
      [
        'nonce' => $nonce->getValue(),
        'timestamp' => $nonce->getExpiredAt()->getTimestamp(),
      ]
    )->save();
  }

}

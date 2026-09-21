<?php

namespace Drupal\fieldblock;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Queries and deletes the block entities provided by this module.
 *
 * @internal
 *   This class is not an API. It may change or be removed at any time without
 *   a deprecation period.
 *
 * @package Drupal\fieldblock
 */
class BlockEntityStorage {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get the storage handler of the block entity type.
   */
  protected function getBlockStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('block');
  }

  /**
   * Load all blocks provided by this module.
   */
  public function loadFieldBlocks(): array {
    $storage = $this->getBlockStorage();
    // Build a query to fetch the entity IDs.
    $entity_query = $storage->getQuery();
    $entity_query->accessCheck(FALSE);
    $entity_query->condition('plugin', 'fieldblock:', 'STARTS_WITH');
    $result = $entity_query->execute();
    return $result ? $storage->loadMultiple($result) : [];
  }

  /**
   * Get all entity type ids that are currently used in Field Blocks.
   *
   * This will also return entity type ids for entities that are no longer
   * available.
   */
  public function getEntityTypesUsed(): array {
    $blocks = $this->loadFieldBlocks();
    $entity_types = [];
    foreach ($blocks as $block) {
      $plugin_parts = explode(':', $block->get('plugin'));
      $entity_types[] = $plugin_parts['1'];
    }

    return $entity_types;
  }

  /**
   * Delete all blocks for an entity type.
   */
  public function deleteBlocksForEntityType(string $entity_type): void {
    $storage = $this->getBlockStorage();
    $blocks = $storage->loadByProperties(['plugin' => "fieldblock:$entity_type"]);
    $storage->delete($blocks);
  }

}

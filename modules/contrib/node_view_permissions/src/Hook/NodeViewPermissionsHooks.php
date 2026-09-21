<?php

namespace Drupal\node_view_permissions\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for node_view_permissions.
 */
final class NodeViewPermissionsHooks {

  /**
   * Constructs hook implementations for node_view_permissions.
   */
  public function __construct(
    private readonly LanguageManagerInterface $languageManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node): array {
    $grants = [];

    // We don't want to override view published permissions.
    foreach ($node->getTranslationLanguages() as $langcode => $language) {
      if ($langcode === Language::LANGCODE_NOT_APPLICABLE || $langcode === Language::LANGCODE_NOT_SPECIFIED) {
        $published = $node->isPublished();
        $grants[] = $this->buildGrant($node, $published ? 'view_any' : 'view_any_unpublished');
        $grants[] = $this->buildGrant($node, $published ? 'view_own' : 'view_own_unpublished', $node->getOwnerId());
        continue;
      }

      // If node is translated, check the publish status of the translation and
      // create a separate realm for it.
      $translation = $node->getTranslation($langcode);
      $published = $translation->isPublished();
      $grants[] = $this->buildGrant($node, $published ? 'view_any' : 'view_any_unpublished', 1, $langcode);
      $grants[] = $this->buildGrant($node, $published ? 'view_own' : 'view_own_unpublished', $node->getOwnerId(), $langcode);
    }

    return $grants;
  }

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants(AccountInterface $account, $op): array {
    $static_cache =& drupal_static('node_view_permissions_node_grants', []);
    $cache_key = $account->id() . ':' . $op;
    if (isset($static_cache[$cache_key])) {
      return $static_cache[$cache_key];
    }

    $grants = [];
    if ($op === 'view') {
      $languages = $this->languageManager->getLanguages();
      $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      $account_id = (int) $account->id();
      $is_authenticated = $account_id !== 0;
      $view_any_unpublished = $account->hasPermission('view any unpublished content');
      $view_own_unpublished = $account->hasPermission('view own unpublished content');

      foreach ($node_types as $type) {
        $type_id = $type->id();
        $view_any = $account->hasPermission("view any $type_id content");
        $view_own = $account->hasPermission("view own $type_id content") && $is_authenticated;

        // Language-independent grants are set once per type.
        $this->addGrant($grants, "view_any_{$type_id}_content", $view_any, 1);
        $this->addGrant($grants, "view_own_{$type_id}_content", $view_own, $account_id);
        $this->addGrant($grants, "view_any_unpublished_{$type_id}_content", $view_any_unpublished && $view_any, 1);
        $this->addGrant($grants, "view_own_unpublished_{$type_id}_content", $view_own_unpublished && $view_own, $account_id);

        // Language-specific grants reuse cached permission results.
        foreach (array_keys($languages) as $langcode) {
          $this->addGrant($grants, "view_any_{$type_id}_{$langcode}_content", $view_any, 1);
          $this->addGrant($grants, "view_own_{$type_id}_{$langcode}_content", $view_own, $account_id);
          $this->addGrant($grants, "view_any_unpublished_{$type_id}_{$langcode}_content", $view_any_unpublished && $view_any, 1);
          $this->addGrant($grants, "view_own_unpublished_{$type_id}_{$langcode}_content", $view_own_unpublished && $view_own, $account_id);
        }
      }
    }

    $static_cache[$cache_key] = $grants;
    return $grants;
  }

  /**
   * Builds a node access grant record.
   */
  private function buildGrant(NodeInterface $node, string $realm_prefix, int|string $gid = 1, ?string $langcode = NULL): array {
    $realm_parts = [$realm_prefix, $node->getType()];
    if ($langcode !== NULL) {
      $realm_parts[] = $langcode;
    }
    $realm_parts[] = 'content';

    $grant = [
      'realm' => implode('_', $realm_parts),
      'gid' => $gid,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
      'priority' => 0,
    ];

    if ($langcode !== NULL) {
      $grant['langcode'] = $langcode;
    }

    return $grant;
  }

  /**
   * Adds a node grant when a permission condition is met.
   */
  private function addGrant(array &$grants, string $realm, bool $condition, int $gid): void {
    if ($condition) {
      $grants[$realm] = [$gid];
    }
  }

}

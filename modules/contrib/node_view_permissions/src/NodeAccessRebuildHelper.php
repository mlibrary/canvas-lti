<?php

namespace Drupal\node_view_permissions;

/**
 * Provides Drupal 10.3/11/12 compatible wrappers for node access rebuild APIs.
 */
final class NodeAccessRebuildHelper {

  /**
   * The Drupal 11.4+ node access rebuild service ID.
   */
  private const SERVICE_ID = 'Drupal\\node\\NodeAccessRebuild';

  /**
   * Marks node access permissions for rebuild.
   *
   * @param bool $needs_rebuild
   *   Whether a rebuild is needed.
   */
  public static function setNeedsRebuild(bool $needs_rebuild = TRUE): void {
    if ($service = self::nodeAccessRebuildService()) {
      $service->setNeedsRebuild($needs_rebuild);
      return;
    }

    if (function_exists('node_access_needs_rebuild')) {
      call_user_func('node_access_needs_rebuild', $needs_rebuild);
    }
  }

  /**
   * Checks whether node access permissions need a rebuild.
   *
   * @return bool
   *   TRUE when a rebuild is needed.
   */
  public static function needsRebuild(): bool {
    if ($service = self::nodeAccessRebuildService()) {
      return $service->needsRebuild();
    }

    return function_exists('node_access_needs_rebuild') ? (bool) call_user_func('node_access_needs_rebuild') : FALSE;
  }

  /**
   * Rebuilds node access permissions.
   */
  public static function rebuild(): void {
    if ($service = self::nodeAccessRebuildService()) {
      $service->rebuild();
      return;
    }

    if (function_exists('node_access_rebuild')) {
      call_user_func('node_access_rebuild');
    }
  }

  /**
   * Gets the Drupal 11.4+ node access rebuild service, when available.
   *
   * @return object|null
   *   The node access rebuild service, or NULL on Drupal versions that do not
   *   provide it.
   */
  private static function nodeAccessRebuildService(): ?object {
    $container = \Drupal::hasContainer() ? \Drupal::getContainer() : NULL;
    if ($container && $container->has(self::SERVICE_ID)) {
      return $container->get(self::SERVICE_ID);
    }
    return NULL;
  }

}

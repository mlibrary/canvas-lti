<?php

/**
 * @file
 * Post update functions for oEmbed Providers.
 */

/**
 * Clear caches to discover new 'oembed_providers.invalidator' service.
 */
function oembed_providers_post_update_invalidator_service() {
  // No-op.
}

/**
 * Clear caches to discover new local task items.
 */
function oembed_providers_post_update_add_local_tasks() {
  // Empty post-update function.
}

/**
 * Clear caches due to updated ProviderRepositoryDecorator constructor.
 */
function oembed_providers_post_update_decorator_constructor_change() {
  // Empty post-update function.
}

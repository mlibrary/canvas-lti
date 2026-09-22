<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Determines component visibility.
 */
class SectionComponentVisibility implements EventSubscriberInterface {

  use ConditionAccessResolverTrait;

  /**
   * Creates a SectionComponentVisibility object.
   */
  public function __construct(protected ContextHandlerInterface $contextHandler, protected ExecutableManagerInterface $conditionManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority is set to 255 so this subscriber is run after the one in
    // BlockComponentRenderArray.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender', 255];
    return $events;
  }

  /**
   * Determines the visibility of section components.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component build render array event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event): void {
    if ($event->inPreview()) {
      return;
    }

    $conditions = [];

    $visibility = $event->getComponent()->get('visibility') ?: [];
    foreach ($visibility as $uuid => $configuration) {
      $condition = $this->conditionManager->createInstance($configuration['id'], $configuration);
      if ($condition instanceof ContextAwarePluginInterface) {
        $this->contextHandler->applyContextMapping($condition, $event->getContexts());
      }
      $event->addCacheableDependency($condition);
      $conditions[$uuid] = $condition;
    }

    $visibility_operator = $event->getComponent()->get('visibility_operator') ?: 'and';

    if ($conditions && !$this->resolveConditions($conditions, $visibility_operator)) {
      // If conditions do not resolve, do not process other subscribers.
      $event->stopPropagation();
    }
  }

}

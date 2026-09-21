<?php

declare(strict_types=1);

namespace Drupal\fieldblock_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the field block test routes.
 */
class FieldBlockTestController extends ControllerBase {

  /**
   * Renders a page on a subpath of a node.
   *
   * The page itself is empty. Its purpose is to provide a route that has a node
   * as a parameter but is not the canonical route of that node.
   *
   * @see \Drupal\fieldblock\Plugin\Block\FieldBlock::getEntity()
   */
  public function subpath(): array {
    return [
      '#markup' => $this->t('Field block subpath.'),
    ];
  }

}

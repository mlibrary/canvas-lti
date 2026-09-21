<?php

namespace Drupal\node_view_permissions;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodePermissions;

/**
 * Class definition.
 *
 * @category NodeViewPermissionsPermissions
 *
 * @package Access Control
 */
class NodeViewPermissionsPermissions extends NodePermissions {

  /**
   * Returns a list of node view permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type): array {
    $type_id = $type->id();
    return [
      "view any $type_id content" => [
        'title' => $this->t('<em>@type_label</em>: View any content', ['@type_label' => $type->label()]),
      ],
      "view own $type_id content" => [
        'title' => $this->t('<em>@type_label</em>: View own content', ['@type_label' => $type->label()]),
      ],
    ];
  }

}

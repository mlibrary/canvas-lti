<?php

namespace Drupal\Tests\rabbit_hole\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\rabbit_hole\BehaviorSettingsManager;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests field access to the Rabbit Hole settings field.
 *
 * The widget is only shown to users holding the "rabbit hole administer
 * <entity type>" permission. Field access has to enforce the same rule, or any
 * writer that honours field access but not the form - JSON:API, REST, bulk
 * operations - lets an ordinary editor redirect or hide an entity.
 *
 * @see rabbit_hole_entity_field_access()
 *
 * @group rabbit_hole
 */
class RabbitHoleFieldAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'rabbit_hole',
  ];

  /**
   * The node whose fields are checked.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'field', 'filter', 'node', 'rabbit_hole']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    \Drupal::service('rabbit_hole.behavior_settings_manager')->enableEntityType('node');
    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'page');

    // Take uid 0 and uid 1 so that no test user is the superuser, who
    // bypasses every permission check.
    $this->createUser([], NULL, FALSE, ['uid' => 0]);
    $this->createUser([], NULL, FALSE, ['uid' => 1]);

    $this->node = Node::create(['type' => 'page', 'title' => 'Test']);
    $this->node->save();
    $this->assertTrue($this->node->hasField(BehaviorSettingsManager::FIELD_NAME));
  }

  /**
   * Editors without the administer permission cannot edit the field.
   */
  public function testEditorCannotEditRabbitHoleField(): void {
    $editor = $this->createUser(['access content', 'edit any page content']);
    $field = $this->node->get(BehaviorSettingsManager::FIELD_NAME);

    $this->assertTrue($this->node->access('update', $editor));
    $this->assertTrue($this->node->get('title')->access('edit', $editor));
    $this->assertFalse($field->access('edit', $editor));
    $this->assertTrue($field->access('view', $editor));
  }

  /**
   * Holders of the administer permission can edit the field.
   */
  public function testAdministratorCanEditRabbitHoleField(): void {
    $administrator = $this->createUser([
      'access content',
      'edit any page content',
      'rabbit hole administer node',
    ]);

    $this->assertTrue($this->node->get(BehaviorSettingsManager::FIELD_NAME)->access('edit', $administrator));
  }

}

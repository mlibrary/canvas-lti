<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Tests quickedit.
 *
 * @group quickedit
 */
class FieldTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor',
    'contextual',
    'quickedit',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // Create note type with body field.
    $node_type = NodeType::create(['type' => 'page', 'name' => 'Page']);
    $node_type->save();
    node_add_body_field($node_type);

    $account = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'edit any page content',
      'use text format filtered_html',
      'access contextual links',
      'access in-place editing',
    ]);
    $this->drupalLogin($account);

  }

  /**
   * Tests that quickeditor works correctly for field with CKEditor.
   */
  public function testFieldWithCkeditor() {
    $body_value = '<p>Dare to be wise</p>';
    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'body' => [['value' => $body_value, 'format' => 'filtered_html']],
    ]);
    $node->save();

    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $this->drupalGet('node/' . $node->id());

    // Wait "Quick edit" button for node.
    $this->assertSession()->waitForElement('css', '[data-quickedit-entity-id="node/' . $node->id() . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $node->id() . '"]', 'Quick edit');
    // Switch to body field.
    $page->find('css', '[data-quickedit-field-id="node/' . $node->id() . '/body/en/full"]')->click();
    // Wait and click by "Blockquote" button from editor for body field.
    $this->assertSession()->waitForElementVisible('css', '.cke_button.cke_button__blockquote')->click();
    // Wait and click by "Save" button after body field was changed.
    $this->assertSession()->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    // Wait until the save occurs and the editor UI disappears.
    $this->assertSession()->assertNoElementAfterWait('css', '.cke_button.cke_button__blockquote');
    // Ensure that the changes take effect.
    $assert->responseMatches("|<blockquote>\s*$body_value\s*</blockquote>|");
  }
  /**
   * Tests quickeditor with Drag and Drop on multi-valued fields.
   */
  public function testFieldWithMultivalueFormEditor() {
    // Create a multi-valued field for 'page' nodes to use for Ajax testing.
    $field_name = 'field_text_test';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'field_text_test' => ['One', 'Two'],
    ]);
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Assign widget settings for the default form mode.
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent('field_text_test', [
        'type' => 'string_textfield',
      ])->save();

    // Assign display settings for the 'default' and 'teaser' view modes.
    $display_repository->getViewDisplay('node', 'page')
      ->setComponent('field_text_test', [
        'label' => 'hidden',
        'type' => 'string',
      ])->save();

    $node->save();

    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->drupalGet('node/' . $node->id());

    // Wait "Quick edit" button for node.
    $this->assertSession()->waitForElement('css', '[data-quickedit-entity-id="node/' . $node->id() . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $node->id() . '"]', 'Quick edit');
    $page->find('css', '[data-quickedit-field-id="node/' . $node->id() . '/field_text_test/en/full"]')->click();

    // Change fields with places.
    $this->assertSession()->waitForElementVisible('css', '.draggable');
    $one = $this->xpath("//input[@value = 'One']/../../..//a[@class='tabledrag-handle']")[0];
    $two = $this->xpath("//input[@value = 'Two']/../../..//a[@class='tabledrag-handle']")[0];
    $one->dragTo($two);

    $assert->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
  }
}

<?php

declare(strict_types=1);

namespace Drupal\Tests\fieldblock\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests on which node routes a field block finds the node to display.
 */
#[Group('fieldblock')]
class FieldBlockNodeRoutesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field',
    'node',
    'fieldblock',
    'fieldblock_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the field displayed by the field block.
   */
  const FIELD_NAME = 'field_fieldblock_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
      'new_revision' => TRUE,
    ]);

    FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Field block test field',
    ])->save();

    // The field must be on the node form to be able to test the preview route.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent(self::FIELD_NAME, ['type' => 'string_textfield'])
      ->save();

    $this->drupalPlaceBlock('fieldblock:node', [
      'region' => 'content',
      'label' => 'Field block',
      'label_from_field' => FALSE,
      'field_name' => self::FIELD_NAME,
      'formatter_id' => 'string',
      'formatter_settings' => ['link_to_entity' => FALSE],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'view all revisions',
      'create article content',
      'edit any article content',
    ]));
  }

  /**
   * Tests that the block displays the node of the canonical route.
   */
  public function testCanonicalRoute(): void {
    $node = $this->createArticle('Version one');

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Version one');
  }

  /**
   * Tests that the block displays the node of a subpath of the node.
   *
   * @see https://www.drupal.org/project/fieldblock/issues/3016860
   */
  public function testSubpathRoute(): void {
    $node = $this->createArticle('Version one');

    $this->drupalGet('/node/' . $node->id() . '/fieldblock-subpath');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Version one');
  }

  /**
   * Tests that the block displays the revision that is being viewed.
   *
   * @see https://www.drupal.org/project/fieldblock/issues/3381074
   */
  public function testRevisionRoute(): void {
    $node = $this->createArticle('Version one');
    $first_revision_id = $node->getRevisionId();

    $node->set(self::FIELD_NAME, 'Version two');
    $node->setNewRevision(TRUE);
    $node->save();

    // The canonical route displays the default revision.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Version two');
    $this->assertSession()->pageTextNotContains('Version one');

    // The revision route displays the revision that is being viewed, not the
    // default revision.
    $this->drupalGet('/node/' . $node->id() . '/revisions/' . $first_revision_id . '/view');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Version one');
    $this->assertSession()->pageTextNotContains('Version two');
  }

  /**
   * Tests that the block displays the node that is being previewed.
   *
   * @see https://www.drupal.org/project/fieldblock/issues/2605874
   */
  public function testPreviewRoute(): void {
    $this->drupalGet('/node/add/article');
    $this->submitForm([
      'title[0][value]' => 'Field block preview',
      self::FIELD_NAME . '[0][value]' => 'Version one',
    ], 'Preview');

    $this->assertSession()->addressMatches('#^/node/preview/[^/]+/full$#');
    $this->assertSession()->pageTextContains('Version one');
  }

  /**
   * Creates an article with the given value in the field the block displays.
   */
  protected function createArticle(string $value): NodeInterface {
    return $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Field block test article',
      self::FIELD_NAME => $value,
    ]);
  }

}

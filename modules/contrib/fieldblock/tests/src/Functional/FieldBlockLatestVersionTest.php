<?php

declare(strict_types=1);

namespace Drupal\Tests\fieldblock\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that a field block displays the pending revision on the latest tab.
 */
#[Group('fieldblock')]
class FieldBlockLatestVersionTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'content_moderation',
    'field',
    'node',
    'workflows',
    'fieldblock',
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

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

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
      'view latest version',
      'view any unpublished content',
      'edit any article content',
      'use editorial transition create_new_draft',
    ]));
  }

  /**
   * Tests the canonical and the latest version route of a moderated node.
   */
  public function testLatestVersionRoute(): void {
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Field block test article',
      'moderation_state' => 'published',
      self::FIELD_NAME => 'Version one',
    ]);

    // Add a pending revision, which does not become the default revision.
    $node->set(self::FIELD_NAME, 'Version two');
    $node->set('moderation_state', 'draft');
    $node->setNewRevision(TRUE);
    $node->save();

    // The canonical route displays the default revision.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('Version one');
    $this->assertSession()->pageTextNotContains('Version two');

    // The latest version route displays the pending revision.
    $this->drupalGet('/node/' . $node->id() . '/latest');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Version two');
    $this->assertSession()->pageTextNotContains('Version one');
  }

}

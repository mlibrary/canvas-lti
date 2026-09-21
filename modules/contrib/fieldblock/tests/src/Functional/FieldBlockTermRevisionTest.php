<?php

declare(strict_types=1);

namespace Drupal\Tests\fieldblock\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that revision support is not limited to nodes.
 *
 * Core declares the revision being viewed with an "entity_revision:" route
 * parameter for every revisionable entity type. The taxonomy term stands in
 * for all of them here.
 */
#[Group('fieldblock')]
class FieldBlockTermRevisionTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field',
    'taxonomy',
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
   * Tests that the block displays the term revision that is being viewed.
   */
  public function testTermRevisionRoute(): void {
    $vocabulary = $this->createVocabulary();

    FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'taxonomy_term',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'taxonomy_term',
      'bundle' => $vocabulary->id(),
      'label' => 'Field block test field',
    ])->save();

    $this->drupalPlaceBlock('fieldblock:taxonomy_term', [
      'region' => 'content',
      'label' => 'Field block',
      'label_from_field' => FALSE,
      'field_name' => self::FIELD_NAME,
      'formatter_id' => 'string',
      'formatter_settings' => ['link_to_entity' => FALSE],
    ]);

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'view all taxonomy revisions',
    ]));

    $term = $this->createTerm($vocabulary, [
      self::FIELD_NAME => 'Version one',
    ]);
    $first_revision_id = $term->getRevisionId();

    $term->set(self::FIELD_NAME, 'Version two');
    $term->setNewRevision(TRUE);
    $term->save();

    // The canonical route displays the default revision.
    $this->drupalGet($term->toUrl());
    $this->assertSession()->pageTextContains('Version two');
    $this->assertSession()->pageTextNotContains('Version one');

    // The revision route displays the revision that is being viewed.
    $this->drupalGet('/taxonomy/term/' . $term->id() . '/revision/' . $first_revision_id . '/view');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Version one');
    $this->assertSession()->pageTextNotContains('Version two');
  }

}

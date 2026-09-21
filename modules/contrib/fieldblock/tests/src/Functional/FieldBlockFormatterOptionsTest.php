<?php

declare(strict_types=1);

namespace Drupal\Tests\fieldblock\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the formatters offered by the field block configuration form.
 */
#[Group('fieldblock')]
#[RunTestsInSeparateProcesses]
class FieldBlockFormatterOptionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field',
    'node',
    'taxonomy',
    'fieldblock',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the reference field to configure the block with.
   */
  const REFERENCE_FIELD_NAME = 'field_fieldblock_reference';

  /**
   * The name of the string field to configure the block with.
   */
  const STRING_FIELD_NAME = 'field_fieldblock_string';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // A field referencing nodes. Formatters that only apply to fields
    // referencing another entity type must not be offered for it.
    $this->createArticleField(self::REFERENCE_FIELD_NAME, 'entity_reference', [
      'target_type' => 'node',
    ]);
    // A plain string field. Formatters that only apply to a specific base
    // field must not be offered for it.
    $this->createArticleField(self::STRING_FIELD_NAME, 'string');

    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));
  }

  /**
   * Tests the formatters offered for a field referencing nodes.
   */
  public function testReferenceFieldFormatterOptions(): void {
    $this->selectField(self::REFERENCE_FIELD_NAME);

    $assert_session = $this->assertSession();
    // Formatters that apply to any reference field remain available.
    $assert_session->optionExists('settings[formatter][id]', 'entity_reference_label');
    $assert_session->optionExists('settings[formatter][id]', 'entity_reference_entity_id');
    // The "Author" formatter only applies to fields referencing users, the
    // "RSS category" formatter only to fields referencing taxonomy terms.
    $assert_session->optionNotExists('settings[formatter][id]', 'author');
    $assert_session->optionNotExists('settings[formatter][id]', 'entity_reference_rss_category');
  }

  /**
   * Tests the formatters offered for a string field.
   */
  public function testStringFieldFormatterOptions(): void {
    $this->selectField(self::STRING_FIELD_NAME);

    $assert_session = $this->assertSession();
    // Formatters that apply to any string field remain available.
    $assert_session->optionExists('settings[formatter][id]', 'string');
    // The "User name" formatter only applies to the name of a user.
    $assert_session->optionNotExists('settings[formatter][id]', 'user_name');
  }

  /**
   * Creates a field on the article content type.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $field_type
   *   The type of the field.
   * @param array $settings
   *   (optional) The storage settings of the field.
   */
  protected function createArticleField(string $field_name, string $field_type, array $settings = []): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_type,
      'settings' => $settings,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $field_name,
    ])->save();
  }

  /**
   * Opens the block configuration form and selects a field to display.
   *
   * @param string $field_name
   *   The name of the field to display.
   */
  protected function selectField(string $field_name): void {
    $this->drupalGet('/admin/structure/block/add/fieldblock:node/' . $this->defaultTheme);
    $this->assertSession()->statusCodeEquals(200);

    // Without JavaScript the formatter options are rebuilt by the "Change
    // field" button.
    $this->submitForm(['settings[field_name]' => $field_name], 'Change field');
    $this->assertSession()->statusCodeEquals(200);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\iframe\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\iframe\Plugin\Field\FieldFormatter\IframeDefaultFormatter;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests default formatter.
 *
 * @group iframe
 * @coversDefaultClass \Drupal\iframe\Plugin\Field\FieldFormatter\IframeDefaultFormatter
 */
final class IframeDefaultFormatterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'iframe',
    'media_iframe',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'iframe',
    ])->save();
    FieldConfig::create([
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepo */
    $displayRepo = \Drupal::service('entity_display.repository');
    $display = $displayRepo->getViewDisplay('entity_test', 'entity_test', 'default');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = IframeDefaultFormatter::PLUGIN_ID;
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();
  }

  /**
   * Tests formatter.
   */
  public function testFormatter(): void {
    $account = $this->createUser([
      'view test entity',
    ]);
    $this->drupalLogin($account);

    $entity = EntityTest::create();
    $entity->foo = [
      'title' => 'Iframe title',
      'class' => 'iframe-class',
      'height' => '768',
      'width' => '1024',
      'url' => 'https://www.example.com/test-iframe',
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl());

    $this->assertSession()->elementExists('css', 'iframe');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'title', 'Iframe title');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'class', 'iframe-class');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'width', '1024');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'height', '768');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'https://www.example.com/test-iframe');
  }

}

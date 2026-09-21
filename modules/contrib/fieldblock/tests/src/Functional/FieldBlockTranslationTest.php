<?php

declare(strict_types=1);

namespace Drupal\Tests\fieldblock\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that a field block renders the field in the content language.
 */
#[Group('fieldblock')]
#[RunTestsInSeparateProcesses]
class FieldBlockTranslationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field',
    'node',
    'language',
    'content_translation',
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
    ]);

    FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'string',
      'translatable' => TRUE,
    ])->save();
    FieldConfig::create([
      'field_name' => self::FIELD_NAME,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Field block test field',
      'translatable' => TRUE,
    ])->save();

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Negotiate the interface language and the content language separately:
    // the interface is fixed to English, while the content language comes from
    // the url prefix. The two can only disagree in this configuration; by
    // default the content language follows the interface language.
    $config = $this->config('language.types');
    $config->set('configurable', [
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ]);
    $config->set('negotiation.language_interface.enabled', [
      LanguageNegotiationSelected::METHOD_ID => 0,
    ]);
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationUrl::METHOD_ID => 0,
    ]);
    $config->save();
    $this->config('language.negotiation')
      ->set('selected_langcode', 'en')
      ->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    \Drupal::service('content_translation.manager')
      ->setEnabled('node', 'article', TRUE);
    \Drupal::service('entity_type.manager')->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();

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
    ]));
  }

  /**
   * Tests that the block displays the translation of the content language.
   *
   * The order of the two requests matters, and this test relies on it: the
   * English page is warmed first so that the German page has a populated
   * render cache to trip over. Both pages share one route with one set of
   * parameters -- the language prefix is stripped before routing -- so the
   * "route" cache context alone would hand the English field to the German
   * page. Do not reorder these requests without reading
   * FieldBlock::getCacheContexts().
   */
  public function testContentLanguageTranslation(): void {
    $node = $this->createTranslatedArticle();

    // The interface language is English on both of these pages. Only the
    // content language differs, and only the url prefix says so.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('English value');
    $this->assertSession()->pageTextNotContains('German value');

    $this->drupalGet('de/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('German value');
    $this->assertSession()->pageTextNotContains('English value');
  }

  /**
   * Tests that the block falls back when the content language is missing.
   */
  public function testUntranslatedFallsBackToSourceLanguage(): void {
    $node = $this->createArticle('English value');

    $this->drupalGet('de/node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('English value');
  }

  /**
   * Tests that the block declares the content language cache context.
   *
   * This asserts the block's own contract rather than the page's cache
   * headers: by the time a node page is rendered the content language context
   * is on it several times over, from the node itself, so a header assertion
   * would pass whether or not this block declared anything.
   */
  public function testContentLanguageCacheContext(): void {
    $block = \Drupal::service('plugin.manager.block')
      ->createInstance('fieldblock:node', ['field_name' => self::FIELD_NAME]);

    $this->assertContains(
      'languages:' . LanguageInterface::TYPE_CONTENT,
      $block->getCacheContexts()
    );
  }

  /**
   * Creates an article with the given value in the field the block displays.
   */
  protected function createArticle(string $value): NodeInterface {
    return $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Field block test article',
      'langcode' => 'en',
      self::FIELD_NAME => $value,
    ]);
  }

  /**
   * Creates an article that is translated into German.
   */
  protected function createTranslatedArticle(): NodeInterface {
    $node = $this->createArticle('English value');
    $node->addTranslation('de', [
      'title' => 'Field block test article, translated',
      self::FIELD_NAME => 'German value',
    ]);
    $node->save();

    return $node;
  }

}

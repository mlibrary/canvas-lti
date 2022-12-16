<?php

namespace Drupal\Tests\http_client_error_status\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests that the Block Conditions work.
 *
 * @group http_client_error_status
 */
class BlockConditionPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'http_client_error_status'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * We use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create and login with user who can administer blocks.
    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));

    $default_theme = $this->config('system.theme')->get('default');
    $plugin_id = 'system_powered_by_block';

    $settings = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'footer',
      'theme' => $default_theme,
      'label' => 'Powered Test block 404',
      'label_display' => 'visible',
      'visibility' => [
        'http_client_error_status' => [
          'request_404' => 1,
        ],
      ],
    ];

    $this->drupalPlaceBlock($plugin_id, $settings);

    $settings = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'footer',
      'theme' => $default_theme,
      'label' => 'Powered Test block 403',
      'label_display' => 'visible',
      'visibility' => [
        'http_client_error_status' => [
          'request_403' => 1,
        ],
      ],
    ];

    $this->drupalPlaceBlock($plugin_id, $settings);

  }

  /**
   * Test blocks are visible on pages.
   */
  public function testBlocksVisible() {

    // Test 404 page.
    $this->drupalGet('randomstring-rD6ve4bN4a5Z');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(404);
    $assert->pageTextContains('Powered Test block 404');

    // Test 403 page.
    $this->drupalGet('admin');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Powered Test block 403');

  }

  /**
   * Test blocks are not visible on 200 page.
   */
  public function testBlockNotVisible200Page() {

    // Test 404 block.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Powered Test block 404');

    // Test 403 Block.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Powered Test block 403');

  }

}

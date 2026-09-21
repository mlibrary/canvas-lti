<?php

namespace Drupal\Tests\node_view_permissions\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\node_view_permissions\NodeAccessRebuildHelper;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for Node View Permissions.
 *
 * @group node_view_permissions
 */
#[Group('node_view_permissions')]
#[RunTestsInSeparateProcesses]
class NodeViewPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_view_permissions', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    NodeAccessRebuildHelper::rebuild();
  }

  /**
   * Test users with a "view own content" permission.
   *
   * Ensure that these users can view nodes of this type that they created.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testViewOwn(): void {
    $user1 = $this->drupalCreateUser(['view own article content']);
    $user2 = $this->drupalCreateUser(['view own article content']);

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'uid' => $user1->id(),
    ]);

    $lookup = [
      [$user1, Response::HTTP_OK],
      [$user2, Response::HTTP_FORBIDDEN],
    ];

    foreach ($lookup as [$user, $expected]) {
      $this->drupalLogin($user);

      $this->drupalGet(Url::fromRoute('entity.node.canonical', [
        'node' => $node->id(),
      ]));

      $this->assertSession()->statusCodeEquals($expected);
    }
  }

  /**
   * Test users with a "view any content" permission.
   *
   * Ensure that these users can view any node of this type, including ones
   * that they did not create.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testViewAny(): void {
    $user1 = $this->drupalCreateUser(['view any article content']);
    $user2 = $this->drupalCreateUser(['view any article content']);

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'uid' => $user1->id(),
    ]);

    foreach ([$user1, $user2] as $user) {
      $this->drupalLogin($user);

      $this->drupalGet(Url::fromRoute('entity.node.canonical', [
        'node' => $node->id(),
      ]));

      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    }
  }

  /**
   * Test that own-content access is not granted to anonymous users.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAnonymousDoesNotViewOwnContent(): void {
    user_role_grant_permissions('anonymous', ['view own article content']);

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'uid' => 0,
    ]);

    NodeAccessRebuildHelper::rebuild();

    $this->drupalGet(Url::fromRoute('entity.node.canonical', [
      'node' => $node->id(),
    ]));

    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Test grant generation across multiple node types and languages.
   */
  public function testNodeGrantsIncludeMultipleNodeTypesAndLanguages(): void {
    $this->drupalCreateContentType(['type' => 'page']);

    \Drupal::service('config.factory')->getEditable('language.entity.fr')
      ->set('id', 'fr')
      ->set('label', 'French')
      ->set('direction', LanguageInterface::DIRECTION_LTR)
      ->save();

    \Drupal::service('language_manager')->reset();

    $user = $this->drupalCreateUser([
      'view own article content',
      'view own page content',
      'view any article content',
      'view any page content',
    ]);

    $grants = node_view_permissions_node_grants($user, 'view');

    $expected_own_gid = (int) $user->id();

    $this->assertSame([$expected_own_gid], $grants['view_own_article_content']);
    $this->assertSame([$expected_own_gid], $grants['view_own_page_content']);
    $this->assertSame([1], $grants['view_any_article_content']);
    $this->assertSame([1], $grants['view_any_page_content']);

    $this->assertSame([$expected_own_gid], $grants['view_own_article_fr_content']);
    $this->assertSame([$expected_own_gid], $grants['view_own_page_fr_content']);
    $this->assertSame([1], $grants['view_any_article_fr_content']);
    $this->assertSame([1], $grants['view_any_page_fr_content']);
  }

  /**
   * Test that node view permissions are discovered for content types.
   */
  public function testNodeViewPermissionsAreDiscovered(): void {
    $permissions = \Drupal::service('user.permissions')->getPermissions();

    $this->assertArrayHasKey('view any article content', $permissions);
    $this->assertArrayHasKey('view own article content', $permissions);

    $this->assertSame(
      '<em>Article</em>: View any content',
      (string) $permissions['view any article content']['title']
    );
    $this->assertSame(
      '<em>Article</em>: View own content',
      (string) $permissions['view own article content']['title']
    );
  }

}

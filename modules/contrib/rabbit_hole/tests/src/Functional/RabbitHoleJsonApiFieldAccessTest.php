<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\rabbit_hole\BehaviorSettingsManager;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;

/**
 * Tests that JSON:API cannot be used to bypass the Rabbit Hole permission.
 *
 * The widget only offers the Rabbit Hole settings to users holding the
 * "rabbit hole administer node" permission. JSON:API honours field access
 * rather than the form, so without field access an editor with only
 * "edit any <type> content" could turn a node into an open redirect or hide it.
 *
 * @see rabbit_hole_entity_field_access()
 *
 * @group rabbit_hole
 */
class RabbitHoleJsonApiFieldAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'rabbit_hole',
    'jsonapi',
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The node being patched.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Core ships JSON:API read-only; the reported bypass needs writes.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->container->get('rabbit_hole.behavior_settings_manager')->enableEntityType('node');
    BehaviorSettings::loadByEntityTypeBundle('node', 'page')
      ->setAction('display_page')
      ->save();
    $this->container->get('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'page');
    // JSON:API registers its routes per bundle.
    $this->container->get('router.builder')->rebuild();

    $this->node = Node::create(['type' => 'page', 'title' => 'Test']);
    $this->node->save();
  }

  /**
   * Sends a JSON:API PATCH request setting a redirect on the node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  protected function patchRedirect($account) {
    $url = Url::fromRoute('jsonapi.node--page.individual', ['entity' => $this->node->uuid()])->setAbsolute();
    return $this->getHttpClient()->request('PATCH', $url->toString(), [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::AUTH => [$account->getAccountName(), $account->passRaw],
      RequestOptions::HEADERS => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
      RequestOptions::BODY => json_encode([
        'data' => [
          'type' => 'node--page',
          'id' => $this->node->uuid(),
          'attributes' => [
            BehaviorSettingsManager::FIELD_NAME => [
              'action' => 'page_redirect',
              'settings' => [
                'redirect' => 'https://example.com/',
                'redirect_code' => 301,
              ],
            ],
          ],
        ],
      ]),
    ]);
  }

  /**
   * Reloads the node from storage.
   */
  protected function reloadNode() {
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$this->node->id()]);
    return Node::load($this->node->id());
  }

  /**
   * An editor without the administer permission is refused.
   */
  public function testEditorIsRefused(): void {
    $editor = $this->drupalCreateUser(['access content', 'edit any page content']);

    $response = $this->patchRedirect($editor);
    $this->assertSame(403, $response->getStatusCode());
    $this->assertStringContainsString(BehaviorSettingsManager::FIELD_NAME, (string) $response->getBody());

    $node = $this->reloadNode();
    $this->assertNotSame('page_redirect', $node->get(BehaviorSettingsManager::FIELD_NAME)->action);

    // The node is still displayed rather than redirected.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($node->toUrl());
  }

  /**
   * A holder of the administer permission can set the redirect.
   */
  public function testAdministratorIsAllowed(): void {
    $administrator = $this->drupalCreateUser([
      'access content',
      'edit any page content',
      'rabbit hole administer node',
    ]);

    $response = $this->patchRedirect($administrator);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

    $node = $this->reloadNode();
    $this->assertSame('page_redirect', $node->get(BehaviorSettingsManager::FIELD_NAME)->action);
    $this->assertSame('https://example.com/', $node->get(BehaviorSettingsManager::FIELD_NAME)->settings['redirect']);
  }

}

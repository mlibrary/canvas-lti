<?php

namespace Drupal\Tests\token\Functional;

use Drupal\block\Entity\Block;
use Drupal\Core\Url;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test the [current-page:*] tokens.
 *
 * @group token
 */
class TokenCurrentPageTest extends TokenTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'block'];

  function testCurrentPageTokens() {
    // Cache clear is necessary because the frontpage was already cached by an
    // initial request.
    $this->rebuildAll();
    $tokens = [
      '[current-page:title]' => 'Log in',
      '[current-page:url]' => Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString(),
      '[current-page:url:absolute]' => Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString(),
      '[current-page:url:relative]' => Url::fromRoute('user.login')->toString(),
      '[current-page:url:path]' => '/user/login',
      '[current-page:url:args:value:0]' => 'user',
      '[current-page:url:args:value:1]' => 'login',
      '[current-page:url:args:value:2]' => NULL,
      '[current-page:url:unaliased]' => Url::fromRoute('user.login', [], ['absolute' => TRUE, 'alias' => TRUE])->toString(),
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => NULL,
      '[current-page:query:bar]' => NULL,
      '[current-page:node:nid]' => NULL,
      '[current-page:taxonomy_term:tid]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'user',
      '[current-page:arg:1]' => 'login',
      '[current-page:arg:2]' => NULL,
    ];
    $this->assertPageTokens('user/login', $tokens);

    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode(['title' => 'Node title', 'path' => ['alias' => '/node-alias']]);
    $tokens = [
      '[current-page:title]' => 'Node title',
      '[current-page:url]' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      '[current-page:url:absolute]' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
      '[current-page:url:relative]' => $node->toUrl()->toString(),
      '[current-page:url:alias]' => '/node-alias',
      '[current-page:url:args:value:0]' => 'node-alias',
      '[current-page:url:args:value:1]' => NULL,
      '[current-page:url:unaliased]' => $node->toUrl('canonical', ['absolute' => TRUE, 'alias' => TRUE])->toString(),
      '[current-page:url:unaliased:args:value:0]' => 'node',
      '[current-page:url:unaliased:args:value:1]' => $node->id(),
      '[current-page:url:unaliased:args:value:2]' => NULL,
      '[current-page:page-number]' => 1,
      '[current-page:query:foo]' => 'bar',
      '[current-page:query:bar]' => NULL,
      '[current-page:node:nid]' => $node->id(),
      '[current-page:taxonomy_term:tid]' => NULL,
      // Deprecated tokens
      '[current-page:arg:0]' => 'node',
      '[current-page:arg:1]' => 1,
      '[current-page:arg:2]' => NULL,
    ];
    $this->assertPageTokens("/node/{$node->id()}", $tokens, [], ['url_options' => ['query' => ['foo' => 'bar']]]);
  }

  /*
   * Test tokens like [current-page:node:nid].
   */
  public function testCurrentPageObjectTokens() {
    // We are especially interested in testing caching.
    // Imitate strategy of UrlTest::testBlockUrlTokenReplacement().
    $this->drupalCreateContentType(['type' => 'page']);
    // Put the first node in a variable for later manipulation.
    $node1 = $this->drupalCreateNode(['title' => 'Node the First']);
    $this->drupalCreateNode(['title' => 'Node the Second']);
    $vocab = $this->createVocabulary();
    $this->createTerm($vocab, ['name' => 'Term the First']);
    $this->createTerm($vocab, ['name' => 'Term the Second']);
    // Place a standard block and use a token in the label.
    $edit = [
      'id' => 'token_url_test_block',
      'label' => 'label',
      'label_display' => TRUE,
    ];
    $this->placeBlock('system_powered_by_block', $edit);
    $block = Block::load('token_url_test_block');

    $tests = [];
    // Chained node token.
    $tests[] = [
      'token' => 'prefix_[current-page:node:title]_suffix',
      'node1' => 'prefix_Node the First_suffix',
      'node2' => 'prefix_Node the Second_suffix',
      'term1' => 'prefix_[current-page:node:title]_suffix',
      'term2' => 'prefix_[current-page:node:title]_suffix',
      'node1_new_title' => 'New Title',
      'node1_new_expected' => 'prefix_New Title_suffix',
    ];
    // Chained taxonomy_term token.
    $tests[] = [
      'token' => 'prefix_[current-page:taxonomy_term:tid]_suffix',
      'node1' => 'prefix_[current-page:taxonomy_term:tid]_suffix',
      'node2' => 'prefix_[current-page:taxonomy_term:tid]_suffix',
      'term1' => 'prefix_1_suffix',
      'term2' => 'prefix_2_suffix',
    ];
    // Show that 'term' token does not work.
    // The current-page:object token does not use the token mapper service.
    $tests[] = [
      'token' => 'prefix_[current-page:term:tid]_suffix',
      'node1' => 'prefix_[current-page:term:tid]_suffix',
      'node2' => 'prefix_[current-page:term:tid]_suffix',
      'term1' => 'prefix_[current-page:term:tid]_suffix',
      'term2' => 'prefix_[current-page:term:tid]_suffix',
    ];
    // Unchained node token.
    $tests[] = [
      'token' => 'prefix_[current-page:node]_suffix',
      'node1' => 'prefix_Node the First_suffix',
      'node2' => 'prefix_Node the Second_suffix',
      'term1' => 'prefix_[current-page:node]_suffix',
      'term2' => 'prefix_[current-page:node]_suffix',
      'node1_new_title' => 'Updated Title',
      'node1_new_expected' => 'prefix_Updated Title_suffix',
    ];

    $assert_session = $this->assertSession();
    foreach ($tests as $test) {
      // Set the block label.
      $block->getPlugin()->setConfigurationValue('label', $test['token']);
      $block->save();

      // Then visit each entity, testing cache context.
      $this->drupalGet('node/1');
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['node1']);

      $this->drupalGet('node/2');
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['node2']);

      $this->drupalGet('taxonomy/term/1');
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['term1']);

      $this->drupalGet('taxonomy/term/2');
      $assert_session->elementContains('css', '#block-token-url-test-block', $test['term2']);

      if (isset($test['node1_new_title'])) {
        // Update node1 and revisit, testing cache tags.
        $node1->set('title', $test['node1_new_title'])->save();
        $this->drupalGet('node/1');
        $assert_session->elementContains('css', '#block-token-url-test-block', $test['node1_new_expected']);
        // Change to to original title.
        $node1->set('title', 'Node the First')->save();
      }
    }
  }
}

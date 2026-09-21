<?php

namespace Drupal\Tests\node_view_permissions\Functional;

use Drupal\node_view_permissions\NodeAccessRebuildHelper;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests install-time behavior for Node View Permissions.
 *
 * @group node_view_permissions
 */
#[Group('node_view_permissions')]
#[RunTestsInSeparateProcesses]
class NodeViewPermissionsInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that hook_install() completes during module installation.
   *
   * The module is intentionally not listed in $modules. Installing it inside
   * the test method exercises node_view_permissions_install(); any exception
   * or fatal error in the install hook fails this test at the install() call.
   */
  public function testInstallHookRunsAndMarksAccessForRebuild(): void {
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $module_handler = \Drupal::moduleHandler();
    $this->assertFalse($module_handler->moduleExists('node_view_permissions'));

    $this->assertTrue(\Drupal::service('module_installer')->install(['node_view_permissions']));
    $this->assertTrue($module_handler->moduleExists('node_view_permissions'));

    $anonymous = Role::load(RoleInterface::ANONYMOUS_ID);
    $authenticated = Role::load(RoleInterface::AUTHENTICATED_ID);

    $this->assertTrue($anonymous->hasPermission('view any article content'));
    $this->assertTrue($authenticated->hasPermission('view any article content'));
    $this->assertTrue(NodeAccessRebuildHelper::needsRebuild());
  }

}

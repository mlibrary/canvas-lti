<?php

namespace Drupal\custom_drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\DrupalKernel;
use Drupal\views\Views;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class CustomDrushCommands extends DrushCommands {

  /**
   * Render a url
   *
   * @param $url
   *   The path to render
   * @param $uid
   *   The uid to run as
   *
   * @command custom_drush:render_page
   * @aliases render,ren,render-page,custom_drush-render_page
   */
  public function renderPage($url = '', $uid = 0) {
    if ($uid) {
      \Drupal::getContainer()
        ->get('current_user')
        ->setAccount(\Drupal\user\Entity\User::load($uid));
    }

    $autoloader = \Drupal::service('class_loader');
    $kernel = new DrupalKernel('prod', $autoloader);
    $kernel->setContainer(\Drupal::getContainer());
    $request = \Symfony\Component\HttpFoundation\Request::create(
      $url,
      'GET',
      [], [], [],
      ['SCRIPT_FILENAME' => '/index.php']
    );
    $response = $kernel->handle($request);
    print $response->getContent();
  }

  /**
   * Say hello.
   *
   * @param $name
   *   The name for saying hello
   * @validate-module-enabled custom_drush
   *
   * @command custom_drush:say_hello
   * @aliases say:hello,say-hello,custom_drush-say_hello
   */
  public function sayHello($name) {
    $this->output()->writeln('Hello ' . $name . ' !');
  }

  /**
   * Build site.
   *
   * @param $site
   *   The name of the site to build
   * @validate-module-enabled custom_drush,custom_uml_mail,build_hooks
   *
   * @command custom_drush:build_site
   * @aliases build-site
   */
  public function buildSite($site) {
    $env = \Drupal::entityTypeManager()->getStorage('frontend_environment')->load($site);
    \Drupal::service('build_hooks.trigger')->triggerBuildHookForEnvironment($env);
  }

  /**
   * Run UM Guide Update.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option clear_terms
   *   Set to 1 to clear out terms
   * @validate-module-enabled custom_guides
   *
   * @command custom_drush:update_guides
   * @aliases update-guides,custom_drush-update_guides
   */
  public function updateGuides(array $options = ['clear_terms' => FALSE]) {
    _custom_guides($options);
  }

  /**
   * Run UM Database Update.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option clear_terms
   *   Set to 1 to clear out terms
   * @validate-module-enabled custom_guides
   *
   * @command custom_drush:update_databases
   * @aliases update-databases,custom_drush-update_databases
   */
  public function updateDatabases(array $options = ['clear_terms' => FALSE]) {
    _custom_databases($options);
  }

  /**
   * Run UM Reserves Update.
   *
   * @validate-module-enabled custom_extras
   *
   * @command custom_drush:update_reserves
   * @aliases update-reserves,custom_drush-update_reserves
   */
  public function updateReserves() {
    _custom_reserves();
  }

  /**
   * Write to s3.
   * @param file
   *   File to copy OR Name of file.
   * @param data
   *   Data to write, defaults to empty for file copy.
   * @param bucket
   *   Name of bucket, default provided.
   * @validate-module-enabled custom_s3,custom_uml_mail
   *
   * @command custom_drush:write_file_to_s3
   * @aliases write-file-s3,custom_drush-write_file_to_s3
   */
  public function writeFileToS3($file, $data = '', $bucket = 'lit-umich-data-feeds') {
    _custom_s3_write_data($file, $data, $bucket);
  }

  /**
   * Render and export a view
   *
   * @param $view_name
   *   The view to use
   * @param $display_name
   *   The view display to use
   * @param $uid
   *   The uid to run as
   * @validate-module-enabled views
   *
   * @command custom_drush:render_view
   * @aliases render-view,custom_drush-render_view
   */
  public function renderView($view_name, $display_name, $uid=0) {
    if ($uid) {
      \Drupal::getContainer()
        ->get('current_user')
        ->setAccount(\Drupal\user\Entity\User::load($uid));
    }

    $view = Views::getView($view_name);
    if (is_object($view)) {
      $view->setArguments(array());
      $view->setDisplay($display_name);
      $view->preExecute();
      $view->execute();
      $content = $view->render();
    }
    print $content['#markup']->__toString();
  }

  /**
   * Run UM Staff User Feed
   *
   * @validate-module-enabled custom_user_feed,custom_uml_mail
   *
   * @command custom_drush:update_user_feed
   * @aliases update-user-feed,custom_drush-update_user_feed
   */
  public function updateUserFeed() {
    _custom_user_feed();
  }

}

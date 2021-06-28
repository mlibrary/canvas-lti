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
   * Run UM Staff User and Department Update. Add a 0/FALSE after to not send welcome emails. Add any arg after to clear dept parents.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option send_welcome_messages
   *   Set to 0 to not send welcome emails
   * @option send_welcome_messages_staff
   *   Set to 0 to not send welcome emails for regular staff
   * @option send_welcome_messages_temp
   *   Set to 0 to not send welcome emails for temporary staff
   * @option send_welcome_messages_student
   *   Set to 0 to not send welcome emails for student staff
   * @option clear_parents
   *   Set to 1 to clear out parent dept associations
   * @option clear_depts
   *   Set to 1 to clear out dept associations on users
   * @validate-module-enabled custom_users_depts,custom_uml_mail
   *
   * @command custom_drush:update_users_depts
   * @aliases update-users-depts,custom_drush-update_users_depts
   */
  public function updateUsersDepts(array $options = ['send_welcome_messages' => FALSE, 'send_welcome_messages_staff' => TRUE, 'send_welcome_messages_temp' => FALSE, 'send_welcome_messages_student' => FALSE, 'clear_parents' => FALSE, 'clear_depts' => FALSE]) {
    _custom_users_depts($options);
  }

  /**
   * Run UM Staff User Update. Add a 0/FALSE after to not send welcome emails
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option send_welcome_messages
   *   Set to 0 to not send welcome emails for regular staff
   * @option send_welcome_messages_staff
   *   Set to 0 to not send welcome emails for regular staff
   * @option send_welcome_messages_temp
   *   Set to 0 to not send welcome emails for temporary staff
   * @option send_welcome_messages_student
   *   Set to 0 to not send welcome emails for student staff
   * @option clear_depts
   *   Set to 1 to clear out dept associations on users
   * @validate-module-enabled custom_users_depts,custom_uml_mail
   *
   * @command custom_drush:update_users
   * @aliases update-users,custom_drush-update_users
   */
  public function updateUsers(array $options = ['send_welcome_messages' => FALSE, 'send_welcome_messages_staff' => TRUE, 'send_welcome_messages_temp' => FALSE, 'send_welcome_messages_student' => FALSE, 'clear_depts' => FALSE]) {
    _custom_users($options);
  }

  /**
   * Run UM Staff Department Update. Add any arg after to clear dept parents.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option clear_parents
   *   Set to 1 to clear out parent dept associations
   * @validate-module-enabled custom_users_depts,custom_uml_mail
   *
   * @command custom_drush:update_depts
   * @aliases update-depts,custom_drush-update_depts
   */
  public function updateDepts(array $options = ['clear_parents' => FALSE]) {
    _custom_depts($options);
  }

  /**
   * Run UM Staff Shortcode Update.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option clear_terms
   *   Set to 1 to clear out terms
   * @validate-module-enabled custom_shortcodes,custom_uml_mail
   *
   * @command custom_drush:update_shortcodes
   * @aliases update-shortcodes,custom_drush-update_shortcodes
   */
  public function updateShortcodes(array $options = ['clear_terms' => FALSE]) {
    _custom_shortcodes($options);
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
   * Run Convert Field Collections to Paragraphs.
   *
   * @validate-module-enabled custom_shortcodes,custom_uml_mail
   *
   * @command custom_drush:copy_fc_to_paragraphs
   * @aliases copy-fc-to-paragraphs
   */
  public function copyFieldCollectionsToParagraphs() {
    _custom_shortcodes_fc_to_paragraphs();
  }

  /**
   * Run Truncate Field Collections tables.
   *
   * @validate-module-enabled custom_shortcodes,custom_uml_mail
   *
   * @command custom_drush:truncate_fc_tables
   * @aliases truncate-fc-tables
   */
  public function truncateFieldCollectionsTables() {
    _custom_shortcodes_truncate_fc_tables();
  }

  /**
   * Remove UM Staff Shortcodes.
   *
   * @validate-module-enabled custom_shortcodes,custom_uml_mail
   *
   * @command custom_drush:remove_shortcodes
   * @aliases remove-shortcodes,custom_drush-remove_shortcodes
   */
  public function removeShortcodes() {
    _custom_shortcodes_delete();
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
   * Run UM Staff PTF HR Data Update.
   *
    * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   * @option none
   *   none
   * @validate-module-enabled custom_shortcodes_sub_sites,custom_users_depts_sub_sites,custom_uml_mail
   *
   * @command custom_drush:update_hr_data
   * @aliases update-ptf-hr-data,custom_drush-update_hr_data
   */
  public function updateHRData(array $options = ['clear_current' => FALSE, 'clear_history' => FALSE]) {
    _custom_ptf_import_hr_data($options);
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
   * Run UM Staff User Update on Sub sites
   *
   * @validate-module-enabled custom_users_depts_sub_sites,custom_uml_mail
   *
   * @command custom_drush:update_users_sub_site
   * @aliases update-users-sub-site,custom_drush-update_users_sub_site
   */
  public function updateUsersSubSite() {
    _custom_users_sub_sites();
  }

  /**
   * Run UM Staff Dept Update on Sub sites
   *
   * @validate-module-enabled custom_users_depts_sub_sites,custom_uml_mail
   *
   * @command custom_drush:update_depts_sub_site
   * @aliases update-depts-sub-site,custom_drush-update_depts_sub_site
   */
  public function updateDeptsSubSite() {
    _custom_depts_sub_sites();
  }

  /**
   * Run UM Staff Locations Update on Sub sites
   *
   * @validate-module-enabled custom_locations_sub_sites,custom_uml_mail
   *
   * @command custom_drush:update_locations_sub_site
   * @aliases update-locations-sub-site,custom_drush-update_locations_sub_site
   */
  public function updateLocationsSubSite() {
    _custom_locations_sub_sites();
  }

  /**
   * Run UM Staff Shortcode Update on Sub sites
   *
   * @validate-module-enabled custom_shortcodes_sub_sites,custom_uml_mail
   *
   * @command custom_drush:update_shortcodes_sub_site
   * @aliases update-shortcodes-sub-site,custom_drush-update_shortcodes_sub_site
   */
  public function updateShortcodesSubSite() {
    _custom_shortcodes_sub_sites();
  }

  /**
   * Run UM Staff Location Update.
   *
   * @validate-module-enabled custom_locations,custom_uml_mail
   *
   * @command custom_drush:update_locations
   * @aliases update-locations,custom_drush-update_locations
   */
  public function updateLocations() {
    _custom_locations();
  }

}

<?php
/**
 * @file Contains the code to generate the custom drush commands.
 */

/**
 * Implements hook_drush_init().
 */
function custom_drush_drush_init() {
  if (function_exists('db_query')) {
    db_query('set session wait_timeout=28800');
  }
}

/**
 * Implements hook_drush_command().
 */
function custom_drush_drush_command() {
  $items = array();
  $items['custom_drush-render_page'] = [
    'description' => 'Render a url',
    'arguments' => [
      'url' => 'The path to render',
      'uid' => 'The uid to run as',
    ],
    'drupal dependencies' => [],
    'aliases' => ['render', 'ren', 'render-page'],
  ];
  $items['custom_drush-say_hello'] = [
    'description' => 'Say hello.',
    'arguments' => [
      'name' => 'The name for saying hello',
    ],
    'drupal dependencies' => ['custom_drush'],
    'aliases' => ['say:hello', 'say-hello'],
  ];

  $items['custom_drush-build_site'] = [
    'description' => 'Build site.',
    'arguments' => [
      'site' => 'The name of the site',
    ],
    'drupal dependencies' => ['custom_drush,custom_uml_mail,build_hooks'],
    'aliases' => ['build-site'],
  ];

  $items['custom_drush-truncate_fc_tables'] = [
    'description' => 'Truncate field collection tables for removal.',
    'arguments' => [
      'site' => 'The name of the site',
    ],
    'drupal dependencies' => ['custom_drush,custom_uml_mail,custom_shortcodes'],
    'aliases' => ['truncate-fc-tables'],
  ];

  $items['custom_drush-copy_fc_to_paragraphs'] = [
    'description' => 'Copy field collections to paragraphs.',
    'arguments' => [
      'site' => 'The name of the site',
    ],
    'drupal dependencies' => ['custom_drush,custom_uml_mail,custom_shortcodes'],
    'aliases' => ['copy-fc-to-paragraphs'],
  ];

  //run user and dept updates
  $items['custom_drush-update_users_depts'] = [
    'description' => 'Run UM Staff User and Department Update. Add a 0/FALSE after to not send welcome emails. Add any arg after to clear dept parents.',
    'options' => [
      'send_welcome_messages' => 'Set to 0 to not send welcome emails',
      'send_welcome_messages_staff' => 'Set to 0 to not send welcome emails for regular staff',
      'send_welcome_messages_temp' => 'Set to 0 to not send welcome emails for temporary staff',
      'send_welcome_messages_student' => 'Set to 0 to not send welcome emails for student staff',
      'clear_parents' => 'Set to 1 to clear out parent dept associations',
      'clear_depts' => 'Set to 1 to clear out dept associations for users',
    ],
    'drupal dependencies' => ['custom_users_depts','custom_uml_mail'],
    'aliases' => ['update-users-depts'],
  ];
  $items['custom_drush-update_users'] = [
    'description' => 'Run UM Staff User Update. Add a 0/FALSE after to not send welcome emails',
    'options' => [
      'send_welcome_messages' => 'Set to 0 to not send welcome emails for regular staff',
      'send_welcome_messages_staff' => 'Set to 0 to not send welcome emails for regular staff',
      'send_welcome_messages_temp' => 'Set to 0 to not send welcome emails for temporary staff',
      'send_welcome_messages_student' => 'Set to 0 to not send welcome emails for student staff',
      'clear_depts' => 'Set to 1 to clear out dept associations for users',
    ],
    'drupal dependencies' => ['custom_users_depts','custom_uml_mail'],
    'aliases' => ['update-users']
  ];
  $items['custom_drush-update_depts'] = [
    'description' => 'Run UM Staff Department Update. Add any arg after to clear dept parents.',
    'options' => [
      'clear_parents' => 'Set to 1 to clear out parent dept associations',
    ],
    'drupal dependencies' => ['custom_users_depts','custom_uml_mail'],
    'aliases' => ['update-depts']
  ];

  //run shortcode updates
  $items['custom_drush-update_shortcodes'] = [
    'description' => 'Run UM Staff Shortcode Update.',
    'options' => [
      'clear_terms' => 'Set to 1 to clear out terms',
    ],
    'drupal dependencies' => ['custom_shortcodes','custom_uml_mail'],
    'aliases' => ['update-shortcodes']
  ];
  //remove shortcodes
  $items['custom_drush-remove_shortcodes'] = [
    'description' => 'Remove UM Staff Shortcodes.',
    'options' => [
    ],
    'drupal dependencies' => ['custom_shortcodes','custom_uml_mail'],
    'aliases' => ['remove-shortcodes']
  ];

  //run ptf updates
  $items['custom_drush-update_hr_data'] = [
    'description' => 'Run UM Staff PTF HR Data Update.',
    'options' => [
      'none' => 'none',
    ],
    'drupal dependencies' => ['custom_shortcodes_sub_sites','custom_users_depts_sub_sites','custom_uml_mail'],
    'aliases' => ['update-ptf-hr-data']
  ];

  //write file to s3
  $items['custom_drush-write_file_to_s3'] = [
    'description' => 'Write a file to s3.',
    'arguments' => [
      'file' => 'file to copy or name of file',
      'data' => 'data to write, defaults to empty for file copy',
      'bucket' => 'name of bucket, uses default - here for later',
    ],
    'drupal dependencies' => ['custom_s3','custom_uml_mail'],
    'aliases' => ['write-to-s3']
  ];

  //render a view
  $items['custom_drush-render_view'] = [
    'description' => 'Render and export a view',
    'arguments' => [
      'view' => 'The view to use',
      'display' => 'The view display to use',
      'uid' => 'The uid to run as',
    ],
    'drupal dependencies' => ['views'],
    'aliases' => ['render-view']
  ];

  //sub site user update
  $items['custom_drush-update_users_sub_site'] = [
    'description' => 'Run UM Staff User Update on Sub sites',
    'options' => [
    ],
    'drupal dependencies' => ['custom_users_depts_sub_sites','custom_uml_mail'],
    'aliases' => ['update-users-sub-site']
  ];

  //sub site dept update
  $items['custom_drush-update_depts_sub_site'] = [
    'description' => 'Run UM Staff Dept Update on Sub sites',
    'options' => [
    ],
    'drupal dependencies' => ['custom_users_depts_sub_sites','custom_uml_mail'],
    'aliases' => ['update-depts-sub-site']
  ];

  //sub site location update
  $items['custom_drush-update_locations_sub_site'] = [
    'description' => 'Run UM Staff Locations Update on Sub sites',
    'options' => [
    ],
    'drupal dependencies' => ['custom_locations_sub_sites','custom_uml_mail'],
    'aliases' => ['update-locations-sub-site']
  ];

  //sub site shortcode update
  $items['custom_drush-update_shortcodes_sub_site'] = [
    'description' => 'Run UM Staff Shortcode Update on Sub sites',
    'options' => [
    ],
    'drupal dependencies' => ['custom_shortcodes_sub_sites','custom_uml_mail'],
    'aliases' => ['update-shortcodes-sub-site']
  ];

  //guide update
  $items['custom_drush-update_guides'] = [
    'description' => 'Run UM Guide Update',
    'options' => [
    ],
    'drupal dependencies' => [],
    'aliases' => ['update-guides']
  ];

  //run location updates
  $items['custom_drush-update_locations'] = [
    'description' => 'Run UM Staff Location Update.',
    'options' => [
    ],
    'drupal dependencies' => ['custom_locations','custom_uml_mail'],
    'aliases' => ['update-locations']
  ];

  return $items;
}

function drush_custom_drush_render_page($url = '', $uid = 0) {
  if ($uid) {
    \Drupal::getContainer()
      ->get('current_user')
      ->setAccount(\Drupal\user\Entity\User::load($uid));
  }

  $autoloader = \Drupal::service('class_loader');
  $kernel = new Drupal\Core\DrupalKernel('prod', $autoloader);
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
 * Call back function drush_custom_drush_command_say_hello()
 * The call back function name in the  following format
 *   drush_{module_name}_{item_id_for_command}()
 */
function drush_custom_drush_say_hello($name = '') {
  drush_print('Hello ' . $name . ' !');
}

function drush_custom_drush_update_users_depts() {
  $args['send_welcome_messages'] = drush_get_option('send_welcome_messages', FALSE);
  $args['send_welcome_messages_staff'] = drush_get_option('send_welcome_messages_staff', TRUE);
  $args['send_welcome_messages_temp'] = drush_get_option('send_welcome_messages_temp', FALSE);
  $args['send_welcome_messages_student'] = drush_get_option('send_welcome_messages_student', FALSE);
  $args['clear_parents'] = drush_get_option('clear_parents', FALSE);
  $args['clear_depts'] = drush_get_option('clear_depts', FALSE);
  _custom_users_depts($args);
}
function drush_custom_drush_update_users() {
  $args['send_welcome_messages'] = drush_get_option('send_welcome_messages', FALSE);
  $args['send_welcome_messages_staff'] = drush_get_option('send_welcome_messages_staff', TRUE);
  $args['send_welcome_messages_temp'] = drush_get_option('send_welcome_messages_temp', FALSE);
  $args['send_welcome_messages_student'] = drush_get_option('send_welcome_messages_student', FALSE);
  $args['clear_depts'] = drush_get_option('clear_depts', FALSE);
  _custom_users($args);
}
function drush_custom_drush_update_depts() {
  $args['clear_parents'] = drush_get_option('clear_parents', FALSE);
  _custom_depts($args);
}
function drush_custom_drush_update_shortcodes() {
  $args['clear_terms'] = drush_get_option('clear_terms', FALSE);
  _custom_shortcodes($args);
}
function drush_custom_drush_update_guides() {
  $args['clear_terms'] = drush_get_option('clear_terms', FALSE);
  _custom_guides($args);
}
function drush_custom_drush_remove_shortcodes() {
  $args = array();
  _custom_shortcodes_delete($args);
}
function drush_custom_drush_update_hr_data() {
  $args['clear_current'] = drush_get_option('clear_current', FALSE);
  $args['clear_history'] = drush_get_option('clear_history', FALSE);
  _custom_ptf_import_hr_data($args);
}
function drush_custom_drush_update_users_sub_site() {
  _custom_users_sub_sites($args);
}
function drush_custom_drush_update_depts_sub_site() {
  _custom_depts_sub_sites($args);
}
function drush_custom_drush_update_locations_sub_site() {
  _custom_locations_sub_sites($args);
}
function drush_custom_drush_update_shortcodes_sub_site() {
  _custom_shortcodes_sub_sites($args);
}
function drush_custom_drush_update_locations() {
  _custom_locations();
}
function drush_custom_drush_render_view($view_name, $display_name, $uid = 0) {
  if ($uid) {
    \Drupal::getContainer()
      ->get('current_user')
      ->setAccount(\Drupal\user\Entity\User::load($uid));
  }

  $view = \Drupal\views\Views::getView($view_name);
  if (is_object($view)) {
    $view->setArguments(array());
    $view->setDisplay($display_name);
    $view->preExecute();
    $view->execute();
    $content = $view->render();
  }
  print $content['#markup']->__toString();
}

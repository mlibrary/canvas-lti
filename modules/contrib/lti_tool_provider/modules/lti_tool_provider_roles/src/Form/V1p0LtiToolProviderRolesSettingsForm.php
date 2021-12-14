<?php

namespace Drupal\lti_tool_provider_roles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implementation V1p0LtiToolProviderRolesSettingsForm class.
 *
 * @package Drupal\lti_tool_provider_roles\Form
 */
class V1p0LtiToolProviderRolesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'v1p0_lti_tool_provider_roles_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $filter = ''): array {
    $settings = $this->config('lti_tool_provider_roles.settings');
    $mapped_roles = $settings->get('v1p0_mapped_roles');
    $lti_roles = $this->config('lti_tool_provider.settings')->get('v1p0_lti_roles');

    $form['mapped_roles'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#caption' => t(
        'This page allows you to map LTI roles to Drupal user roles. This is applied every time a user logs in via LTI. Please note that if roles are mapped and they are not present on the LMS, they will be removed from the Drupal user. Please be careful when setting this for the authenticated user role.'
      ),
      '#header' => [t('User Role'), t('LTI Role')],
    ];

    foreach (user_roles(TRUE) as $key => $user_role) {
      $form['mapped_roles'][$key] = [
        'user_role' => [
          '#type' => 'item',
          '#title' => $user_role->label(),
        ],
        'lti_role' => [
          '#type' => 'select',
          '#required' => FALSE,
          '#empty_option' => t('None'),
          '#empty_value' => TRUE,
          '#default_value' => $mapped_roles[$key] ?? '',
          '#options' => array_combine($lti_roles, $lti_roles),
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('lti_tool_provider_roles.settings');
    $lti_roles = $this->config('lti_tool_provider.settings')->get('v1p0_lti_roles');

    $mapped_roles = [];
    foreach ($form_state->getValue('mapped_roles') as $key => $value) {
      if (in_array($value['lti_role'], $lti_roles)) {
        $mapped_roles[$key] = $value['lti_role'];
      }
    }

    $settings->set('v1p0_mapped_roles', $mapped_roles);

    $settings->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['lti_tool_provider_roles.settings'];
  }

}

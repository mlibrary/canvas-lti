<?php

namespace Drupal\lti_tool_provider_provision\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LtiToolProviderProvisionSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'lti_tool_provider_provision_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['lti_tool_provider_provision.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $filter = ''): array
    {
        $settings = $this->config('lti_tool_provider_provision.settings');
        $lti_roles = $this->config('lti_tool_provider.settings')->get('lti_roles');

        $entityType = $form_state->getValue('entity_type') ? $form_state->getValue('entity_type') : $settings->get('entity_type');
        $entityBundle = $form_state->getValue('entity_bundle') ? $form_state->getValue('entity_bundle') : $settings->get('entity_bundle');
        $entityRedirect = $form_state->getValue('entity_redirect') ? $form_state->getValue('entity_redirect') : $settings->get('entity_redirect');
        $entityDefaults = $form_state->getValue('entity_defaults') ? $form_state->getValue('entity_defaults') : $settings->get('entity_defaults');
        $entitySync = $form_state->getValue('entity_sync') ? $form_state->getValue('entity_sync') : $settings->get('entity_sync');
        $allowedRolesEnabled = $form_state->getValue('allowed_roles_enabled') ? $form_state->getValue('allowed_roles_enabled') : $settings->get('allowed_roles_enabled');
        $allowedRoles = $form_state->getValue('allowed_roles') ? $form_state->getValue('allowed_roles') : $settings->get('allowed_roles');

        $form['#attributes']['id'] = uniqid($this->getFormId());

        $options = [];
        $definitions = Drupal::entityTypeManager()->getDefinitions();

        foreach ($definitions as $definition) {
            if ($definition instanceof ContentEntityType) {
                $options[$definition->id()] = $definition->getLabel();
            }
        }

        $form['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Default entity type'),
            '#description' => $this->t('Select the entity type to use as the default entity provision.'),
            '#default_value' => $entityType,
            '#empty_value' => '',
            '#empty_option' => '- Select an entity type -',
            '#options' => $options,
            '#ajax' => [
                'callback' => '::getEntityBundles',
                'event' => 'change',
                'wrapper' => $form['#attributes']['id'],
                'progress' => [
                    'type' => 'throbber',
                ],
            ],
        ];

        if ($entityType) {
            $options = [];

            $bundles = Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType);
            foreach ($bundles as $key => $bundleInfo) {
                $options[$key] = $bundleInfo['label'];
            }

            $form['entity_bundle'] = [
                '#type' => 'select',
                '#title' => $this->t('Default entity bundle'),
                '#description' => $this->t('Select the entity bundle to use as the default entity provision.'),
                '#default_value' => $entityBundle,
                '#empty_value' => '',
                '#empty_option' => '- Select an entity type -',
                '#options' => $options,
                '#ajax' => [
                    'callback' => '::getEntityBundles',
                    'event' => 'change',
                    'wrapper' => $form['#attributes']['id'],
                    'progress' => [
                        'type' => 'throbber',
                    ],
                ],
            ];
        }

        $form['entity_redirect'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Always redirect to entity upon launch.'),
            '#default_value' => $entityRedirect,
        ];

        if ($entityBundle) {
            $lti_launch = $this->config('lti_tool_provider.settings')->get('lti_launch');

            $form['entity_defaults'] = [
                '#type' => 'fieldset',
                '#title' => 'Entity defaults',
                '#tree' => true,
            ];

            /* @var $entityManager Drupal\Core\Entity\EntityFieldManagerInterface */
            $entityManager = Drupal::service('entity_field.manager');
            $userFieldDefinitions = $entityManager->getFieldDefinitions($entityType, $entityBundle);
            foreach ($userFieldDefinitions as $key => $field) {
                $type = $field->getType();
                if ($type === 'string') {
                    $form['entity_defaults'][$key] = [
                        'name' => [
                            '#type' => 'item',
                            '#title' => $field->getLabel(),
                        ],
                        'lti_attribute' => [
                            '#type' => 'select',
                            '#required' => false,
                            '#empty_option' => t('None'),
                            '#empty_value' => true,
                            '#default_value' => $entityDefaults[$key],
                            '#options' => array_combine($lti_launch, $lti_launch),
                        ],
                    ];
                }
            }

            $form['entity_sync'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Always sync entity fields from context during launch.'),
                '#default_value' => $entitySync,
            ];
        }

        $form['allowed_roles_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Restrict entity provision to specific LTI roles.'),
            '#default_value' => $allowedRolesEnabled,
        ];

        $form['allowed_roles'] = [
            '#type' => 'details',
            '#title' => 'Allowed Roles',
            '#description' => $this->t('If enabled above, allow only specific LTI roles to provision entities.'),
            '#tree' => true,
            '#open' => false,
        ];

        foreach ($lti_roles as $ltiRole) {
            $form['allowed_roles'][$ltiRole] = [
                '#type' => 'checkbox',
                '#title' => $this->t($ltiRole),
                '#default_value' => $allowedRoles[$ltiRole],
            ];
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $settings = $this->config('lti_tool_provider_provision.settings');
        $lti_launch = $this->config('lti_tool_provider.settings')->get('lti_launch');

        $entityType = $form_state->getValue('entity_type');
        $entityBundle = $form_state->getValue('entity_bundle');
        $entityRedirect = $form_state->getValue('entity_redirect');
        $entitySync = $form_state->getValue('entity_sync');
        $allowedRolesEnabled = $form_state->getValue('allowed_roles_enabled');

        $settings->set('entity_type', $entityType)->save();
        $settings->set('entity_bundle', $entityBundle)->save();
        $settings->set('entity_redirect', $entityRedirect)->save();
        $settings->set('entity_sync', $entitySync)->save();
        $settings->set('allowed_roles_enabled', $allowedRolesEnabled)->save();

        $entityDefaults = [];
        foreach ($form_state->getValue('entity_defaults') as $key => $value) {
            if (in_array($value['lti_attribute'], $lti_launch)) {
                $entityDefaults[$key] = $value['lti_attribute'];
            }
        }
        $settings->set('entity_defaults', $entityDefaults)->save();

        $allowedRoles = [];
        foreach ($form_state->getValue('allowed_roles') as $key => $value) {
            $allowedRoles[$key] = $value;
        }
        $settings->set('allowed_roles', $allowedRoles)->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * @param array $form
     * @return array
     */
    public function getEntityBundles(array $form): array
    {
        return $form;
    }
}

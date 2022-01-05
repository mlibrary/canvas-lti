<?php

namespace Drupal\lti_tool_provider_content\Form;

use Drupal;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LtiToolProviderContentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lti_tool_provider_content_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $filter = ''): array {
    $settings = $this->config('lti_tool_provider_content.settings');
    $lti_launch = $this->config('lti_tool_provider.settings')->get('v1p3_lti_launch');

    $enabled = $form_state->getValue('enabled') ?? $settings->get('enabled');
    $entityTypes = $form_state->getValue('entity_types') ?? $settings->get('entity_types');
    $entityBundles = $form_state->getValue('entity_bundles') ?? $settings->get('entity_bundles');
    $entityDefaults = $form_state->getValue('entity_defaults') ?? $settings->get('entity_defaults');
    $owner = $form_state->getValue('owner') ?? $settings->get('owner');
    $sync = $form_state->getValue('sync') ?? $settings->get('sync');

    $form['#attributes']['id'] = uniqid($this->getFormId());

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable content selection.'),
      '#default_value' => $enabled,
    ];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available Content'),
    ];

    $options = [];
    $definitions = Drupal::entityTypeManager()->getDefinitions();

    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityType) {
        $options[$definition->id()] = $definition->getLabel();
      }
    }

    $form['fieldset']['entity_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Entities'),
      '#description' => $this->t('Select the entity types that should be available for content selection.'),
      '#default_value' => $entityTypes,
      '#multiple' => TRUE,
      '#empty_value' => '',
      '#empty_option' => '- Select an entity type -',
      '#options' => $options,
      '#ajax' => [
        'callback' => '::rebuildForm',
        'event' => 'change',
        'wrapper' => $form['#attributes']['id'],
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    if (is_array($entityTypes) && !empty($entityTypes)) {
      $options = [];

      foreach ($entityTypes as $entityType) {
        $bundles = Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType);
        foreach ($bundles as $bundle => $bundleInfo) {
          $options["$entityType-$bundle"] = $bundleInfo['label'];
        }
      }

      $form['fieldset']['entity_bundles'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundles'),
        '#description' => $this->t('Select the entity bundles that should be available for content selection.'),
        '#default_value' => $entityBundles,
        '#multiple' => TRUE,
        '#empty_value' => '',
        '#empty_option' => '- Select an bundle -',
        '#options' => $options,
        '#ajax' => [
          'callback' => '::rebuildForm',
          'event' => 'change',
          'wrapper' => $form['#attributes']['id'],
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];
    }

    if (is_array($entityBundles) && !empty($entityBundles)) {
      $form['fieldset']['entity_defaults'] = [
        '#type' => 'fieldset',
        '#title' => t('Entity Defaults'),
        '#tree' => TRUE,
      ];

      foreach ($entityBundles as $value) {
        [$entityType, $entityBundle] = explode('-', $value);
        $bundles = Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType);
        if (!empty($entityType) && !empty($entityBundle)) {
          $form['fieldset']['entity_defaults']["$entityType-$entityBundle"] = [
            '#type' => 'fieldset',
            '#title' => t('@label Entity Defaults', ['@label' => $bundles[$entityBundle]['label']]),
          ];

          /** @var Drupal\Core\Entity\EntityFieldManagerInterface $entityManager */
          $entityManager = Drupal::service('entity_field.manager');
          $userFieldDefinitions = $entityManager->getFieldDefinitions($entityType, $entityBundle);
          foreach ($userFieldDefinitions as $key => $field) {
            $type = $field->getType();
            if ($type === 'string') {
              $default_value = $entityDefaults["$entityType-$entityBundle"]["$entityType-$entityBundle-$key"];
              $form['fieldset']['entity_defaults']["$entityType-$entityBundle"]["$entityType-$entityBundle-$key"] = [
                'name' => [
                  '#type' => 'item',
                  '#title' => $field->getLabel(),
                  '#value' => $key,
                ],
                'lti_attribute' => [
                  '#type' => 'select',
                  '#required' => FALSE,
                  '#empty_option' => t('None'),
                  '#empty_value' => TRUE,
                  '#default_value' => $default_value['lti_attribute'] ?? $default_value,
                  '#options' => array_combine($lti_launch, $lti_launch),
                ],
              ];
            }
          }
        }
      }
    }

    $form['fieldset']['owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by owner on common entity types.'),
      '#default_value' => $owner,
    ];

    $form['fieldset']['sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always sync entity fields from context during launch.'),
      '#default_value' => $sync,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('lti_tool_provider_content.settings');

    $enabled = $form_state->getValue('enabled');
    $entityTypes = $form_state->getValue('entity_types');
    $entityBundles = $form_state->getValue('entity_bundles');
    $entityDefaults = $form_state->getValue('entity_defaults');
    $owner = $form_state->getValue('owner');
    $sync = $form_state->getValue('sync');

    $settings->set('enabled', $enabled);
    $settings->set('entity_types', $entityTypes);
    $settings->set('entity_bundles', $entityBundles);
    $settings->set('entity_defaults', $entityDefaults);
    $settings->set('owner', $owner);
    $settings->set('sync', $sync);

    $settings->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * @param array $form
   *
   * @return array
   */
  public function rebuildForm(array $form): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['lti_tool_provider_content.settings'];
  }

}

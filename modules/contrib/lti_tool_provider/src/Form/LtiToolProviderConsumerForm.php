<?php

namespace Drupal\lti_tool_provider\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form for editing a lti_tool_provider_consumer entity.
 *
 * @see \Drupal\lti_tool_provider\Entity\LtiToolProviderConsumer
 */
class LtiToolProviderConsumerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $v1p0_lti_fields = [
      'consumer_key',
      'consumer_secret',
      'name',
      'mail',
    ];
    $v1p3_lti_fields = [
      'platform_id',
      'client_id',
      'deployment_id',
      'key_set_url',
      'auth_token_url',
      'auth_login_url',
    ];

    foreach ($v1p0_lti_fields as $lti_field) {
      $form[$lti_field]['widget'][0]['value']['#states'] = [
        'visible' => [
          ':input[name="lti_version"]' => [
            'value' => 'v1p0',
          ],
        ],
        'required' => [
          ':input[name="lti_version"]' => [
            'value' => 'v1p0',
          ],
        ],
      ];
    }

    foreach ($v1p3_lti_fields as $lti_field) {
      $form[$lti_field]['widget'][0]['value']['#states'] = [
        'visible' => [
          ':input[name="lti_version"]' => [
            'value' => 'v1p3',
          ],
        ],
        'required' => [
          ':input[name="lti_version"]' => [
            'value' => 'v1p3',
          ],
        ],
      ];
    }

    $form['public_key']['widget'][0]['target_id']['#states'] = [
      'visible' => [
        ':input[name="lti_version"]' => [
          'value' => 'v1p3',
        ],
      ],
      'required' => [
        ':input[name="lti_version"]' => [
          'value' => 'v1p3',
        ],
      ],
    ];

    $form['private_key']['widget'][0]['target_id']['#states'] = [
      'visible' => [
        ':input[name="lti_version"]' => [
          'value' => 'v1p3',
        ],
      ],
      'required' => [
        ':input[name="lti_version"]' => [
          'value' => 'v1p3',
        ],
      ],
    ];

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('lti_version')[0]['value'] === 'v1p0') {
      // Temporarily store all form errors.
      $form_errors = $form_state->getErrors();

      // Clear the form errors.
      $form_state->clearErrors();

      // Remove the field_mobile form error.
      unset($form_errors['private_key][0']);

      // Now loop through and re-apply the remaining form error messages.
      foreach ($form_errors as $name => $error_message) {
        $form_state->setErrorByName($name, $error_message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    try {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    catch (EntityMalformedException $e) {
      $form_state->setRedirect('entity.lti_tool_provider_consumer.collection');
    }

    return $status;
  }

}

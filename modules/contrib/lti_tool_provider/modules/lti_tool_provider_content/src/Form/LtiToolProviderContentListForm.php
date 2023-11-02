<?php

namespace Drupal\lti_tool_provider_content\Form;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;

class LtiToolProviderContentListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lti_tool_provider_content_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->config('lti_tool_provider_content.settings');

    $enabled = $settings->get('enabled');
    $entityBundles = $settings->get('entity_bundles');
    $owner = $settings->get('owner');
    $bundle = $form_state->getValue('select') ?? reset($entityBundles);

    $form['#attributes']['id'] = uniqid($this->getFormId());

    if (!$enabled) {
      $form['message'] = [
        '#markup' => 'Content selection is not enabled.',
      ];
      return $form;
    }

    $options = [];
    foreach ($entityBundles as $value) {
      [$entityType, $entityBundle] = explode('-', $value);
      $bundles = Drupal::service('entity_type.bundle.info')->getBundleInfo($entityType);
      if (isset($bundles[$entityBundle])) {
        $options[$value] = $bundles[$entityBundle]['label'];
      }
    }

    $form['bundle'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; align-items: last baseline',
      ]
    ];
    $form['bundle']['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter Content'),
      '#default_value' => $bundle,
      '#empty_value' => '',
      '#empty_option' => '- Select an bundle -',
      '#options' => $options,
      '#attributes' => [
        'style' => '',
      ]
    ];
    $form['bundle']['change'] = [
      '#type' => 'button',
      '#value' => t('Go'),
      '#attributes' => [
        'style' => 'height: 100%',
      ]
    ];

    if ($bundle) {
      $entityTypeManager = Drupal::entityTypeManager();
      [$entityType, $entityBundle] = explode('-', $bundle);

      try {
        $bundleType = $entityTypeManager->getDefinition($entityType)->getKey('bundle');
      }
      catch (PluginNotFoundException $e) {
        $this->logger('lti_tool_provider_content')->error($e->getMessage());
        return $form;
      }

      $header = [
        'title' => [
          'data' => $this->t('Title'),
          'specifier' => 'title',
        ],
        'op' => [
          'data' => $this->t(' '),
        ],
      ];

      $form['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('No content has been found.'),
      ];

      try {
        $storage = Drupal::entityTypeManager()->getStorage($entityType);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        $this->logger('lti_tool_provider_content')->error($e->getMessage());
        return $form;
      }

      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition($bundleType, $entityBundle)
        ->condition('status', 1)
        ->tableSort($header)
        ->pager(15);

      if ($owner) {
        $uid = Drupal::currentUser()->id();
        switch ($entityType) {
          case 'node':
            $query->condition('uid', $uid);
        }
      }

      $ids = $query->execute();
      $entities = $storage->loadMultiple(array_keys($ids));

      foreach ($entities as $id => $entity) {
        $form['table'][$id]['title'] = [
          '#type' => 'markup',
          '#markup' => $entity->label(),
        ];
        $form['table'][$id]['op'] = [
          '#type' => 'submit',
          '#name' => $id,
          '#value' => 'Select',
        ];
      }

      $form['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = Drupal::request();

    $client_id = $request->request->get('client_id') ?: $request->get('client_id');
    $return = $request->request->get('return') ?: $request->get('return');

    [$entityType] = explode('-', $form_state->getValue('select'));
    $entityId = $form_state->getTriggeringElement()['#name'];

    $redirect = Url::fromRoute('lti_tool_provider.content.return', [
      'client_id' => $client_id,
      'return' => $return,
      'entityType' => $entityType,
      'entityId' => $entityId,
    ], ['absolute' => TRUE]);

    $form_state->setRedirectUrl($redirect);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function changeBundle(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $request = Drupal::request();

    $context = $request->getSession()->get('lti_tool_provider_context');
    $is_context = $context instanceof LTIToolProviderContextInterface;
    $client_id = $request->request->get('client_id') ?: $request->get('client_id');
    $return = $request->request->get('return') ?: $request->get('return');
    $is_client_id = is_string($client_id) && strlen($client_id) > 0;
    $is_return = is_string($return) && strlen($return) > 0;

    return AccessResult::allowedIf($is_context && $is_client_id && $is_return);
  }

}

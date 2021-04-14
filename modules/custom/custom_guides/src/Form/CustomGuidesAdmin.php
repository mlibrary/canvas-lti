<?php

/**
 * @file
 * Contains \Drupal\custom_guides\Form\CustomGuidesAdmin.
 */

namespace Drupal\custom_guides\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomGuidesAdmin extends ConfigFormBase {

  /**
   * Constructs a \Drupal\user\AccountSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_guides_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_guides.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);

    //clear all routing caches to update any changed settings.
    \Drupal::service("router.builder")->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_guides.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('custom_guides.settings');
    
    $form['custom_guides_data'] = [
      '#type' => 'textfield',
      '#title' => t('Guide Data'),
      '#default_value' => $config->get('custom_guides_data'),
      '#size' => 80,
      '#maxlength' => 200,
      '#description' => t("The address of the data for guides."),
    ];
    $form['custom_guides_data1'] = [
      '#type' => 'textfield',
      '#title' => t('Recommended Databases'),
      '#default_value' => $config->get('custom_guides_data1'),
      '#size' => 80,
      '#maxlength' => 200,
      '#description' => t("The address of the data for Recommended Databases"),
    ];
    /*
    $form['custom_guides_mail_recipient'] = [
      '#type' => 'email',
      '#title' => t('Mail Recipient'),
      '#default_value' => $config->get('custom_guides_mail_recipient'),
      '#size' => 80,
      '#maxlength' => 200,
      '#description' => t("Who should get the mail detailing data import"),
    ];
    */

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

}

<?php

namespace Drupal\fieldblock\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a fieldblock.
 *
 * @Block(
 *   id = "fieldblock",
 *   admin_label = @Translation("Field as Block"),
 *   deriver = "Drupal\fieldblock\Plugin\Derivative\FieldBlockDeriver"
 * )
 */
class FieldBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity to be used when displaying the block.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $fieldBlockEntity;

  /**
   * Constructs a FieldBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, FormatterPluginManager $formatter_plugin_manager, RouteMatchInterface $route_match, EntityRepositoryInterface $entityRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->formatterPluginManager = $formatter_plugin_manager;
    $this->routeMatch = $route_match;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('current_route_match'),
      $container->get('entity.repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_from_field' => TRUE,
      'field_name' => '',
      'formatter_id' => '',
      'formatter_settings' => [],
    ];
  }

  /**
   * Returns field options.
   *
   * @return array
   *   Array of field option names keyed by their machine name.
   */
  protected function getFieldOptions() {
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->getDerivativeId());
    $options = [];
    foreach ($field_definitions as $definition) {
      $options[$definition->getName()] = $definition->getLabel();
    }
    return $options;
  }

  /**
   * Returns field formatter names.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field.
   *
   * @return array
   *   Array of formatter names keyed by field type.
   */
  protected function getFormatterOptions(FieldDefinitionInterface $field_definition) {
    $options = $this->formatterPluginManager->getOptions($field_definition->getType());
    foreach (array_keys($options) as $id) {
      $definition = $this->formatterPluginManager->getDefinition($id, FALSE);
      $formatter_plugin_class = $definition['class'] ?? NULL;
      if (is_subclass_of($formatter_plugin_class, FormatterInterface::class) && !$formatter_plugin_class::isApplicable($field_definition)) {
        unset($options[$id]);
      }
    }
    return $options;
  }

  /**
   * Gets the field definition.
   *
   * A FieldBlock works on an entity type across bundles, and thus only has
   * access to field storage definitions. In order to be able to use formatters,
   * we create a generic field definition out of that storage definition.
   *
   * @param string $field_name
   *   The field name.
   *
   * @see BaseFieldDefinition::createFromFieldStorageDefinition()
   * @see \Drupal\views\Plugin\views\field\Field::getFieldDefinition()
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition used by this block.
   */
  protected function getFieldDefinition($field_name) {
    $field_storage_config = $this->getFieldStorageDefinition($field_name);
    return BaseFieldDefinition::createFromFieldStorageDefinition($field_storage_config);
  }

  /**
   * Gets the field storage definition.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition used by this block.
   */
  protected function getFieldStorageDefinition($field_name) {
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->getDerivativeId());
    return $field_storage_definitions[$field_name];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // This method receives a sub form state instead of the full form state.
    // There is an ongoing discussion around this which could result in the
    // passed form state going back to a full form state. In order to prevent
    // future breakage because of a core update we'll just check which type of
    // FormStateInterface we've been passed and act accordingly.
    // @See https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $form_state = $form_state->getCompleteFormState();
    }

    $form['label_from_field'] = [
      '#title' => $this->t('Use field label as block title'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['label_from_field'],
    ];

    $form['field_name'] = [
      '#title' => $this->t('Field'),
      '#type' => 'select',
      '#options' => $this->getFieldOptions(),
      '#default_value' => $this->configuration['field_name'],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'blockFormChangeFieldOrFormatterAjax'],
        'wrapper' => 'edit-block-formatter-wrapper',
      ],
    ];

    $form['formatter'] = [
      '#type' => 'container',
      '#id' => 'edit-block-formatter-wrapper',
    ];

    $field_name = $form_state->getValue(['settings', 'field_name'], $this->configuration['field_name']);
    $field_definition = NULL;
    $formatter_id = $form_state->getValue(['settings', 'formatter', 'id'], $this->configuration['formatter_id']);

    if ($field_name) {
      $field_definition = $this->getFieldDefinition($field_name);
      $formatter_options = $this->getFormatterOptions($field_definition);
      if (empty($formatter_options)) {
        $formatter_id = '';
      }
      else {
        if (empty($formatter_id)) {
          $formatter_id = key($formatter_options);
        }
        $form['formatter']['id'] = [
          '#title' => $this->t('Formatter'),
          '#type' => 'select',
          '#options' => $formatter_options,
          '#default_value' => $this->configuration['formatter_id'],
          '#required' => TRUE,
          '#ajax' => [
            'callback' => [$this, 'blockFormChangeFieldOrFormatterAjax'],
            'wrapper' => 'edit-block-formatter-wrapper',
          ],
        ];
      }
    }

    $form['formatter']['change'] = [
      '#type' => 'submit',
      '#name' => 'fieldblock_change_field',
      '#value' => $this->t('Change field'),
      '#attributes' => ['class' => ['js-hide']],
      '#limit_validation_errors' => [['settings']],
      '#submit' => [[get_class($this), 'blockFormChangeFieldOrFormatter']],
    ];

    if ($formatter_id) {
      $formatter_settings = $this->configuration['formatter_settings'] + $this->formatterPluginManager->getDefaultSettings($formatter_id);
      $formatter_options = [
        'field_definition' => $field_definition,
        'view_mode' => '_custom',
        'configuration' => [
          'type' => $formatter_id,
          'settings' => $formatter_settings,
          'label' => '',
          'weight' => 0,
        ],
      ];

      if ($formatter_plugin = $this->formatterPluginManager->getInstance($formatter_options)) {
        $formatter_settings_form = $formatter_plugin->settingsForm($form, $form_state);
        // Convert field UI selector states to work in the block configuration
        // form.
        FormHelper::rewriteStatesSelector($formatter_settings_form,
          "fields[{$field_name}][settings_edit_form]",
          'settings[formatter][settings]');
      }
      if (!empty($formatter_settings_form)) {
        $form['formatter']['settings'] = $formatter_settings_form;
        $form['formatter']['settings']['#type'] = 'fieldset';
        $form['formatter']['settings']['#title'] = $this->t('Formatter settings');
      }
    }

    return $form;
  }

  /**
   * Element submit handler for non-JS field/formatter changes.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function blockFormChangeFieldOrFormatter(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback on changing field_name or formatter_id form element.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   The part of the form that has changed.
   */
  public function blockFormChangeFieldOrFormatterAjax(array $form) {
    return $form['settings']['formatter'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['label_from_field'] = $form_state->getValue('label_from_field');
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    $this->configuration['formatter_id'] = $form_state->getValue(['formatter',
      'id',
    ], '');
    $this->configuration['formatter_settings'] = $form_state->getValue(['formatter',
      'settings',
    ], []);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\Plugin\views\field\Field::calculateDependencies()
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Add the module providing the configured field storage as a dependency.
    if (($field_storage_definition = $this->getFieldStorageDefinition($this->configuration['field_name'])) && $field_storage_definition instanceof EntityInterface) {
      $dependencies['config'][] = $field_storage_definition->getConfigDependencyName();
    }
    // Add the module providing the formatter.
    if (!empty($this->configuration['formatter_id'])) {
      $dependencies['module'][] = $this->formatterPluginManager->getDefinition($this->configuration['formatter_id'])['provider'];
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $entity = $this->getEntity();

    if (!$entity) {
      return AccessResult::forbidden();
    }

    $field = $entity->get($this->configuration['field_name']);
    return AccessResult::allowedIf(!$field->isEmpty())
      ->andIf($field->access('view', $account, TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $entity = $this->getEntity();

    if ($entity) {
      $build['field'] = $this->getTranslatedFieldFromEntity($entity)->view([
        'label' => 'hidden',
        'type' => $this->configuration['formatter_id'],
        'settings' => $this->configuration['formatter_settings'],
      ]);
      if ($this->configuration['label_from_field'] && !empty($build['field']['#title'])) {
        $build['#title'] = $build['field']['#title'];
      }
    }

    return $build;
  }

  /**
   * Ensure that the field gets correctly translated into the content language.
   *
   * The block renders content, so it follows the negotiated content language
   * rather than the interface language. On sites where the two are configured
   * separately -- an English interface with content in many languages, say --
   * those differ. The entity repository resolves the same way core's own param
   * converters do, language fallback candidates included, so the block renders
   * the translation the rest of the page is already showing.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that contains the field.
   *
   * @see \Drupal\Core\Entity\EntityRepositoryInterface::getTranslationFromContext()
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field in the content language.
   */
  private function getTranslatedFieldFromEntity(ContentEntityInterface $entity) {
    $translation = $this->entityRepository->getTranslationFromContext($entity);
    return $translation->get($this->configuration['field_name']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $entity = $this->getEntity();
    if ($entity) {
      return $entity->getCacheTags();
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This block must be cached per route: every entity has its own canonical
    // url and its own fields. It must also be cached per content language,
    // because that is the language the field is rendered in. The route context
    // does not cover that: a language prefix is stripped from the path before
    // routing, so "/node/1" and "/de/node/1" are the very same route with the
    // very same parameters, and without this the render cache hands the one
    // language's field to the other.
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'route',
      'languages:' . LanguageInterface::TYPE_CONTENT,
    ]);
  }

  /**
   * Finds the entity to be used when displaying the block.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity to be used when displaying the block.
   */
  protected function getEntity() {
    if (isset($this->fieldBlockEntity)) {
      return $this->fieldBlockEntity;
    }

    $entity_type = $this->getDerivativeId();
    $field_name = $this->configuration['field_name'];

    $route = $this->routeMatch->getRouteObject();
    if (empty($route)) {
      return NULL;
    }

    $parameters = $route->getOption('parameters');
    if (empty($parameters)) {
      return NULL;
    }

    // Check if any of the route parameters represents an entity. Use the first
    // one that is of the right type. What the route match hands back is
    // checked, rather than the parameter type the route declares, because not
    // every parameter that ends up holding an entity is declared with an
    // "entity:" type. The node preview route, for one, declares a
    // "node_preview" type of its own, and its param converter still returns a
    // node.
    foreach ($this->sortParameterNames($parameters, $entity_type) as $name) {
      $entity = $this->routeMatch->getParameter($name);

      if ($entity instanceof ContentEntityInterface
        && $entity->hasLinkTemplate('canonical')
        && $entity->getEntityTypeId() === $entity_type
        && $entity->hasField($field_name)
      ) {
        $this->fieldBlockEntity = $entity;
        return $this->fieldBlockEntity;
      }
    }

    return NULL;
  }

  /**
   * Orders route parameter names by the order in which they must be checked.
   *
   * Revision routes carry the entity twice: once as the default revision and
   * once as the revision being viewed. Core declares the latter with an
   * "entity_revision:" parameter type, for every revisionable entity type. The
   * revision on display is the one the block must render, so the names of those
   * parameters are returned before all others.
   *
   * Takes the "parameters" option of the route, keyed by parameter name, and
   * the entity type id of the block.
   */
  protected function sortParameterNames(array $parameters, string $entity_type): array {
    $revision_names = [];
    $other_names = [];

    foreach ($parameters as $name => $options) {
      $type = $options['type'] ?? '';
      if ($type === 'entity_revision:' . $entity_type) {
        $revision_names[] = $name;
      }
      else {
        $other_names[] = $name;
      }
    }

    return array_merge($revision_names, $other_names);
  }

}

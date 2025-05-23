<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides states handler for entity reference fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_entity_reference_autocomplete",
 * )
 */
class EntityReference extends ConditionalFieldsHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * Provides an interface for entity type managers.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an entity reference states handler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  final public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $values_set = $options['values_set'];

    switch ($values_set) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $value_form = $this->getWidgetValue($options['value_form']);
        if (empty($value_form)) {
          break;
        }
        $entity_type = $this->entityTypeManager->getStorage($field["#target_type"]);
        if ($options['field_cardinality'] == 1) {
          $entity = $entity_type->load($value_form[0]['target_id']);
          $state[$options['state']][$options['selector']] = $this->getAutocompleteSuggestions($entity);
        }
        else {
          $ids = array_column($value_form, 'target_id');
          $entities = $entity_type->loadMultiple($ids);
          if (!empty($entities)) {
            foreach (array_values($entities) as $key => $entity) {
              $selector = str_replace('[0]', "[{$key}]", $options['selector']);
              $state[$options['state']][$selector] = $this->getAutocompleteSuggestions($entity);
            }
          }
        }
        break;

      default:
        break;
    }

    return $state;
  }

  /**
   * Get a variants of node title for autocomplete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return array
   *   An array with a few relevant suggestions for autocomplete.
   */
  private function getAutocompleteSuggestions(EntityInterface $entity) {
    return [
      // Node title (nid).
      ['value' => $entity->label() . ' (' . $entity->id() . ')'],
      // Node title.
      ['value' => $entity->label()],
    ];
  }

  /**
   * Get values from widget settings for plugin.
   *
   * @param array $value_form
   *   Dependency options.
   *
   * @return mixed
   *   Values for triggering events.
   */
  public function getWidgetValue(array $value_form) {
    return $value_form;
  }

}

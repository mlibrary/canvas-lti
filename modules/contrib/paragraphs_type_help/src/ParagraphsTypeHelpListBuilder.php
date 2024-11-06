<?php

namespace Drupal\paragraphs_type_help;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of paragraphs_type_help entities.
 *
 * @see \Drupal\paragraphs_type_help\Entity\ParagraphsTypeHelp
 */
class ParagraphsTypeHelpListBuilder extends EntityListBuilder {

  use RedirectDestinationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );

    $instance->setEntityTypeManager($container->get('entity_type.manager'));

    return $instance;
  }

  /**
   * Set the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @return $this
   *   This instance.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Get the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface|null
   *   The entity type manager service.
   */
  public function getEntityTypeManager() {
    return isset($this->entityTypeManager) ? $this->entityTypeManager : \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Admin Label');
    $header['id'] = $this->t('Id');
    $header['host_bundle'] = $this->t('Paragraph Type');
    $header['host_form_mode'] = $this->t('Form Mode');
    $header['host_view_mode'] = $this->t('View Mode');
    $header['status'] = $this->t('Published');
    $header['weight'] = $this->t('Weight');
    $header['help_build'] = $this->t('Rendered Help');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['host_bundle'] = $this->t('@label (@id)', [
      '@label' => $entity->getHostBundleLabel(),
      '@id' => $entity->getHostBundleId(),
    ]);
    $row['host_form_mode'] = $entity->getHostFormModeLabel() ?: $this->t('None');
    $row['host_view_mode'] = $entity->getHostViewModeLabel() ?: $this->t('None');

    $row['status'] = $entity->isPublished() ? $this->t('Yes') : $this->t('No');
    $row['weight'] = $entity->getWeight();
    $row['help_build'] = [];

    $view_builder = $this->getEntityTypeManager()->getViewBuilder('paragraphs_type_help');
    if ($view_builder && ($help_build = $view_builder->view($entity, 'admin_list'))) {
      $row['help_build']['data'] = $help_build;
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery(): QueryInterface {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      // Sort by label instead of ID.
      ->sort($this->entityType->getKey('label'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $this->getRedirectDestination()->get();
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];
    $build['help'] = $this->buildHelpDetails();
    $build += parent::render();
    return $build;
  }

  /**
   * Build markup that shows at the top of the list.
   *
   * @return array
   *   A render array.
   */
  public function buildHelpDetails() {
    $details = [
      '#type' => 'details',
      '#title' => $this->t('Paragraphs Help'),
      '#open' => TRUE,
    ];

    $details['content'] = [
      '#markup' => $this->t("Help can be created for any Paragraph type's form mode or view mode. The 'Paragraphs Type Help' extra field must be configured on each Paragraph type for the help to render in the Paragraph form or view mode."),
    ];

    return $details;
  }

}

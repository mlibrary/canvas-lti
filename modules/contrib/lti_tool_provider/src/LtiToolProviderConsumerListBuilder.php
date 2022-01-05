<?php

namespace Drupal\lti_tool_provider;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\lti_tool_provider\Entity\LtiToolProviderConsumer;

/**
 * Implementation LtiToolProviderConsumerListBuilder class.
 */
class LtiToolProviderConsumerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'consumer' => [
        'data' => $this->t('Label'),
        'field' => 'consumer',
        'specifier' => 'consumer',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'lti_version' => [
        'data' => $this->t('LTI version'),
        'field' => 'lti_version',
        'specifier' => 'lti_version',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'field' => 'created',
        'specifier' => 'created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row = [];

    if ($entity instanceof LtiToolProviderConsumer) {
      $row = [
        'id' => $entity->id(),
        'consumer' => $link = Link::fromTextAndUrl(
          $entity->label(),
          Url::fromRoute(
            'entity.lti_tool_provider_consumer.canonical',
            ['lti_tool_provider_consumer' => $entity->id()]
          )
        ),
        'lti_version' => $entity->get('lti_version')->value,
        'created' => Drupal::service('date.formatter')
          ->format($entity->get('created')->value, 'short'),
      ];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('No consumers found.');

    return $build;
  }

}

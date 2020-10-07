<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;

/**
 * Class IframeOnlyFormatter.
 *
 * @FieldFormatter(
 *  id = "iframe_only",
 *  label = @Translation("Iframe without title"),
 *  field_types = {"iframe"}
 * )
 */
class IframeOnlyFormatter extends IframeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      if (!isset($item->title)) {
        $item->title = '';
      }
      $elements[$delta] = [
        '#markup' => Markup::create(IframeDefaultFormatter::iframeIframe('', $item->url, $item)),
        '#allowed_tags' => ['iframe', 'a', 'h3', 'style'],
      ];
    }
    return $elements;
  }

}

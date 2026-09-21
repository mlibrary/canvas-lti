<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Class IframeUrlheightWidget.
 */
#[FieldWidget(
  id: 'iframe_urlheight',
  label: new TranslatableMarkup('URL with height'),
  field_types: ['iframe'],
)]
class IframeUrlheightWidget extends IframeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $on_admin_page = isset($element['#field_parents'][0]) && ('default_value_input' == $element['#field_parents'][0]);
    if (!$on_admin_page) {
      $this->allowedAttributes['width'] = 0;
    }
    $elements = parent::formElement($items, $delta, $element, $form, $form_state);
    if (!$on_admin_page) {
      // Don't show, only save default value.
      $elements['width']['#type'] = 'value';
      unset($element['width']['#required']);
    }

    return $elements;
  }

}

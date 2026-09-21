<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'iframe_urlwidthheigth' widget.
 */
#[FieldWidget(
  id: 'iframe_urlwidthheight',
  label: new TranslatableMarkup('URL with width and height'),
  field_types: ['iframe'],
)]
class IframeUrlwidthheightWidget extends IframeWidgetBase {

}

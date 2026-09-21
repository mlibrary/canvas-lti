<?php

namespace Drupal\iframe\Plugin\migrate\process\d7;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * The Class CckIframe.
 */
#[MigrateProcess('d7_cck_iframe')]
class CckIframe extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $attributes = unserialize($value['attributes'], [
      'allowed_classes' => FALSE,
    ]);
    // Drupal 6 iframe attributes might be double serialized.
    if (!is_array($attributes)) {
      try {
        $attributes = unserialize($attributes, [
          'allowed_classes' => FALSE,
        ]);
      }
      catch (\Exception) {
        // Ignore and set default attributes were
        // Only optional and ar not necessarily required.
        $attributes = [];
      }
    }

    // Massage the values into the correct form for the iframe.
    foreach ($attributes as $akey => $aval) {
      if (isset($akey)) {
        $route[$akey] = (string) $aval;
      }
    }
    $route['url'] = (string) $value['url'];
    $route['title'] = (string) $value['title'];
    $route['width'] = (string) $value['width'];
    $route['height'] = (string) $value['height'];
    $route['tokensupport'] = (int) $value['tokensupport'];
    return $route;
  }

}

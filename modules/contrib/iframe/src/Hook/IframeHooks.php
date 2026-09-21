<?php

namespace Drupal\iframe\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for iframe.
 */
class IframeHooks {
  use StringTranslationTrait;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.iframe':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Iframe module allows you to create fields that contain iframe URLs and iframe title text. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for general information on fields and how to create and manage them. For more information, see the <a href=":iframe_documentation">online documentation for the Link module</a>.', [
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => $this->moduleHandler->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
          ':iframe_documentation' => 'https://www.drupal.org/documentation/modules/iframe',
        ]) . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Managing and displaying iframe fields') . '</dt>';
        $output .= '<dd>' . $this->t('The <em>settings</em> and the <em>display</em> of the iframe field can be configured separately. See the <a href=":field_ui">Field UI help</a> for more information on how to manage fields and their display.', [
          ':field_ui' => $this->moduleHandler->moduleExists('field_ui') ? Url::fromRoute('help.page', [
            'name' => 'field_ui',
          ])->toString() : '#',
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Adding attributes to iframes') . '</dt>';
        $output .= '<dd>' . $this->t('You can add attributes to iframes, by changing the <em>Format settings</em> in the <em>Manage display</em> page. Further definable are attributes for styling the iframe, like: URL, width, height, title, headerlevel, class, frameborder, scrolling and transparency.') . '</dd>';
        $output .= '<dt>' . $this->t('Validating URLs') . '</dt>';
        $output .= '<dd>' . $this->t('All URLs are validated after a iframe field is filled in. They can include anchors or query strings.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme(): array {
    return [
          /* template name "iframe" => templates/iframe.html.twig */
      'iframe' => [
        'variables' => [
          'src' => 'src',
          'attributes' => [],
          'text' => '',
          'style' => '',
          'headerlevel' => '',
        ],
        'template' => 'iframe',
      ],
    ];
  }

}

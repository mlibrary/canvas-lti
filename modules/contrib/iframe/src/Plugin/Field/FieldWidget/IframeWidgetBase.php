<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation base functions.
 */
class IframeWidgetBase extends WidgetBase {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a MediaLibraryWidget widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('token'),
    );
  }

  /**
   * Allowed editable attributes of iframe field on node-edit.
   *
   * @var array
   */
  public $allowedAttributes = [
    'title' => 1,
    'url' => 1,
    'headerlevel' => 1,
    'width' => 1,
    'height' => 1,
    'tokensupport' => 1,
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '',
      'height' => '',
      'headerlevel' => '3',
      'class' => '',
      'expose_class' => 0,
      'frameborder' => '0',
      'scrolling' => 'auto',
      'transparency' => '0',
      'tokensupport' => '0',
      'allowfullscreen' => '0',
    ] + parent::defaultSettings();
  }

  /**
   * Translate the description for iframe width/height only once.
   */
  protected static function getSizedescription() {
    return t('The iframe\'s width and height can be set in pixels as a number only ("500" for 500 pixels) or in a percentage value followed by the percent symbol (%) ("50%" for 50 percent), further supported for width em/rem/vw and for height em/rem/vh.');
  }

  /**
   * It is {@inheritdoc}.
   *
   * Used : at "Manage form display" after work-symbol.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /* Settings form after "manage form display" page, valid for one content type */
    $field_settings = $this->getFieldSettings();
    $widget_settings = $this->getSettings();
    // \iframe_debug
    // (0, 'manage settingsForm widget_settings', $widget_settings);
    // \iframe_debug(0, 'manage settingsForm field_settings', $field_settings);
    $settings = [];
    foreach ($widget_settings as $wkey => $wvalue) {
      if (empty($wvalue) && isset($field_settings[$wkey])) {
        $settings[$wkey] = $field_settings[$wkey];
      }
      else {
        $settings[$wkey] = $wvalue;
      }
    }
    $settings = $settings + $field_settings + self::defaultSettings();
    // \iframe_debug(0, 'manage settingsForm settings', $settings);
    /* NOW all values have their default values at minimum */

    // Widget width/heigth wins, only if empty,
    // then field-width/height are taken.
    $element['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe Width'),
      // ''
      '#default_value' => $settings['width'],
      '#description' => self::getSizedescription(),
      '#maxlength' => 7,
      '#size' => 7,
    ];
    $element['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe Height'),
      // ''
      '#default_value' => $settings['height'],
      '#description' => self::getSizedescription(),
      '#maxlength' => 7,
      '#size' => 7,
    ];
    $element['headerlevel'] = [
      '#type' => 'select',
      '#title' => $this->t('Header Level'),
      '#options' => [
        '1' => $this->t('h1'),
        '2' => $this->t('h2'),
        '3' => $this->t('h3'),
        '4' => $this->t('h4'),
      ],
      // 0
      '#default_value' => $settings['headerlevel'],
      '#description' => $this->t('Header level for accessibility, defaults to "h3".'),
    ];
    $element['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional CSS Class'),
      // ''
      '#default_value' => $settings['class'],
      '#description' => $this->t('When output, this iframe will have this class attribute. Multiple classes should be separated by spaces.'),
    ];
    $element['expose_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose Additional CSS Class'),
      // 0
      '#default_value' => $settings['expose_class'],
      '#description' => $this->t('Allow author to specify an additional class attribute for this iframe.'),
    ];
    $element['frameborder'] = [
      '#type' => 'select',
      '#title' => $this->t('Frameborder'),
      '#options' => [
        '0' => $this->t('No frameborder'),
        '1' => $this->t('Show frameborder'),
      ],
      // 0
      '#default_value' => $settings['frameborder'],
      '#description' => $this->t('Frameborder is the border around the iframe. Most people want it removed, so the default value for frameborder is zero (0), or no border.'),
    ];
    $element['scrolling'] = [
      '#type' => 'select',
      '#title' => $this->t('Scrolling'),
      '#options' => [
        'auto' => $this->t('Automatic'),
        'no' => $this->t('Disabled'),
        'yes' => $this->t('Enabled'),
      ],
      // 'auto'
      '#default_value' => $settings['scrolling'],
      '#description' => $this->t('Scrollbars help the user to reach all iframe content despite the real height of the iframe content. Please disable it only if you know what you are doing.'),
    ];
    $element['transparency'] = [
      '#type' => 'select',
      '#title' => $this->t('Transparency'),
      '#options' => [
        '0' => $this->t('No transparency'),
        '1' => $this->t('Allow transparency'),
      ],
      // 0
      '#default_value' => $settings['transparency'],
      '#description' => $this->t('Allow transparency per CSS in the outer iframe tag. You have to set background-color:transparent in your iframe body tag too!'),
    ];
    $element['allowfullscreen'] = [
      '#type' => 'select',
      '#title' => $this->t('Allow fullscreen'),
      '#options' => [
        '0' => $this->t('false'),
        '1' => $this->t('true'),
      ],
      // 0
      '#default_value' => $settings['allowfullscreen'],
      '#description' => $this->t('Allow fullscreen for iframe. The iframe can activate fullscreen mode by calling the requestFullscreen() method.'),
    ];

    if (!$this->moduleHandler->moduleExists('token')) {
      $element['tokensupport']['#description'] .= ' ' . $this->t('Attention: Token module is not currently enabled!');
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $widget_settings = $this->getSettings();
    $field_settings = $this->getFieldSettings();
    // \iframe_debug(0, 'settingsSummary widget_settings', $widget_settings);
    // \iframe_debug(0, 'settingsSummary field_settings', $field_settings);
    $settings = [];
    foreach ($widget_settings as $wkey => $wvalue) {
      if (empty($wvalue) && isset($field_settings[$wkey])) {
        $settings[$wkey] = $field_settings[$wkey];
      }
      else {
        $settings[$wkey] = $wvalue;
      }
    }
    $settings = $settings + $field_settings + self::defaultSettings();

    /* summary on the "manage display" page, valid for one content type */
    $summary = [];
    $summary[] = $this->t('Iframe default header level: h@level', ['@level' => $settings['headerlevel']]);
    $summary[] = $this->t('Iframe default width: @width', ['@width' => $settings['width']]);
    $summary[] = $this->t('Iframe default height: @height', ['@height' => $settings['height']]);
    $summary[] = $this->t('Iframe default frameborder: @frameborder', ['@frameborder' => $settings['frameborder']]);
    $summary[] = $this->t('Iframe default scrolling: @scrolling', ['@scrolling' => $settings['scrolling']]);

    return $summary;
  }

  /**
   * It is {@inheritdoc}.
   *
   * Used: (1) at admin edit fields.
   *
   * Used: (2) at add-story for creation content.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // 1) Shows the "default fields" in the edit-type-field page
    // -- (on_admin_page = true).
    // 2) Edit-fields on the article-edit-page (on_admin_page = false).
    // Global settings.
    // getSettings from manage form display after work-symbol
    // (admin/structure/types/manage/test/form-display
    // and wheel behind iframe-field)
    $widget_settings = $this->getSettings();
    // getFieldSettings from field edit page on
    // admin/structure/types/manage/test/fields/node.test.field_iframe.
    $field_settings = $this->getFieldSettings();
    // \iframe_debug(0, 'formElement widget_settings', $widget_settings);
    // \iframe_debug(0, 'formElement field_settings', $field_settings);
    // \iframe_debug
    // (0, 'formElement defaultSettings', self::defaultSettings());
    /** @var \Drupal\iframe\Plugin\Field\FieldType\IframeItem $item */
    $item =& $items[$delta];
    $field_definition = $item->getFieldDefinition();
    /* on_admin_page TRUE only if on field edit page, not on widget-edit */
    $on_admin_page = isset($element['#field_parents'][0]) && ('default_value_input' == $element['#field_parents'][0]);
    $is_new = $item->getEntity()->isNew();
    // \iframe_debug
    // (0, 'formElement onAdminPage', $on_admin_page ? "TRUE" : "false");
    // \iframe_debug(0, 'formElement isNew', $is_new ? "TRUE" : "false");
    $values = $item->toArray();

    $settings = [];
    /* take widget_settings only if NOT on_admin_page (so not on field-edit-page, where we edit the field_settings) */
    if (!$on_admin_page) {
      foreach ($widget_settings as $wkey => $wvalue) {
        if (empty($wvalue) && isset($field_settings[$wkey])) {
          $settings[$wkey] = $field_settings[$wkey];
        }
        else {
          $settings[$wkey] = $wvalue;
        }
      }
    }
    $settings = $settings + $field_settings + self::defaultSettings();

    if ($is_new || $on_admin_page) {
      foreach ($values as $vkey => $vval) {
        if ($vval !== NULL && $vval !== '') {
          $settings[$vkey] = $vval;
        }
      }
    }
    else {
      if (isset($settings['expose_class']) && $settings['expose_class']) {
        $this->allowedAttributes['class'] = 1;
      }
      foreach ($this->allowedAttributes as $attribute => $attrAllowed) {
        if ($attrAllowed) {
          $settings[$attribute] = $values[$attribute];
        }
      }
    }
    // \iframe_debug(0, 'add-story formElement final settings', $settings);
    foreach ($settings as $attribute => $attrValue) {
      $item->setValue($attribute, $attrValue);
    }

    $element += [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    if (!$on_admin_page) {
      $element['#title'] = $field_definition->getLabel();
    }

    /* if field is required, then url/width/height should be shown as required too! */
    $required = [];
    if (!empty($element['#required'])) {
      $required['#required'] = TRUE;
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe Title'),
      '#placeholder' => '',
      '#default_value' => $settings['title'],
      '#size' => 80,
      '#maxlength' => 255,
      '#weight' => 2,
      // '#element_validate' => array('text'),
    ] + $required;

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe URL'),
      '#placeholder' => 'https://',
      '#default_value' => $settings['url'] ? static::getUriAsDisplayableString($settings['url']) : '',
      '#size' => 80,
      '#maxlength' => 2048,
      '#weight' => 1,
      '#element_validate' => [[$this, 'validateUrl']],
    ] + $required;

    $element['width'] = [
      '#title' => $this->t('Iframe Width'),
      '#type' => 'textfield',
      '#default_value' => $settings['width'] ?? '',
      '#description' => self::getSizedescription(),
      '#maxlength' => 7,
      '#size' => 7,
      '#weight' => 3,
      '#element_validate' => [[$this, 'validateWidth']],
    ] + $required;
    $element['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iframe Height'),
      '#default_value' => $settings['height'] ?? '',
      '#description' => self::getSizedescription(),
      '#maxlength' => 7,
      '#size' => 7,
      '#weight' => 4,
      '#element_validate' => [[$this, 'validateHeight']],
    ] + $required;
    if (isset($settings['expose_class']) && $settings['expose_class']) {
      $element['class'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Additional CSS Class'),
        // ''
        '#maxlength' => 255,
        '#default_value' => $settings['class'],
        '#description' => $this->t('When output, this iframe will have this class attribute. Multiple classes should be separated by spaces.'),
        '#weight' => 5,
      ];
    }
    return $element;
  }

  /**
   * Validate width(if minimum url is defined)
   *
   * @see \Drupal\Core\Form\FormValidator
   */
  public function validateWidth(&$form, FormStateInterface &$form_state) {
    // Get settings for this field.
    $me = $this->getField($form, $form_state);

    // \iframe_debug(0, 'validateWidth', $me);
    if (!empty($me['url']) && isset($me['width'])) {
      if (empty($me['width']) || !preg_match('#^(\d+(?:\%|em|rem|vw)?|auto)$#', $me['width'])) {
        $form_state->setError($form, self::getSizedescription());
      }
    }
  }

  /**
   * Validate height (if minimum url is defined)
   *
   * @see \Drupal\Core\Form\FormValidator
   */
  public function validateHeight(&$form, FormStateInterface &$form_state) {
    // Get settings for this field.
    $me = $this->getField($form, $form_state);

    // \iframe_debug(0, 'validateHeight', $me);
    if (!empty($me['url']) && isset($me['height'])) {
      if (empty($me['height']) || !preg_match('#^(\d+(?:\%|em|rem|vh)?|auto)$#', $me['height'])) {
        $form_state->setError($form, self::getSizedescription());
      }
    }
  }

  /**
   * Validate url.
   *
   * @see \Drupal\Core\Form\FormValidator
   */
  public function validateUrl($element, FormStateInterface $form_state, $form) {
    // Replace any tokens.
    $settings = $this->getFieldSettings();
    if (isset($settings['tokensupport']) && $settings['tokensupport'] == 2) {
      $element['#value'] = $this->token->replace($element['#value'], ['user' => $this->currentUser]);
    }

    // Use Drupal core's LinkWidget to validate the url.
    // It handles conversions needed for internal and absolute urls.
    LinkWidget::validateUriElement($element, $form_state, $form);
  }

  /**
   * Return the field values.
   *
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The field.
   */
  private function getField(array &$form, FormStateInterface &$form_state) {
    $parents = $form['#parents'];
    $node = $form_state->getUserInput();

    // Remove the field property from the list of parents.
    array_pop($parents);

    // Starting from the node drill down to the field.
    $field = $node;
    for ($i = 0; $i < count($parents); $i++) {
      $field = $field[$parents[$i]];
    }

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Global values.
    $field_settings = $this->getFieldSettings();
    $settings = $this->getSettings() + $field_settings;

    if (isset($settings['expose_class']) && $settings['expose_class']) {
      $this->allowedAttributes['class'] = 1;
    }

    // \iframe_debug(0, __METHOD__ . ' settings', $settings);
    // \iframe_debug(0, __METHOD__ . ' old-values', $values);
    foreach ($values as $delta => $value) {
      $value['url'] = static::getUserEnteredStringAsUri($value['url']);

      /*
       * Validate that all keys are available,
       * in the user-has-only-some-values case too.
       */
      $testvalue = $value + $settings;
      $newvalue = [];

      foreach ($testvalue as $key => $val) {
        if (
          isset($this->allowedAttributes[$key])
          && $this->allowedAttributes[$key]
        ) {
          $newvalue[$key] = $val;
        }
        elseif (isset($settings[$key])) {
          $newvalue[$key] = $settings[$key];
        }
        else {
          $newvalue[$key] = $val;
        }
      }
      if (!empty($settings['class']) && !strstr($newvalue['class'], $settings['class'])) {
        $newvalue['class'] = trim(implode(" ", [
          $settings['class'],
          $newvalue['class'],
        ]));
      }
      $new_values[$delta] = $newvalue;
    }
    // \iframe_debug(0, __METHOD__ . ' new-values', $new_values);
    return $new_values;
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of LinkWidget::getUserEnteredStringAsUri().
   *
   * This method is copied from LinkWidget::getUriAsDisplayableString because it
   * is not public.
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *
   * @see LinkWidget::getUriAsDisplayableString()
   * @see LinkWidget::getUserEnteredStringAsUri()
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      [$entity_type, $entity_id] = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      if ($entity_type == 'node' && $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }
    elseif ($scheme === 'route') {
      $displayable_string = ltrim($displayable_string, 'route:');
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * This method is copied from LinkWidget::getUriAsDisplayableString because it
   * is not public.
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.

   * @see LinkWidget::getUserEnteredStringAsUri()
   * @see LinkWidget::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is a URI.
    $uri = trim($string);

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Support linking to nothing.
    elseif (in_array($string, ['<nolink>', '<none>', '<button>'], TRUE)) {
      $uri = 'route:' . $string;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

}

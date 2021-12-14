<?php

namespace Drupal\lti_tool_provider\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the LTIToolProviderConsumer entity.
 *
 * @ingroup lti_tool_provider
 *
 * @ContentEntityType(
 *   id = "lti_tool_provider_consumer",
 *   label = @Translation("Consumer entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" =
 *   "Drupal\lti_tool_provider\LtiToolProviderConsumerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\lti_tool_provider\Form\LtiToolProviderConsumerForm",
 *       "edit" = "Drupal\lti_tool_provider\Form\LtiToolProviderConsumerForm",
 *       "delete" =
 *   "Drupal\lti_tool_provider\Form\LtiToolProviderConsumerDeleteForm",
 *     },
 *     "access" =
 *   "Drupal\lti_tool_provider\LtiToolProviderConsumerAccessController",
 *   },
 *   base_table = "lti_tool_provider_consumer",
 *   admin_permission = "administer lti_tool_provider module",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "consumer",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/lti-tool-provider/consumer",
 *     "collection" = "/admin/config/lti-tool-provider/consumer",
 *     "edit-form" =
 *   "/admin/config/lti-tool-provider/consumer/{lti_tool_provider_consumer}/edit",
 *     "delete-form" =
 *   "/admin/config/lti-tool-provider/consumer/{lti_tool_provider_consumer}/delete",
 *   },
 * )
 */
class LtiToolProviderConsumer extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['consumer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer'))
      ->setDescription(t('The name of the Consumer entity.'))
      ->setRequired(TRUE)
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => 1,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 1,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lti_version'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('LTI version'))
      ->setDescription(t('LTI version of the Consumer entity.'))
      ->setRequired(TRUE)
      ->setSettings(
        [
          'allowed_values' => [
            'v1p0' => 'LTI 1.0/1.1',
            'v1p3' => 'LTI 1.3',
          ],
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 2,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 2,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consumer_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key'))
      ->setDescription(t('The key of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 3,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 3,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consumer_secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secret'))
      ->setDescription(t('The secret of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 4,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 4,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['platform_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Issuer (Platform Id)'))
      ->setDescription(t('The issuer of Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 5,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 5,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client Id'))
      ->setDescription(t('The Client Id of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 6,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 6,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deployment_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Deployment Id'))
      ->setDescription(t('The Deployment Id of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 7,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 7,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['key_set_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Public keyset URL'))
      ->setDescription(t('Public keyset URL of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 8,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 8,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['auth_token_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Access token URL'))
      ->setDescription(t('Access token URL of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 9,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 9,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['auth_login_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Authentication request URL'))
      ->setDescription(t('Authentication request URL of the Consumer entity.'))
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 10,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 10,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['public_key'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Public Key'))
      ->setDescription(t('The public key to share with the consumer platform.'))
      ->setSetting('target_type', 'key')
      ->setSetting('handler', 'default')
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'entity_reference_label',
          'weight' => 11,
          'settings' => [
            'link' => TRUE,
          ],
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'weight' => 11,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            'placeholder' => '',
          ],
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['private_key'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Private Key'))
      ->setDescription(t('The private key for encrypting messages sent to a consumer platform.'))
      ->setSetting('target_type', 'key')
      ->setSetting('handler', 'default')
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'entity_reference_label',
          'weight' => 12,
          'settings' => [
            'link' => TRUE,
          ],
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'weight' => 12,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            'placeholder' => '',
          ],
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name field'))
      ->setDescription(t('The LTI field to get the users unique name from. Default is "lis_person_contact_email_primary"'))
      ->setRequired(TRUE)
      ->setDefaultValue('lis_person_contact_email_primary')
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 4,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 4,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mail field'))
      ->setDescription(t('The LTI field to get the users email from. Default is "lis_person_contact_email_primary"'))
      ->setRequired(TRUE)
      ->setDefaultValue('lis_person_contact_email_primary')
      ->setSettings(
        [
          'max_length' => 512,
          'text_processing' => 0,
        ]
      )
      ->setDisplayOptions(
        'view',
        [
          'label' => 'inline',
          'type' => 'string',
          'weight' => 5,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string',
          'weight' => 5,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Date created'))
      ->setDescription(t('Date the consumer was created'));

    return $fields;
  }

}

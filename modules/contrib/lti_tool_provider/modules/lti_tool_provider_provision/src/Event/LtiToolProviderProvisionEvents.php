<?php

namespace Drupal\lti_tool_provider_provision\Event;

final class LtiToolProviderProvisionEvents {

  /**
   * Occurs before entity is provisioned.
   *
   * @Event("Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionedEntityEvent")
   */
  const CREATE_ENTITY = 'lti_tool_provider_provision.create.entity';

  /**
   * Occurs before provision record is created.
   *
   * @Event("Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionCreateProvisionEvent")
   */
  const CREATE_PROVISION = 'lti_tool_provider_provision.create.provision';

  /**
   * Occurs before the provisioned entity fields are synced.
   *
   * @Event("Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionSyncProvisionedEntityEvent")
   */
  const SYNC_ENTITY = 'lti_tool_provider_provision.sync.entity';

  /**
   * Occurs after provisioning but before redirection to entity.
   *
   * @Event("Drupal\lti_tool_provider_provision\Event\LtiToolProviderProvisionRedirectEvent")
   */
  const REDIRECT = 'lti_tool_provider_provision.redirect';

}

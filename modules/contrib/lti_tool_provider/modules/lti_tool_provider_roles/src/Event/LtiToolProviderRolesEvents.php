<?php

namespace Drupal\lti_tool_provider_roles\Event;

final class LtiToolProviderRolesEvents {

  /**
   * Occurs before roles are provisioned.
   *
   * @Event("Drupal\lti_tool_provider_roles\Event\LtiToolProviderRolesProvisionEvent")
   */
  const PROVISION = 'lti_tool_provider_roles.provision';

}

<?php

namespace Drupal\lti_tool_provider_attributes\Event;

final class LtiToolProviderAttributesEvents {

  /**
   * Occurs before user attributes are provisioned.
   *
   * @Event("Drupal\lti_tool_provider_attributes\Event\LtiToolProviderAttributesProvisionEvent")
   */
  const PROVISION = 'lti_tool_provider_attributes.provision';

}

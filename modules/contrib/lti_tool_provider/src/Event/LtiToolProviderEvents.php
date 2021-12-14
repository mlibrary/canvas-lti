<?php

namespace Drupal\lti_tool_provider\Event;

final class LtiToolProviderEvents {

  /**
   * Occurs after a user is authenticated via LTI.
   *
   * @Event("Drupal\lti_tool_provider\Event\LtiToolProviderAuthenticatedEvent")
   */
  const AUTHENTICATED = 'lti_tool_provider.authenticated';

  /**
   * Occurs the first time a user is provisioned.
   *
   * @Event("Drupal\lti_tool_provider\Event\LtiToolProviderCreateUserEvent")
   */
  const CREATE_USER = 'lti_tool_provider.create.user';

  /**
   * Occurs each time a user is provisioned, before authentication is complete.
   *
   * @Event("Drupal\lti_tool_provider\Event\LtiToolProviderProvisionUserEvent")
   */
  const PROVISION_USER = 'lti_tool_provider.provision.user';

  /**
   * Occurs after a user is authenticated but before redirect.
   *
   * @Event("Drupal\lti_tool_provider\Event\LtiToolProviderLaunchEvent")
   */
  const LAUNCH = 'lti_tool_provider.launch';

  /**
   * Occurs before user is logged out and redirected to consumer platform.
   *
   * @Event("Drupal\lti_tool_provider\Event\LtiToolProviderReturnEvent")
   */
  const RETURN = 'lti_tool_provider.return';

}

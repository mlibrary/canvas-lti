<?php

namespace Drupal\lti_tool_provider_content\Event;

final class LtiToolProviderContentEvents {

  /**
   * Occurs before content selection redirect.
   *
   * @Event("Drupal\lti_tool_provider_content\Event\LtiToolProviderContentSelectEvent")
   */
  const SELECT = 'lti_tool_provider_content.select';

  /**
   * Occurs before creating resource link.
   *
   * @Event("Drupal\lti_tool_provider_content\Event\LtiToolProviderContentResourceEvent")
   */
  const RESOURCE = 'lti_tool_provider_content.resource';

  /**
   * Occurs before return to platform.
   *
   * @Event("Drupal\lti_tool_provider_content\Event\LtiToolProviderContentReturnEvent")
   */
  const RETURN = 'lti_tool_provider_content.return';

  /**
   * Occurs during launch, before redirect to entity.
   *
   * @Event("Drupal\lti_tool_provider_content\Event\LtiToolProviderContentLaunchEvent")
   */
  const LAUNCH = 'lti_tool_provider_content.launch';

}

<?php

namespace Drupal\lti_tool_provider_content\Event;

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Drupal\Component\EventDispatcher\Event;

class LtiToolProviderContentReturnEvent extends Event {

  /**
   * @var \OAT\Library\Lti1p3Core\Message\LtiMessageInterface
   */
  private $message;

  /**
   * LtiToolProviderContentReturnEvent constructor.
   *
   * @param \OAT\Library\Lti1p3Core\Message\LtiMessageInterface $message
   */
  public function __construct(LtiMessageInterface $message) {
    $this->setMessage($message);
  }

  /**
   * @return \OAT\Library\Lti1p3Core\Message\LtiMessageInterface
   */
  public function getMessage(): LtiMessageInterface {
    return $this->message;
  }

  /**
   * @param \OAT\Library\Lti1p3Core\Message\LtiMessageInterface $message
   */
  public function setMessage(LtiMessageInterface $message): void {
    $this->message = $message;
  }

}

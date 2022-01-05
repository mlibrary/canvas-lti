<?php

namespace Drupal\lti_tool_provider\Authentication\Provider;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\lti_tool_provider\Entity\LtiToolProviderConsumer;
use Drupal\lti_tool_provider\Entity\Nonce;
use Drupal\lti_tool_provider\LTIToolProviderContext;
use Drupal\lti_tool_provider\LTIToolProviderContextInterface;
use Exception;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAuthProvider;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;

class LTIToolProviderV1P0 extends LTIToolProviderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request): bool {
    return LTIToolProviderV1P0::isValidLaunchRequest($request);
  }

  /**
   * @param $request
   *
   * @return bool
   *
   * @see https://www.imsglobal.org/wiki/step-1-lti-launch-request
   */
  static function isValidLaunchRequest($request): bool {
    $lti_message_type = $request->request->get('lti_message_type');
    $lti_version = $request->request->get('lti_version');
    $oauth_consumer_key = $request->request->get('oauth_consumer_key');
    $resource_link_id = $request->request->get('resource_link_id');

    if (!$request->isMethod('POST')) {
      return FALSE;
    }

    if ($lti_message_type !== 'basic-lti-launch-request') {
      return FALSE;
    }

    if (!in_array($lti_version, ['LTI-1p0', 'LTI-1p2'])) {
      return FALSE;
    }

    if (empty($oauth_consumer_key)) {
      return FALSE;
    }

    if (empty($resource_link_id)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Looks up the consumer entity that matches the consumer key.
   *
   * @param OAuthProvider $provider
   *
   * @return int
   *   - OAUTH_OK if validated.
   *   - OAUTH_CONSUMER_KEY_UNKNOWN if not.
   */
  public function consumerHandler(OAuthProvider $provider): int {
    if (!isset($provider->consumer_key)) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }

    try {
      $consumers = $this->entityTypeManager
        ->getStorage('lti_tool_provider_consumer')
        ->loadByProperties(['consumer_key' => $provider->consumer_key]);

      $consumer = reset($consumers);

      if (!($consumer instanceof LtiToolProviderConsumer)) {
        throw new Exception("Client not found.");
      }
    }
    catch (Exception $e) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }

    if (!($consumer instanceof LtiToolProviderConsumer)) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }

    // This must be set dynamically since OauthProvider doesn't declare variables.
    $provider->consumer_secret = $consumer->get('consumer_secret')
      ->getValue()[0]['value'];

    if (!is_string($provider->consumer_secret)) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }

    return OAUTH_OK;
  }

  /**
   * Validate nonce.
   *
   * @param $provider
   *
   * @return int
   *   - OAUTH_OK if validated.
   *   - OAUTH_BAD_TIMESTAMP if timestamp too old.
   *   - OAUTH_BAD_NONCE if nonce has been used.
   */
  public function timestampNonceHandler($provider): int {
    // Verify timestamp has been set.
    if (!isset($provider->timestamp)) {
      return OAUTH_BAD_TIMESTAMP;
    }

    // Verify nonce timestamp is not older than now - nonce interval.
    if ($provider->timestamp < (time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL)) {
      return OAUTH_BAD_TIMESTAMP;
    }

    // Verify nonce timestamp is not newer than now + nonce interval.
    if ($provider->timestamp > (time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL)) {
      return OAUTH_BAD_TIMESTAMP;
    }

    // Verify nonce has been set.
    if (!isset($provider->nonce)) {
      return OAUTH_BAD_NONCE;
    }

    try {
      $nonceEntities = $this->entityTypeManager
        ->getStorage('lti_tool_provider_nonce')
        ->loadByProperties(['nonce' => $provider->nonce]);

      $nonce = reset($nonceEntities);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->loggerFactory->error($e->getMessage());
      return OAUTH_BAD_NONCE;
    }

    // Ensure that there isn't a nonce entry.
    if ($nonce instanceof Nonce) {
      return OAUTH_BAD_NONCE;
    }


    try {
      $this->entityTypeManager
        ->getStorage('lti_tool_provider_nonce')
        ->create(
          [
            'nonce' => $provider->nonce,
            'timestamp' => $provider->timestamp,
          ]
        )
        ->save();
    }
    catch (Exception $e) {
      $this->loggerFactory->error($e->getMessage());
      return OAUTH_BAD_NONCE;
    }

    return OAUTH_OK;
  }

  /**
   * @param \Psr\Http\Message\ServerRequestInterface $request
   *
   * @return \Drupal\lti_tool_provider\LTIToolProviderContextInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  protected function validate(ServerRequestInterface $request): LTIToolProviderContextInterface {
    $payload = $request->getParsedBody();
    $provider = new OAuthProvider(["oauth_signature_method" => OAUTH_SIG_METHOD_HMACSHA1]);
    $provider->consumerHandler([$this, 'consumerHandler']);
    $provider->timestampNonceHandler([$this, 'timestampNonceHandler']);
    $provider->isRequestTokenEndpoint(FALSE);
    $provider->is2LeggedEndpoint(TRUE);
    $provider->checkOAuthRequest();

    if (!isset($provider->consumer_key)) {
      throw new Exception('No consumer key.');
    }

    $consumers = $this->entityTypeManager
      ->getStorage('lti_tool_provider_consumer')
      ->loadByProperties(['consumer_key' => $provider->consumer_key]);

    $consumer = reset($consumers);

    if (!($consumer instanceof LtiToolProviderConsumer)) {
      throw new Exception('No consumer found.');
    }

    $name = $payload[$consumer->get('name')->getValue()[0]['value']];
    $mail = $payload[$consumer->get('mail')->getValue()[0]['value']];
    $userIdentity = new UserIdentity(0, $name, $mail);

    $payload['consumer_id'] = $consumer->id();
    $payload['consumer_label'] = $consumer->label();

    return new LTIToolProviderContext($userIdentity, $payload);
  }

}

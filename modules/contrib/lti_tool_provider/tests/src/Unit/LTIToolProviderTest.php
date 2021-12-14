<?php

namespace Drupal\Tests\lti_tool_provider\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\lti_tool_provider\Authentication\Provider\LTIToolProvider;
use Drupal\Tests\UnitTestCase;
use OauthProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

define("LTI_TOOL_PROVIDER_NONCE_INTERVAL", (5 * 60));
define("LTI_TOOL_PROVIDER_NONCE_EXPIRY", (1.5 * 60 * 60));

if (!class_exists('\Oauth')) {
  define("OAUTH_OK", 0);
  define("OAUTH_BAD_NONCE", 4);
  define("OAUTH_BAD_TIMESTAMP", 8);
  define("OAUTH_CONSUMER_KEY_UNKNOWN", 16);
}

/**
 * LTIToolProvider unit tests.
 *
 * @ingroup lti_tool_provider
 *
 * @group lti_tool_provider
 *
 * @coversDefaultClass \Drupal\lti_tool_provider\Authentication\Provider\LTIToolProvider
 */
class LTIToolProviderTest extends UnitTestCase {

  /**
   * The mocked configuration factory.
   *
   * @var ConfigFactoryInterface|MockObject
   */
  protected $configFactory;

  /**
   * The mocked Entity Manager.
   *
   * @var EntityTypeManagerInterface|MockObject
   */
  protected $entityTypeManager;

  /**
   * A mocked logger instance.
   *
   * @var LoggerChannelFactoryInterface|MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked module handler.
   *
   * @var EventDispatcherInterface|MockObject
   */
  protected $eventDispatcher;

  /**
   * The mocked PECL OauthProvider class.
   *
   * @var OauthProvider | mixed
   */
  protected $provider;

  /**
   * Test the applies() method.
   *
   * @dataProvider appliesProvider
   * @covers ::applies
   * @covers ::__construct
   *
   * @param $expected
   * @param $request
   */
  public function testApplies($expected, $request) {
    $provider = new LTIToolProvider(
      $this->configFactory,
      $this->entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $actual = $provider->applies($request);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Request Provider.
   */
  public function appliesProvider(): array {
    return [
      'empty request' => [FALSE, Request::create('/lti', 'POST', [])],
      'get request' => [
        FALSE,
        Request::create(
          '/lti',
          'GET',
          [
            'oauth_consumer_key' => 'oauth_consumer_key',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
          ]
        ),
      ],
      'LTI-1p0 request' => [
        TRUE,
        Request::create(
          '/lti',
          'POST',
          [
            'oauth_consumer_key' => 'oauth_consumer_key',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
          ]
        ),
      ],
      'LTI-1p2 request' => [
        TRUE,
        Request::create(
          '/lti',
          'POST',
          [
            'oauth_consumer_key' => 'oauth_consumer_key',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p2',
            'resource_link_id' => 'resource_link_id',
          ]
        ),
      ],
      'missing resource link request' => [
        FALSE,
        Request::create(
          '/lti',
          'POST',
          [
            'oauth_consumer_key' => 'oauth_consumer_key',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
          ]
        ),
      ],
      'empty resource link request' => [
        FALSE,
        Request::create(
          '/lti',
          'POST',
          [
            'oauth_consumer_key' => 'oauth_consumer_key',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => '',
          ]
        ),
      ],
      'missing oauth consumer key request' => [
        FALSE,
        Request::create(
          '/lti',
          'POST',
          [
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
          ]
        ),
      ],
      'empty oauth_consumer_key request' => [
        FALSE,
        Request::create(
          '/lti',
          'POST',
          [
            'oauth_consumer_key' => '',
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => 'resource_link_id',
          ]
        ),
      ],
    ];
  }

  /**
   * Test the timestampNonceHandler() method.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerMissingTimestamp() {
    $provider = new LTIToolProvider(
      $this->configFactory,
      $this->entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $expected = OAUTH_BAD_TIMESTAMP;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test the timestampNonceHandler() method.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerMissingNonceConsumer() {
    $provider = new LTIToolProvider(
      $this->configFactory,
      $this->entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $this->provider->timestamp = time();
    $expected = OAUTH_BAD_NONCE;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests a nonce timestamp that is too old.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerOld() {
    $provider = new LTIToolProvider(
      $this->configFactory,
      $this->entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL - 10;

    $expected = OAUTH_BAD_TIMESTAMP;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests a nonce timestamp that is almost too old.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerAlmostTooOld() {
    $provider = $this->getNonceSpecificLtiToolProvider();

    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time() - LTI_TOOL_PROVIDER_NONCE_INTERVAL;

    $expected = OAUTH_OK;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Generate a entity type manager for testing timestampNonceHandler.
   */
  public function getNonceSpecificLtiToolProvider(): LTIToolProvider {
    $entityTypeManager = $this->entityTypeManager;

    $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->once())
      ->method('condition')
      ->will($this->returnValue($query));
    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue([]));

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($query));
    $storage->expects($this->once())
      ->method('create')
      ->will($this->returnValue($this->createMock('Drupal\Core\Entity\EntityInterface')));

    $entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->will($this->returnValue($storage));

    return new LTIToolProvider(
      $this->configFactory,
      $entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );
  }

  /**
   * Tests a nonce timestamp that is current.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerCurrent() {
    $provider = $this->getNonceSpecificLtiToolProvider();

    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time();

    $expected = OAUTH_OK;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests a nonce timestamp that is almost too new.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerAlmostTooNew() {
    $provider = $this->getNonceSpecificLtiToolProvider();

    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL;

    $expected = OAUTH_OK;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests a nonce timestamp that is too new.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampNonceHandlerTooNew() {
    $provider = new LTIToolProvider(
      $this->configFactory,
      $this->entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time() + LTI_TOOL_PROVIDER_NONCE_INTERVAL + 10;

    $expected = OAUTH_BAD_TIMESTAMP;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests duplicate nonces.
   *
   * @covers ::timestampNonceHandler
   * @covers ::__construct
   */
  public function testTimestampDuplicateNonce() {
    $entityTypeManager = $this->entityTypeManager;
    $this->provider->consumer_key = '';
    $this->provider->nonce = uniqid();
    $this->provider->timestamp = time();

    $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->once())
      ->method('condition')
      ->will($this->returnValue($query));
    $query->expects($this->once())
      ->method('execute')
      ->will($this->returnValue([$this->createMock('Drupal\Core\Entity\EntityInterface')]));

    $storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($query));

    $entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->will($this->returnValue($storage));

    $provider = new LTIToolProvider(
      $this->configFactory,
      $entityTypeManager,
      $this->loggerFactory,
      $this->eventDispatcher
    );

    $expected = OAUTH_BAD_NONCE;
    $actual = $provider->timestampNonceHandler($this->provider);
    $this->assertEquals($expected, $actual);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');

    $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcher')
      ->onlyMethods(['__construct'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannelFactory')
      ->addMethods(['__construct'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->provider = $this->getMockBuilder('\OAuthProvider')
      ->onlyMethods(['__construct', 'checkOAuthRequest'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->provider->expects($this->any())
      ->method('checkOAuthRequest')
      ->will($this->returnValue(TRUE));
  }

}

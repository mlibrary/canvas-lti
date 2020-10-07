<?php

namespace Drupal\Tests\lti_tool_provider\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\lti_tool_provider\Controller\LTIToolProviderController;
use Drupal\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * LTIToolProviderController unit tests.
 *
 * @ingroup lti_tool_provider
 *
 * @group lti_tool_provider
 *
 * @coversDefaultClass \Drupal\lti_tool_provider\Controller\LTIToolProviderController
 */
class LTIToolProviderControllerTest extends UnitTestCase
{
    /**
     * @var ConfigFactoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $configFactory;

    /**
     * @var LoggerChannelFactoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerFactory;

    /**
     * @var EventDispatcherInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ResponsePolicyInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $killSwitch;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configFactory = $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface');

        $this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcher')
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->killSwitch = $this->getMockBuilder('\Drupal\Core\PageCache\ResponsePolicy\KillSwitch')
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerFactory = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannelFactory')
            ->setMethods(['__construct'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider accessDataProvider
     * @covers ::access
     * @covers ::__construct
     * @param $expected
     * @param Request $request
     * @param SessionInterface $session
     * @param mixed $context
     * @param string $destination
     */
    public function testAccess($expected, Request $request, SessionInterface $session, $context, $destination)
    {
        $controller = new LTIToolProviderController(
            $this->configFactory,
            $this->loggerFactory,
            $this->eventDispatcher,
            $this->killSwitch,
            $request,
            $session,
            $context,
            $destination
        );

        $actual = $controller->access();
        $this->assertInstanceOf(AccessResult::class, $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function accessDataProvider()
    {
        return [
            'no context' => [AccessResult::neutral(), Request::create('/lti', 'GET'), new Session(), null, null],
            'with context' => [AccessResult::allowed(), Request::create('/lti', 'GET'), new Session(), [1], null],
        ];
    }

    /**
     * @covers ::ltiLaunch
     * @covers ::__construct
     */
    public function testLtiLaunchNoContext()
    {
        $controller = new LTIToolProviderController(
            $this->configFactory,
            $this->loggerFactory,
            $this->eventDispatcher,
            $this->killSwitch,
            new Request(),
            new Session(),
            null,
            '/'
        );

        $this->expectException(InvalidArgumentException::class);
        $controller->ltiLaunch();
    }

    /**
     * @dataProvider ltiLaunchWithContextDataProvider
     * @covers ::ltiLaunch
     * @covers ::__construct
     * @param $expected
     * @param Request $request
     * @param SessionInterface $session
     * @param mixed $context
     * @param string $destination
     */
    public function testLtiLaunchWithContext(RedirectResponse $expected, Request $request, SessionInterface $session, $context, $destination)
    {
        $controller = new LTIToolProviderController(
            $this->configFactory,
            $this->loggerFactory,
            $this->eventDispatcher,
            $this->killSwitch,
            $request,
            $session,
            $context,
            $destination
        );

        $actual = $controller->ltiLaunch();
        $this->assertInstanceOf(RedirectResponse::class, $actual);
        $this->assertEquals($expected->getTargetUrl(), $actual->getTargetUrl());
    }

    /**
     * @return array
     */
    public function ltiLaunchWithContextDataProvider()
    {
        return [
            'destination from settings' => [
                new RedirectResponse('/'),
                Request::create('/lti', 'GET'),
                new Session(),
                ['no destination url'],
                '/',
            ],
            'destination from context' => [
                new RedirectResponse('/'),
                Request::create('/lti', 'GET'),
                new Session(),
                ['custom_destination' => '/'],
                null,
            ],
        ];
    }

    /**
     * @covers ::ltiReturn
     * @covers ::__construct
     */
    public function testLtiReturnNoContext()
    {
        $controller = new LTIToolProviderController(
            $this->configFactory,
            $this->loggerFactory,
            $this->eventDispatcher,
            $this->killSwitch,
            new Request(),
            new Session(),
            null,
            '/'
        );

        $this->expectException(InvalidArgumentException::class);
        $controller->ltiReturn();
    }

    /**
     * @dataProvider ltiReturnWithContextDataProvider
     * @covers ::ltiReturn
     * @covers ::__construct
     * @param $expected
     * @param Request $request
     * @param SessionInterface $session
     * @param mixed $context
     * @param string $destination
     */
    public function testLtiReturnWithContext(
        TrustedRedirectResponse $expected,
        Request $request,
        SessionInterface $session,
        $context,
        $destination
    ) {
        /* @var $controller LTIToolProviderController */
        $controller = $this->getMockBuilder('Drupal\lti_tool_provider\Controller\LTIToolProviderController')
            ->setConstructorArgs(
                [
                    $this->configFactory,
                    $this->loggerFactory,
                    $this->eventDispatcher,
                    $this->killSwitch,
                    $request,
                    $session,
                    $context,
                    $destination,
                ]
            )
            ->setMethods(['userLogout'])
            ->getMock();


        $actual = $controller->ltiReturn();
        $this->assertInstanceOf(TrustedRedirectResponse::class, $actual);
        $this->assertEquals($expected->getTargetUrl(), $actual->getTargetUrl());
    }

    /**
     * @return array
     */
    public function ltiReturnWithContextDataProvider()
    {
        return [
            'destination from settings' => [
                new TrustedRedirectResponse('/home'),
                Request::create('/lti', 'GET'),
                new Session(),
                ['no destination url'],
                '/home',
            ],
            'destination from context' => [
                new TrustedRedirectResponse('/home'),
                Request::create('/lti', 'GET'),
                new Session(),
                ['launch_presentation_return_url' => '/home'],
                null,
            ],
        ];
    }
}

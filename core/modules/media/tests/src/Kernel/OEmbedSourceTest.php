<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\media\Source\OEmbed;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\media\Plugin\media\Source\OEmbed
 *
 * @group media
 */
class OEmbedSourceTest extends MediaKernelTestBase {

  /**
   * @covers ::getMetadata
   */
  public function testGetMetadata() {
    $configuration = [
      'source_field' => 'field_test_oembed',
    ];
    $plugin = OEmbed::create($this->container, $configuration, 'oembed', []);

    // Test that NULL is returned for a media item with no source value.
    $media = $this->prophesize('\Drupal\media\MediaInterface');
    $field_items = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
    $field_items->isEmpty()->willReturn(TRUE);
    $media->get($configuration['source_field'])->willReturn($field_items->reveal());
    $this->assertNull($plugin->getMetadata($media->reveal(), 'type'));
  }

  /**
   * @covers ::getLocalThumbnailUri
   */
  public function testLocalThumbnailUriQueryStringIsIgnored() {
    // There's no need to resolve the resource URL in this test; we just need
    // to fetch the resource.
    $this->container->set(
      'media.oembed.url_resolver',
      $this->prophesize(UrlResolverInterface::class)->reveal()
    );

    $thumbnail_url = Url::fromUri('internal:/core/misc/druplicon.png?foo=bar');

    // Create a mocked resource whose thumbnail URL contains a query string.
    $resource = $this->prophesize(Resource::class);
    $resource->getTitle()->willReturn('Test resource');
    $resource->getThumbnailUrl()->willReturn($thumbnail_url);

    // The source plugin will try to fetch the remote thumbnail, so mock the
    // HTTP client to ensure that request returns an empty "OK" response.
    $http_client = $this->prophesize(Client::class);
    $http_client->request('GET', Argument::type('string'))->willReturn(new Response());
    $this->container->set('http_client', $http_client->reveal());

    // Mock the resource fetcher so that it will return our mocked resource.
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);
    $resource_fetcher->fetchResource(NULL)->willReturn($resource->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $media_type = $this->createMediaType('oembed:video');
    $source = $media_type->getSource();

    $media = Media::create([
      'bundle' => $media_type->id(),
      $source->getSourceFieldDefinition($media_type)->getName() => $this->randomString(),
    ]);
    $media->save();

    // Get the local thumbnail URI and ensure that it does not contain any
    // query string.
    $local_thumbnail_uri = $media_type->getSource()->getMetadata($media, 'thumbnail_uri');
    $expected_uri = 'public://oembed_thumbnails/' . Crypt::hashBase64('/core/misc/druplicon.png') . '.png';
    $this->assertSame($expected_uri, $local_thumbnail_uri);
  }

  /**
   * Data provider for ::testGetThumbnailUri().
   *
   * @return array
   *   The sets of arguments to pass to the test method.
   */
  public function providerGetThumbnailUri() {
    return [
      'with file extension' => [
        'https://upload.wikimedia.org/wikipedia/commons/9/90/Dries_Buytaert_at_FOSDEM_2008_by_Wikinews.jpg',
      ],
      'no file extension' => [
        'http://placekitten.com/200/300',
      ],
    ];
  }

  /**
   * Tests remote thumbnail URL handling.
   *
   * @param string $thumbnail_url
   *   The remote URL of the thumbnail.
   *
   * @dataProvider providerGetThumbnailUri
   */
  public function testGetThumbnailUri($thumbnail_url) {
    // We will need a media type that uses the oEmbed plugin.
    $media = Media::create([
      'bundle' => $this->createMediaType('oembed:video')->id(),
      'field_media_oembed_video' => 'test',
    ]);

    // Mock the services which are used to fetch the resource and thumbnail.
    $url_resolver = $this->prophesize('\Drupal\media\OEmbed\UrlResolverInterface');
    $resource_fetcher = $this->prophesize('\Drupal\media\OEmbed\ResourceFetcherInterface');
    $http_client = $this->prophesize('\GuzzleHttp\ClientInterface');

    $resource = $this->prophesize('\Drupal\media\OEmbed\Resource');
    $resource->getThumbnailUrl()->willReturn(Url::fromUri($thumbnail_url));

    $url_resolver->getResourceUrl('test')->willReturnArgument(0);
    $resource_fetcher->fetchResource('test')->willReturn($resource->reveal());

    // The HTTP client should make a HEAD request ONLY if the thumbnail URL
    // doesn't have a file extension.
    if (pathinfo($thumbnail_url, PATHINFO_EXTENSION)) {
      $http_client->request('HEAD', $thumbnail_url)->shouldNotBeCalled();
    }
    else {
      $response = new Response(200, [
        'Content-Type' => [
          'image/jpeg',
        ],
      ]);
      $http_client->request('HEAD', $thumbnail_url)
        // A HEAD request should be made every time the thumbnail is requested
        // when it lacks a file extension.
        ->shouldBeCalledTimes(2)
        ->willReturn($response);
    }
    // We always expect the actual thumbnail to be retrieved but a GET
    // request should only be made ONCE when first downloading the file.
    $http_client->request('GET', $thumbnail_url)
      ->shouldBeCalledOnce()
      ->willReturn(new Response());

    $plugin = new OEmbed(
      ['source_field' => 'field_media_oembed_video'],
      'oembed',
      [],
      $this->container->get('entity_type.manager'),
      $this->container->get('entity_field.manager'),
      $this->container->get('config.factory'),
      $this->container->get('plugin.manager.field.field_type'),
      $this->container->get('logger.factory')->get('media'),
      $this->container->get('messenger'),
      $http_client->reveal(),
      $resource_fetcher->reveal(),
      $url_resolver->reveal(),
      $this->container->get('media.oembed.iframe_url_helper'),
      $this->container->get('file_system')
    );

    // Get the local thumbnail URI twice, to ensure that it is only downloaded
    // (i.e., with a GET request) once. If the thumbnail URL is remote, we
    // expect a HEAD request to be made each time by
    // getRemoteThumbnailPathExtension().
    for ($i = 0; $i < 2; $i++) {
      $local_thumbnail_uri = $plugin->getMetadata($media, 'thumbnail_uri');
      $this->assertNotEmpty($local_thumbnail_uri);
      $this->assertFileExists($local_thumbnail_uri);
      $this->assertRegExp('/.jpe?g$/i', $local_thumbnail_uri);
    }
  }

}

<?php

namespace Drupal\s3fs;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\Core\Site\Settings;
use Drupal\s3fs\Compiler\S3fsMimeTypePass;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The stream wrapper class.
 *
 * In the docs for this class, anywhere you see "<scheme>", it can mean either
 * "s3" or "public", depending on which stream is currently being serviced.
 */
class S3fsServiceProvider extends ServiceProviderBase {

  /**
   * Modifies existing service definitions.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    if (Settings::get('s3fs.use_s3_for_public')) {
      // Replace the public stream wrapper with S3fsStream.
      $container->getDefinition('stream_wrapper.public')
        ->setClass('Drupal\s3fs\StreamWrapper\PublicS3fsStream');

      // Fix CSS static urls prior to Drupal 10.1 when they used the public
      // scheme.
      if (version_compare(\Drupal::VERSION, '10.1', '<')) {
        $container->getDefinition('asset.css.optimizer')
          ->setClass('Drupal\s3fs\Asset\S3fsCssOptimizer')
          ->setArguments([
            new Reference('config.factory'),
          ]);
      }

      if ($container->hasDefinition('file_url_generator')) {
        // @todo Move up to setArguments above when D9.3 is minimally supported version.
        $container->getDefinition('asset.css.optimizer')
          ->addArgument(new Reference('file_url_generator'));
      }
    }

    if (Settings::get('s3fs.use_s3_for_private') && $container->hasDefinition('stream_wrapper.private')) {
      // Replace the private stream wrapper with S3fsStream.
      $container->getDefinition('stream_wrapper.private')
        ->setClass('Drupal\s3fs\StreamWrapper\PrivateS3fsStream');
    }

    if (version_compare(\Drupal::VERSION, '10.2.9999999', '<=')) {
      $container->getDefinition('s3fsfileservice')
        // Deprecated class used for older version of Drupal Core.
        // @phpstan-ignore-next-line classConstant.deprecatedClass
        ->setClass(S3fsFileService::class);
    }

    if (!static::mimeGuesserUsesSetterInjection()) {
      $container->getDefinition('s3fs.mime_type.guesser')->setArguments([
        new Reference('stream_wrapper_manager'),
        new TaggedIteratorArgument('s3fs_mime_type_guesser'),
      ]);
    }
  }

  /**
   * Whether the MimeTypeGuesser services uses setter injection.
   *
   * Core 11.4+ migrated to providing a service locator to the constructor.
   */
  protected static function mimeGuesserUsesSetterInjection(): bool {
    return method_exists(MimeTypeGuesser::class, 'addMimeTypeGuesser')
      || method_exists(MimeTypeGuesser::class, 'addGuesser');
  }

  /**
   * Register dynamic service definitions.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be checked.
   */
  public function register(ContainerBuilder $container) {

    // D11.4+ uses a tagged iterator to constructor, no compiler pass required.
    if (static::mimeGuesserUsesSetterInjection()) {
      $container->addCompilerPass(new S3fsMimeTypePass());
    }

    if ($container->hasDefinition('advagg.optimizer.css') && Settings::get('s3fs.use_s3_for_public')) {
      $container
        ->register('s3fs.advagg.css_subscriber', 'Drupal\s3fs\EventSubscriber\S3fsAdvAggSubscriber')
        ->addTag('event_subscriber')
        ->setArguments([new Reference('config.factory')]);

      if ($container->hasDefinition('file_url_generator')) {
        // @todo Move to setArguments above when D9.3 is minimally supported versions.
        $container->getDefinition('s3fs.advagg.css_subscriber')
          ->addArgument(new Reference('file_url_generator'));
      }
    }
  }

}

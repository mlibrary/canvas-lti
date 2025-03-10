<?php

declare(strict_types=1);

namespace Doctrine\Persistence\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Contract for metadata drivers.
 */
interface MappingDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @phpstan-param class-string<T> $className
     * @phpstan-param ClassMetadata<T> $metadata
     *
     * @template T of object
     */
    public function loadMetadataForClass(string $className, ClassMetadata $metadata): void;

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array<int, string> The names of all mapped classes known to this driver.
     * @phpstan-return list<class-string>
     */
    public function getAllClassNames(): array;

    /**
     * Returns whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a MappedSuperclass.
     *
     * @phpstan-param class-string $className
     */
    public function isTransient(string $className): bool;
}

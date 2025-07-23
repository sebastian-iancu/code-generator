<?php

namespace OpenEHR\Tools\CodeGen\Model\Bmm;

use InvalidArgumentException;
use JsonSerializable;
use OpenEHR\Tools\CodeGen\Model\Collection;

/**
 * Class representing the top-level BMM schema structure
 */
readonly class BmmSchema implements JsonSerializable
{

    public const string BMM_VERSION = '2.4';

    /**
     * @param string $rmPublisher
     * @param string $rmRelease
     * @param string $schemaName
     * @param string $schemaRevision
     * @param string $schemaLifecycleState
     * @param string $schemaDescription
     * @param string $schemaAuthor
     * @param Collection<string, BmmPackage> $packages
     * @param Collection<string, BmmClass>|null $primitiveTypes
     * @param Collection<string, BmmClass>|null $classDefinitions
     * @param Collection<string, BmmSchemaInclude>|null $includes
     * @param string|null $bmmVersion
     */
    public function __construct(
        public string $rmPublisher,
        public string $schemaName,
        public string $rmRelease,
        public string $schemaRevision,
        public string $schemaLifecycleState,
        public string $schemaDescription,
        public string $schemaAuthor,
        public Collection $packages,
        public ?Collection $primitiveTypes = new Collection(),
        public ?Collection $classDefinitions = new Collection(),
        public ?Collection $includes = new Collection(),
        public ?string $bmmVersion = self::BMM_VERSION,
    )
    {
    }

    public function getSchemaId(): string{
        return $this->rmPublisher . '_' . $this->schemaName . '_' . $this->rmRelease;
    }


    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'bmm_version' => $this->bmmVersion,
            'rm_publisher' => $this->rmPublisher,
            'schema_name' => $this->schemaName,
            'rm_release' => $this->rmRelease,
            'schema_revision' => $this->schemaRevision,
            'schema_lifecycle_state' => $this->schemaLifecycleState,
            'schema_description' => $this->schemaDescription,
            'schema_author' => $this->schemaAuthor,
            'includes' => $this->includes->getArrayCopy(),
            'packages' => $this->packages->getArrayCopy(),
            'primitive_types' => $this->primitiveTypes->getArrayCopy(),
            'class_definitions' => $this->classDefinitions->getArrayCopy(),
        ]);
    }

    /**
     * Create a BMMSchema from a JSON array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            rmPublisher: $data['rm_publisher'],
            schemaName: $data['schema_name'],
            rmRelease: $data['rm_release'],
            schemaRevision: $data['schema_revision'],
            schemaLifecycleState: $data['schema_lifecycle_state'],
            schemaDescription: $data['schema_description'],
            schemaAuthor: $data['schema_author'],
            packages: new Collection(),
            primitiveTypes: new Collection(),
            classDefinitions: new Collection(),
            includes: new Collection(),
            bmmVersion: $data['bmm_version'] ?? self::BMM_VERSION,
        );

        if (!empty($data['packages']) && is_iterable($data['packages'])) {
            array_walk($data['packages'], function ($packageData) use ($instance) {
                $instance->packages->add(BmmPackage::fromArray($packageData));
            });
        } else {
            throw new InvalidArgumentException('Schema must contain at least one package');
        }

        if (!empty($data['primitive_types']) && is_iterable($data['primitive_types'])) {
            array_walk($data['primitive_types'], function ($primitiveTypeData) use ($instance) {
                $instance->primitiveTypes->add(AbstractBmmClass::fromArray($primitiveTypeData));
            });
        }
        if (!empty($data['class_definitions']) && is_iterable($data['class_definitions'])) {
            array_walk($data['class_definitions'], function ($classDefinitionData) use ($instance) {
                $instance->classDefinitions->add(AbstractBmmClass::fromArray($classDefinitionData));
            });
        }
        if (!empty($data['includes']) && is_iterable($data['includes'])) {
            array_walk($data['includes'], function ($includeData) use ($instance) {
                $instance->includes->add(BmmSchemaInclude::fromArray($includeData));
            });
        }

        return $instance;
    }
}

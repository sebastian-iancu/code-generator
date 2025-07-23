<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use JsonException;
use OpenEHR\Tools\CodeGen\Model\Collection;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlClass;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlConstraint;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlEnumeration;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlFile;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlOperation;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlPackage;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlParameter;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlProperty;
use OpenEHR\Tools\CodeGen\Model\Uml\UmlTemplateParameter;

class UmlToBmmWriter extends AbstractWriter
{

    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'BMM' . DIRECTORY_SEPARATOR;

    public const string REVISION = '1';
    public const string AUTHOR = 'code-generator';

    public const array SKIP_PACKAGES = ['functional', 'builtins'];

    public const array PRIMITIVES = [
        'Any',
        'Ordered',
        'Numeric',
        'Ordered_Numeric',
        'Byte',
        'Octet',
        'Boolean',
        'Integer',
        'Integer64',
        'Real',
        'Double',
        'Character',
        'String',
        'Uri',
        'Temporal',
        'Iso8601_type',
        'Date',
        'Time',
        'Date_time',
        'Duration',
        'Iso8601_date',
        'Iso8601_time',
        'Iso8601_date_time',
        'Iso8601_duration',
        'Terminology_term',
        'Terminology_code',
        'Container',
        'List',
        'Array',
        'Set',
        'Interval',
        'Cardinality',
        'Multiplicity_interval',
        'Hash',
    ];


    /**
     * @throws JsonException
     */
    public function write(): void
    {
        /** @var UmlFile $umlFile */
        foreach ($this->reader->files as $umlFile) {
            $schema_name = strtolower($umlFile->name);
            self::log('generating to [%s] schema.', $schema_name);
            $schema = [
                'bmm_version' => BmmSchema::BMM_VERSION,
                'rm_publisher' => 'openehr',
                'rm_release' => $umlFile->getRelease(),
                'schema_name' => $schema_name,
                'schema_revision' => $umlFile->getRelease() . '.' . self::REVISION,
                'schema_lifecycle_state' => 'stable',
                'schema_description' => $umlFile->umlPackage->description,
                'schema_author' => self::AUTHOR,
            ];
            $collectedUmlClasses = new Collection();
            // serializing packages and their classes
            /** @var UmlPackage $umlPackage */
            foreach ($umlFile->umlPackage->getPackages('org::openehr::' . $schema_name . '::*') as $umlPackage) {
                if (in_array($umlPackage->name, self::SKIP_PACKAGES)) {
                    continue;
                }
                $bmmPackage = self::asBmmPackage($umlPackage, "org.openehr.{$schema_name}.", $collectedUmlClasses);
                $schema['packages'][$bmmPackage['name']] = $bmmPackage;
            }
            // serializing primitive_types and class
            $schema['primitive_types'] = [];
            $schema['class_definitions'] = [];
            /** @var UmlClass $umlClass */
            foreach ($collectedUmlClasses as $umlClass) {
                if (in_array($umlClass->name, self::PRIMITIVES)) {
                    $schema['primitive_types'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                } else {
                    $schema['class_definitions'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                }
            }
            // saving as a file
            $filename = self::DIR . str_replace('.xmi', '', $umlFile->id) . '.bmm.json';
            $content = json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
            $bytes = file_put_contents($filename, $content);
            self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
        }
    }

    /**
     * @param UmlPackage $umlPackage
     * @param string $namePrefix
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmPackage(UmlPackage $umlPackage, string $namePrefix, Collection $collectedUmlClasses): array
    {
        $bmmPackage = [
            'name' => $namePrefix . $umlPackage->name,
            'packages' => [],
            'classes' => self::collectClassNames($umlPackage, $collectedUmlClasses),
        ];
        /** @var UmlPackage $childUmlPackage */
        foreach ($umlPackage->umlPackages as $childUmlPackage) {
            if (in_array($childUmlPackage->name, self::SKIP_PACKAGES)) {
                continue;
            }
            $bmmChildUmlPackage = self::asBmmPackage($childUmlPackage, '', $collectedUmlClasses);
            $bmmPackage['packages'][$bmmChildUmlPackage['name']] = $bmmChildUmlPackage;
        }
        self::log('  Generated [%s] package.', $bmmPackage['name']);
        return array_filter($bmmPackage);
    }

    /**
     * @param UmlPackage $umlPackage
     * @param Collection $collectedUmlClasses
     * @return string[]
     */
    protected static function collectClassNames(UmlPackage $umlPackage, Collection $collectedUmlClasses): array
    {
        $names = [];
        foreach ($umlPackage->umlClasses as $umlClass) {
            if (str_contains($umlClass->name, '<')) {
                continue;
            }
            $names[] = $umlClass->name;
            $collectedUmlClasses->add($umlClass);
        }
        return $names;
    }

    /**
     * @param UmlClass|UmlEnumeration $umlClass
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmClass(UmlClass|UmlEnumeration $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmClass = [
            '_type' => null,
            'name' => $umlClass->name,
            'documentation' => $umlClass->description,
        ];
        if ($umlClass instanceof UmlClass) {
            if ($umlClass->isAbstract) {
                $bmmClass['is_abstract'] = true;
            }
            if ($umlClass->umlGeneralizations->count()) {
                $bmmClass['ancestors'] = array_keys((array)$umlClass->umlGeneralizations);
            }
            /** @var UmlTemplateParameter $umlTemplateParameter */
            foreach ($umlClass->umlTemplateParameters as $umlTemplateParameter) {
                $bmmClass['generic_parameter_defs'][$umlTemplateParameter->name] = self::asBmmGenericParameterDefs($umlTemplateParameter);
            }
            /** @var UmlProperty $umlProperty */
            foreach ($umlClass->umlProperties as $umlProperty) {
                if ($umlProperty->isStatic) {
                    $bmmClass['constants'][$umlProperty->name] = self::asBmmConstant($umlProperty, $umlClass, $collectedUmlClasses);
                } else {
                    $bmmClass['properties'][$umlProperty->name] = self::asBmmProperty($umlProperty, $umlClass, $collectedUmlClasses);
                }
            }
            /** @var UmlOperation $umlOperation */
            foreach ($umlClass->umlOperations as $umlOperation) {
                $bmmClass['functions'][$umlOperation->name] = self::asBmmFunction($umlOperation, $umlClass, $collectedUmlClasses);
            }
            /** @var UmlConstraint $umlConstraint */
            foreach ($umlClass->umlConstraints as $umlConstraint) {
                $bmmClass['invariants'][$umlConstraint->name] = $umlConstraint->rule;
            }
        }
        if ($umlClass instanceof UmlEnumeration) {
            if ($umlClass->name === 'CONSTRAINT_STATUS') {
                $bmmClass['_type'] = 'P_BMM_ENUMERATION_INTEGER';
                $bmmClass['ancestors'] = ['Integer'];
                $bmmClass['item_values'] = array_keys($umlClass->enumerations);
            } else {
                $bmmClass['_type'] = 'P_BMM_ENUMERATION_STRING';
                $bmmClass['ancestors'] = ['String'];
            }
            $bmmClass['item_names'] = array_column($umlClass->enumerations, 'name');
            $bmmClass['item_documentations'] = array_column($umlClass->enumerations, 'description');
        }
        if ($umlClass->name === 'PROPORTION_KIND') {
            $itemNames = $itemValues = $itemDocumentations = [];
            foreach ($umlClass->umlProperties as $umlProperty) {
                $itemNames[] = $umlProperty->name;
                $itemValues[] = (int)$umlProperty->default;
                $itemDocumentations[] = $umlProperty->description;
            }
            $bmmClass = [
                '_type' => 'P_BMM_ENUMERATION_INTEGER',
                'name' => $umlClass->name,
                'documentation' => $umlClass->description,
                'ancestors' => ['Integer'],
                'item_names' => $itemNames,
                'item_values' => $itemValues,
                'item_documentations' => $itemDocumentations,
            ];
        }
        self::log('  Generated [%s] class.', $bmmClass['name']);
        return array_filter($bmmClass);
    }

    /**
     * @param UmlTemplateParameter $UMLTemplateParameter
     * @return array<string, mixed>
     */
    protected static function asBmmGenericParameterDefs(UmlTemplateParameter $UMLTemplateParameter): array
    {
        $bmmGenericParameterDef = [
            'name' => $UMLTemplateParameter->name,
        ];
        if ($UMLTemplateParameter->type->referenceMethod !== 'implicit') {
            $bmmGenericParameterDef['conforms_to_type'] = $UMLTemplateParameter->type->name;
        }
        return $bmmGenericParameterDef;
    }

    protected static function asBmmFunction(UmlOperation $umlOperation, UmlClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmFunction = [
            'name' => $umlOperation->name,
            'description' => $umlOperation->description,
        ];
        /** @var UmlParameter $umlParameter */
        foreach ($umlOperation->umlParameters as $umlParameter) {
            $bmmFunction['parameters'][$umlParameter->name] = self::asBmmParameter($umlParameter, $umlClass, $collectedUmlClasses);
        }
        /** @var UmlConstraint $umlConstraint */
        foreach ($umlOperation->umlConstraints as $umlConstraint) {
            if (str_starts_with(strtolower($umlConstraint->name), 'pre')) {
                $bmmFunction['pre_conditions'][$umlConstraint->name] = $umlConstraint->rule;
            } else {
                $bmmFunction['post_conditions'][$umlConstraint->name] = $umlConstraint->rule;
            }
        }
        $bmmProperty = self::asType($umlOperation->return->name, $umlOperation->maxOccurs, $umlClass, $collectedUmlClasses);
        $bmmFunction['result'] = match ($bmmProperty['_type'] ?? null) {
            'P_BMM_GENERIC_PROPERTY' => array_merge(['_type' => 'P_BMM_GENERIC_TYPE'], $bmmProperty['type_def']),
            'P_BMM_CONTAINER_PROPERTY' => array_merge(['_type' => 'P_BMM_CONTAINER_TYPE'], $bmmProperty['type_def']),
            default => $bmmProperty,
        };
        return array_filter($bmmFunction);
    }

    protected static function asBmmParameter(UmlParameter $umlParameter, UmlClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmParameter = [
            '_type' => null,
            'name' => $umlParameter->name,
            'description' => $umlParameter->description ?? null,
            'is_mandatory' => (bool)$umlParameter->minOccurs,
        ];
        if ($umlParameter->templateParameterId) {
            $bmmParameter['_type'] = 'P_BMM_SINGLE_PROPERTY_OPEN';
            /** @var UmlTemplateParameter $umlTemplateParameter */
            $umlTemplateParameter = $umlClass->umlTemplateParameters->get($umlParameter->templateParameterId);
            $bmmParameter['type'] = $umlTemplateParameter->name;
        } else {
            $bmmParameter = array_merge($bmmParameter, self::asType($umlParameter->type->name, $umlParameter->maxOccurs, $umlClass, $collectedUmlClasses));
        }
        if ($umlParameter->maxOccurs === -1 && ($bmmParameter['_type'] === 'P_BMM_CONTAINER_PROPERTY')) {
            $bmmParameter['cardinality'] = [
                'lower' => $umlParameter->minOccurs,
                'upper_unbounded' => true,
            ];
        }
        // a bit hacky and silly solution, but it works
        if (!empty($bmmParameter['_type'])) {
            $bmmParameter['_type'] = str_replace('_PROPERTY', '_FUNCTION_PARAMETER', $bmmParameter['_type']);
        }
        return array_filter($bmmParameter);
    }

    protected static function asBmmConstant(UmlProperty $umlProperty, UmlClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmConstant = [
            '_type' => null,
            'name' => $umlProperty->name,
            'documentation' => $umlProperty->description ?? null,
            'type' => $umlProperty->type->name,
            'value' => $umlProperty->default,
        ];

        return array_filter($bmmConstant);
    }

    /**
     * @param UmlProperty $umlProperty
     * @param UmlClass $umlClass
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmProperty(UmlProperty $umlProperty, UmlClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmProperty = [
            '_type' => null,
            'name' => $umlProperty->name,
            'documentation' => $umlProperty->description,
            'is_mandatory' => (bool)$umlProperty->minOccurs,
        ];
        if ($umlProperty->templateParameterId) {
            $bmmProperty['_type'] = 'P_BMM_SINGLE_PROPERTY_OPEN';
            /** @var UmlTemplateParameter $umlTemplateParameter */
            $umlTemplateParameter = $umlClass->umlTemplateParameters->get($umlProperty->templateParameterId);
            $bmmProperty['type'] = $umlTemplateParameter->name;
        } else {
            $bmmProperty = array_merge($bmmProperty, self::asType($umlProperty->type->name, $umlProperty->maxOccurs, $umlClass, $collectedUmlClasses));
        }
        if ($umlProperty->maxOccurs === -1 && ($bmmProperty['_type'] === 'P_BMM_CONTAINER_PROPERTY')) {
            $bmmProperty['cardinality'] = [
                'lower' => $umlProperty->minOccurs,
                'upper_unbounded' => true,
            ];
        }
        return array_filter($bmmProperty);
    }

    public static function asType(string $typeName, int $maxOccurs, UmlClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmPropertyType = [];
        if (str_contains($typeName, '<')) {
            if ($maxOccurs === -1) {
                $bmmPropertyType['_type'] = 'P_BMM_CONTAINER_PROPERTY';
                $bmmPropertyType['type_def'] = [
                    'container_type' => 'List',
                    'type_def' => array_merge(['_type' => 'P_BMM_GENERIC_TYPE'], self::asTypeDef($typeName, $collectedUmlClasses)),
                ];
            } else {
                $bmmPropertyType['_type'] = 'P_BMM_GENERIC_PROPERTY';
                $bmmPropertyType['type_def'] = self::asTypeDef($typeName, $collectedUmlClasses);
            }
        } elseif ($maxOccurs === -1) {
            $bmmPropertyType['_type'] = 'P_BMM_CONTAINER_PROPERTY';
            /** @var UmlClass|null $typeDefUmlClass */
            $typeDefUmlClass = $collectedUmlClasses->get($typeName);
            // exceptional situation on data = Octet[]
            if ($typeName === 'Byte') {
                $bmmPropertyType['type_def'] = [
                    'container_type' => 'Array',
                    'type' => 'Octet'
                ];
            } elseif ($typeDefUmlClass && $typeDefUmlClass->isGenericType()) {
                $bmmPropertyType['type_def'] = [
                    'container_type' => 'List',
                    'type_def' => [
                        '_type' => 'P_BMM_GENERIC_TYPE',
                        'root_type' => $typeName,
                        'generic_parameters' => [$umlClass->isGenericType() ? $umlClass->getGenericParameterName() : $umlClass->name],
                    ]
                ];
            } else {
                $bmmPropertyType['type_def'] = [
                    'container_type' => 'List',
                    'type' => $typeName,
                ];
            }
        } else {
//            $bmmPropertyType['_type'] = 'P_BMM_SINGLE_PROPERTY';
            $bmmPropertyType['type'] = $typeName;
        }
        return $bmmPropertyType;
    }

    /**
     * @param string $descriptor
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    public static function asTypeDef(string $descriptor, Collection $collectedUmlClasses): array
    {
        $typeDef = [];
        if (preg_match('/^(\w+)\s*\<(.*)\>$/', $descriptor, $m)) {
            $typeDef['root_type'] = $m[1];
            if (str_contains($m[2], '<')) {
                $descriptorPart = $m[2];
                $typeDef['generic_parameter_defs'] = [];
                /** @var UmlClass $umlTemplateClass */
                $umlTemplateClass = $collectedUmlClasses->get($m[1]);
                $keys = array_keys((array)$umlTemplateClass->umlTemplateParameters);
                while (preg_match('/^(\w+)(\<(?:([^\<\>]*)|(?:(?3)(?2)(?3))*)\>)?(?:,\s*)?/', $descriptorPart, $p)) {
                    $descriptorPart = substr($descriptorPart, strlen($p[0]));
                    $key = current($keys) ?: count($typeDef['generic_parameter_defs']);
                    if (empty($p[2])) {
                        $typeDef['generic_parameter_defs'][$key] = [
                            'type' => $p[1],
                        ];
                    } else {
                        $typeDef['generic_parameter_defs'][$key] = array_merge([
                            '_type' => 'P_BMM_GENERIC_TYPE',
                        ], self::asTypeDef($p[1] . $p[2], $collectedUmlClasses));
                    }
                    next($keys);
                }
            } else {
                $typeDef['generic_parameters'] = explode(', ', $m[2]);
            }
        } else {
            $typeDef['err_type_def'] = $descriptor;
        }
        return $typeDef;
    }
}

<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use JsonException;
use OpenEHR\Tools\CodeGen\Helper\Collection;
use OpenEHR\Tools\CodeGen\Model\UMLClass;
use OpenEHR\Tools\CodeGen\Model\UMLConstraint;
use OpenEHR\Tools\CodeGen\Model\UMLEnumeration;
use OpenEHR\Tools\CodeGen\Model\UMLFile;
use OpenEHR\Tools\CodeGen\Model\UMLOperation;
use OpenEHR\Tools\CodeGen\Model\UMLPackage;
use OpenEHR\Tools\CodeGen\Model\UMLParameter;
use OpenEHR\Tools\CodeGen\Model\UMLProperty;
use OpenEHR\Tools\CodeGen\Model\UMLTemplateParameter;

class BMM extends AbstractWriter
{

    public const string REVISION = '1';
    public const string AUTHOR = 'xmi-code-generator';

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

    public function __construct()
    {
    }

    public function setDir(string $dir): void
    {
        $dir .= DIRECTORY_SEPARATOR . 'BMM';
        parent::setDir($dir);
    }


    /**
     * @throws JsonException
     */
    public function write(): void
    {
        /** @var UMLFile $umlFile */
        foreach ($this->reader->umlFiles as $umlFile) {
            $schema_name = strtolower($umlFile->name);
            self::log('generating to [%s] schema.', $schema_name);
            $schema = [
                'bmm_version' => '2.4',
                'rm_publisher' => 'openehr',
                'schema_name' => $schema_name,
                'rm_release' => $umlFile->getRelease(),
                'schema_revision' => $umlFile->getRelease() . '.' . self::REVISION,
                'schema_lifecycle_state' => 'stable',
                'schema_description' => $umlFile->umlPackage->description,
                'schema_author' => self::AUTHOR,
            ];
            $collectedUmlClasses = new Collection();
            // serializing packages and their classes
            /** @var UMLPackage $umlPackage */
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
            /** @var UMLClass $umlClass */
            foreach ($collectedUmlClasses as $umlClass) {
                if (in_array($umlClass->name, self::PRIMITIVES)) {
                    $schema['primitive_types'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                } else {
                    $schema['class_definitions'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                }
            }
            // saving as file
            $filename = $this->dir . DIRECTORY_SEPARATOR . str_replace('.xmi', '', $umlFile->id) . '.bmm.json';
            $content = json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
            $bytes = file_put_contents($filename, $content);
            self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
        }
    }

    /**
     * @param UMLPackage $umlPackage
     * @param string $namePrefix
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmPackage(UMLPackage $umlPackage, string $namePrefix, Collection $collectedUmlClasses): array
    {
        $bmmPackage = [
            'name' => $namePrefix . $umlPackage->name,
            'packages' => [],
            'classes' => self::collectClassNames($umlPackage, $collectedUmlClasses),
        ];
        /** @var UMLPackage $childUmlPackage */
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
     * @param UMLPackage $umlPackage
     * @param Collection $collectedUmlClasses
     * @return string[]
     */
    protected static function collectClassNames(UMLPackage $umlPackage, Collection $collectedUmlClasses): array
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
     * @param UMLClass|UMLEnumeration $umlClass
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmClass(UMLClass|UMLEnumeration $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmClass = [
            'name' => $umlClass->name,
        ];
        if ($umlClass instanceof UMLClass) {
            if ($umlClass->isAbstract) {
                $bmmClass['is_abstract'] = true;
            }
            if ($umlClass->umlGeneralizations->count()) {
                $bmmClass['ancestors'] = array_keys((array)$umlClass->umlGeneralizations);
            }
            $bmmClass['documentation'] = $umlClass->description;
            /** @var UMLTemplateParameter $umlTemplateParameter */
            foreach ($umlClass->umlTemplateParameters as $umlTemplateParameter) {
                $bmmClass['generic_parameter_defs'][$umlTemplateParameter->name] = self::asBmmGenericParameterDefs($umlTemplateParameter);
            }
            /** @var UMLProperty $umlProperty */
            foreach ($umlClass->umlProperties as $umlProperty) {
                $bmmClass['properties'][$umlProperty->name] = self::asBmmProperty($umlProperty, $umlClass, $collectedUmlClasses);
            }
            /** @var UMLOperation $umlOperation */
            foreach ($umlClass->umlOperations as $umlOperation) {
                $bmmClass['functions'][$umlOperation->name] = self::asBmmFunction($umlOperation, $umlClass, $collectedUmlClasses);
            }
            /** @var UMLConstraint $umlConstraint */
            foreach ($umlClass->umlConstraints as $umlConstraint) {
                $bmmClass['invariants'][$umlConstraint->name] = $umlConstraint->rule;
            }
        }
        if ($umlClass instanceof UMLEnumeration) {
            $bmmClass['_type'] = 'P_BMM_ENUMERATION_STRING';
            $bmmClass['ancestors'] = ['String'];
            $bmmClass['documentation'] = $umlClass->description;
            $bmmClass['item_names'] = $umlClass->enumerations;
        }
        self::log('  Generated [%s] class.', $bmmClass['name']);
        return $bmmClass;
    }

    /**
     * @param UMLTemplateParameter $UMLTemplateParameter
     * @return array<string, mixed>
     */
    protected static function asBmmGenericParameterDefs(UMLTemplateParameter $UMLTemplateParameter): array
    {
        $bmmGenericParameterDef = [
            'name' => $UMLTemplateParameter->name,
        ];
        if ($UMLTemplateParameter->type->referenceMethod !== 'implicit') {
            $bmmGenericParameterDef['conforms_to_type'] = $UMLTemplateParameter->type->name;
        }
        return $bmmGenericParameterDef;
    }

    protected static function asBmmFunction(UMLOperation $umlOperation, UMLClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmFunction = [
            'name' => $umlOperation->name,
            'description' => $umlOperation->description,
        ];
        /** @var UMLParameter $umlParameter */
        foreach ($umlOperation->umlParameters as $umlParameter) {
            $bmmFunction['parameters'][$umlParameter->name] = self::asType($umlParameter->type->name, $umlParameter->maxOccurs, $umlClass, $collectedUmlClasses);
        }
        /** @var UMLConstraint $umlConstraint */
        foreach ($umlOperation->umlConstraints as $umlConstraint) {
            if (str_starts_with(strtolower($umlConstraint->name), 'pre')) {
                $bmmFunction['pre_conditions'][$umlConstraint->name] = $umlConstraint->rule;
            } else {
                $bmmFunction['post_conditions'][$umlConstraint->name] = $umlConstraint->rule;
            }
        }
        $bmmFunction['result'] = self::asType($umlOperation->return->name, $umlOperation->maxOccurs, $umlClass, $collectedUmlClasses);
        return $bmmFunction;
    }

    /**
     * @param UMLProperty $umlProperty
     * @param UMLClass $umlClass
     * @param Collection $collectedUmlClasses
     * @return array<string, mixed>
     */
    protected static function asBmmProperty(UMLProperty $umlProperty, UMLClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmProperty = [
            'name' => $umlProperty->name,
        ];
        if ($umlProperty->templateParameterId) {
            $bmmProperty['_type'] = 'P_BMM_SINGLE_PROPERTY_OPEN';
            /** @var UMLTemplateParameter $umlTemplateParameter */
            $umlTemplateParameter = $umlClass->umlTemplateParameters->get($umlProperty->templateParameterId);
            $bmmProperty['type'] = $umlTemplateParameter->name;
        } else {
            $bmmProperty = array_merge($bmmProperty, self::asType($umlProperty->type->name, $umlProperty->maxOccurs, $umlClass, $collectedUmlClasses));
        }
        if ($umlProperty->maxOccurs === -1 && $bmmProperty['_type'] = 'P_BMM_CONTAINER_PROPERTY') {
            $bmmProperty['cardinality'] = [
                'lower' => $umlProperty->minOccurs,
                'upper_unbounded' => true,
            ];
        }
        if ($umlProperty->minOccurs) {
            $bmmProperty['is_mandatory'] = true;
        }
        $bmmProperty['documentation'] = $umlProperty->description;
        return $bmmProperty;
    }

    public static function asType(string $typeName, int $maxOccurs, UMLClass $umlClass, Collection $collectedUmlClasses): array
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
            /** @var UMLClass|null $typeDefUmlClass */
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
                        'generic_parameters' => $umlClass->isGenericType() ? $umlClass->getGenericParameterName() : $umlClass->name,
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
        if (preg_match('/^(\w+)\<(.*)\>$/', $descriptor, $m)) {
            $typeDef['root_type'] = $m[1];
            if (str_contains($m[2], '<')) {
                $descriptorPart = $m[2];
                $typeDef['generic_parameter_defs'] = [];
                /** @var UMLClass $umlTemplateClass */
                $umlTemplateClass = $collectedUmlClasses->get($m[1]);
                $keys = array_keys((array)$umlTemplateClass->umlTemplateParameters);
                while (preg_match('/^(\w+)(\<(?:([^\<\>]*)|(?:(?3)(?2)(?3))*)\>)?(?:,\s*)?/', $descriptorPart, $p)) {
                    $descriptorPart = substr($descriptorPart, strlen($p[0]));
                    $key = current($keys) ?: count($typeDef['generic_parameter_defs']);
                    if (empty($p[2])) {
                        $typeDef['generic_parameter_defs'][$key] = [
                            '_type' => 'P_BMM_SIMPLE_TYPE',
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

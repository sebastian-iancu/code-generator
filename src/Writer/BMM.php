<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Helper\Collection;
use OpenEHR\Tools\CodeGen\Model\UMLClass;
use OpenEHR\Tools\CodeGen\Model\UMLEnumeration;
use OpenEHR\Tools\CodeGen\Model\UMLFile;
use OpenEHR\Tools\CodeGen\Model\UMLPackage;
use OpenEHR\Tools\CodeGen\Model\UMLProperty;
use OpenEHR\Tools\CodeGen\Model\UMLTemplateParameter;

class BMM extends AbstractWriter
{

    public const REVISION = '1';
    public const AUTHOR = 'codegen';

    public const SKIP_PACKAGES = ['functional', 'primitive_types', 'builtins'];

    public const PRIMITIVES = [
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
        $dir .= DIRECTORY_SEPARATOR . 'InternalModel';
        parent::setDir($dir);
    }


    public function write(): void
    {
        /** @var UMLFile $umlFile */
        /** @var UMLPackage $umlPackage */
        /** @var UMLPackage $childUmlPackage */
        /** @var UMLClass $umlClass */
        foreach ($this->reader->umlFiles as $umlFile) {
            $schema_name = strtolower($umlFile->name);
            self::log('generating to [%s] schema.', $schema_name);
            $schema = [
                'bmm_version' => '2.3',
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
            foreach ($umlFile->umlPackage->getPackages('org::openehr::' . $schema_name . '::*') as $umlPackage) {
                if (in_array($umlPackage->name, self::SKIP_PACKAGES)) {
                    continue;
                }
                $bmmPackage = self::asBmmPackage($umlPackage, "org.openehr.{$schema_name}.", $collectedUmlClasses);
                $schema['packages'][$bmmPackage['name']] = $bmmPackage;
            }
            // serializing primitives and class
            $schema['primitives'] = [];
            $schema['class_definitions'] = [];
            foreach ($collectedUmlClasses as $umlClass) {
                if (in_array($umlClass->name, self::PRIMITIVES)) {
                    $schema['primitives'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                } else {
                    $schema['class_definitions'][$umlClass->name] = self::asBmmClass($umlClass, $collectedUmlClasses);
                }
            }
            // saving as file
            $filename = $this->dir . DIRECTORY_SEPARATOR . str_replace('.xmi', '', $umlFile->id) . '.bmm.json';
            $bytes = file_put_contents($filename, json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
        }
    }

    protected static function asBmmPackage(UMLPackage $umlPackage, string $namePrefix, Collection $collectedUmlClasses): array
    {
        $bmmPackage = [
            'name' => $namePrefix . $umlPackage->name,
            'packages' => [],
            'classes' => self::collectClassNames($umlPackage, $collectedUmlClasses),
        ];
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
            foreach ($umlClass->umlTemplateParameters as $umlTemplateParameter) {
                $bmmClass['generic_parameter_defs'][$umlTemplateParameter->name] = self::asBmmGenericParameterDefs($umlTemplateParameter);
            }
            foreach ($umlClass->umlProperties as $umlProperty) {
                $bmmClass['properties'][$umlProperty->name] = self::asBmmProperty($umlProperty, $umlClass, $collectedUmlClasses);
            }
        }
        if ($umlClass instanceof UMLEnumeration) {
            $bmmClass['p_bmm_type'] = 'P_BMM_ENUMERATION_STRING';
            $bmmClass['ancestors'] = ['String'];
            $bmmClass['documentation'] = $umlClass->description;
            $bmmClass['item_names'] = $umlClass->enumerations;
        }
        self::log('  Generated [%s] class.', $bmmClass['name']);
        return $bmmClass;
    }

    protected static function asBmmProperty(UMLProperty $umlProperty, UMLClass $umlClass, Collection $collectedUmlClasses): array
    {
        $bmmProperty = [
            'name' => $umlProperty->name,
        ];
        if ($umlProperty->templateParameterId) {
            $bmmProperty['p_bmm_type'] = 'P_BMM_SINGLE_PROPERTY_OPEN';
            $bmmProperty['type'] = $umlClass->umlTemplateParameters->get($umlProperty->templateParameterId)->name;
        } elseif (str_contains($umlProperty->type->name, '<')) {
            $bmmProperty['p_bmm_type'] = 'P_BMM_GENERIC_PROPERTY';
            $bmmProperty['type_def'] = self::asTypeDef($umlProperty->type->name, $collectedUmlClasses);
        } elseif ($umlProperty->maxOccurs === -1) {
            $bmmProperty['p_bmm_type'] = 'P_BMM_CONTAINER_PROPERTY';
            $bmmProperty['type_def'] = [
                'container_type' => 'List',
                'type' => $umlProperty->type->name,
            ];
            $bmmProperty['cardinality'] = "|>={$umlProperty->minOccurs}|";
        } else {
            $bmmProperty['p_bmm_type'] = 'P_BMM_SINGLE_PROPERTY';
            $bmmProperty['type'] = $umlProperty->type->name;
        }
        if ($umlProperty->minOccurs) {
            $bmmProperty['is_mandatory'] = true;
        }
        $bmmProperty['documentation'] = $umlProperty->description;
        return $bmmProperty;
    }

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
                            'p_bmm_type' => 'P_BMM_SIMPLE_TYPE',
                            'type' => $p[1],
                        ];
                    } else {
                        $typeDef['generic_parameter_defs'][$key] = array_merge([
                            'p_bmm_type' => 'P_BMM_GENERIC_TYPE',
                        ], self::asTypeDef($p[1].$p[2], $collectedUmlClasses));
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
}

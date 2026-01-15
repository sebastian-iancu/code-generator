<?php /** @noinspection DuplicatedCode */

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmConstant;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationInteger;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationString;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmFunction;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmInterface;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSimpleType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameterOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSinglePropertyOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\Globals;

class AsciidocDefinition
{
    public const array TEXT_REPLACEMENT = [
        '|' => '&#124;',
        '"<="' => '"\<="',
        ' <=' => ' \<=',
        '(<=)' => '(\<=)',
        '".*"' => '".&#42;"',
        '/.*/' => '/.&#42;/',
        "'*'" => "'&#42;'",
        " {" => " \{",
    ];

    public function __construct(private readonly bool $legacyFormat = false)
    {
    }

    public function format(AbstractBmmClass $class, string $prefix): string
    {
        if ($class instanceof BmmInterface) {
            return $this->formatInterface($class, $prefix);
        }
        if ($class instanceof BmmClass) {
            return $this->formatClass($class, $prefix);
        }
        if ($class instanceof BmmEnumerationString || $class instanceof BmmEnumerationInteger) {
            return $this->formatEnum($class, $prefix);
        }
        return "Unsupported *{$class->name}*, context *as-definition*";
    }

    private function formatEnum(BmmEnumerationString|BmmEnumerationInteger $enum, string $prefix): string
    {
        $rows = [];

        if ($this->legacyFormat) {
            $rows[] = '=== ' . $enum->name . ' Enumeration';
            $rows[] = '';
        }

        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Enumeration*';
        $rows[] = '2+^h|*' . $enum->name . '*';

        // Description
        if (!empty($enum->documentation)) {
            $rows[] = '';
            $rows[] = 'h|*Description*';
            $rows[] = '2+a|' . $this->formatText($enum->documentation);
        }

        // Constants
        if ($enum->itemNames) {
            $rows[] = '';
            $rows[] = 'h|*Constants*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var string $name */
            foreach ($enum->itemNames as $i => $name) {
                $rows[] = '';
                $rows[] = 'h|';
                if (in_array('Integer', $enum->ancestors)) {
                    $signature = '*' . $name . '*: `' . $this->formatType('Integer', $prefix) . '{nbsp}={nbsp}' . ($enum->itemValues[$i] ?? '') . '`';
                } else {
                    $signature = $name;
                }
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . $this->formatText($enum->itemDocumentations[$i] ?? '');
            }
        }

        // Functions
        if ($enum->functions->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Functions*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var BmmFunction $function */
            foreach ($enum->functions as $function) {
                $rows[] = '';
                [$card, $signature] = $this->formatFunctionSignature($function, $prefix);
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . $this->formatText($function->documentation ?? '');
            }
        }

        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    private function formatClass(BmmClass $class, string $prefix): string
    {
        $rows = [];

        if ($this->legacyFormat) {
            $rows[] = '=== ' . $class->name . ' Class';
            $rows[] = '';
        }

        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Class*';
        $className = $class->name;
        if ($class->genericParameterDefs->count() > 0) {
            $className = $className . '<' . implode(',', array_keys($class->genericParameterDefs->getArrayCopy())) . '>';
        }
        if ($class->isAbstract) {
            $className = '__' . $className . ' (abstract)__';
        }
        $rows[] = '2+^h|*' . $className . '*';

        // Description
        if (!empty($class->documentation)) {
            $rows[] = '';
            $rows[] = 'h|*Description*';
            $rows[] = '2+a|' . $this->formatText($class->documentation);
        }

        // Inherit
        if (!empty($class->ancestors)) {
            $rows[] = '';
            $rows[] = 'h|*Inherit*';
            $parts = array_map(fn($ancestorName): string => $this->formatType($ancestorName, $prefix), $class->ancestors);
            $rows[] = '2+|`' . implode('`, `', $parts) . '`';
        }

        // Constants
        if ($class->constants->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Constants*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var BmmConstant $constant */
            foreach ($class->constants as $constant) {
                [$card, $signature] = $this->formatConstantSignature($constant, $prefix);
                $rows[] = '';
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $doc = property_exists($constant, 'documentation') ? $this->formatText($constant->documentation ?? '') : '';
                $rows[] = 'a|' . $doc;
            }
        }

        // Attributes
        if ($class->properties->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Attributes*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var AbstractBmmProperty $property */
            foreach ($class->properties as $property) {
                [$card, $signature] = $this->formatPropertySignature($property, $prefix);
                $override = $this->formatPropertyOverride($class, $property);
                $rows[] = '';
                $rows[] = 'h|*' . $card . $override . '*';
                $rows[] = '|' . $signature;
                $doc = property_exists($property, 'documentation') ? $this->formatText($property->documentation ?? '') : '';
                $rows[] = 'a|' . $doc;
            }
        }

        // Functions
        if ($class->functions->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Functions*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var BmmFunction $function */
            foreach ($class->functions as $function) {
                $rows[] = '';
                [$card, $signature, $parameterDocs] = $this->formatFunctionSignature($function, $prefix);
                $override = $this->formatFunctionOverride($class, $function);
                $rows[] = 'h|*' . $card . $override . '*';
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . $this->formatText($function->documentation ?? '');
                if ($parameterDocs) {
                    $rows[] = '';
                    $rows[] = '.Parameters +';
                    $rows[] = '[horizontal]';
                    foreach ($parameterDocs as $parameterName => $doc) {
                        $rows[] = '`_' . $parameterName . '_`:: ' . $this->formatText($doc);
                    }
                }
            }
        }

        // extra line if no attributes or functions are missing
        if (!$class->constants->count() && !$class->properties->count() && !$class->functions->count()) {
            $rows[] = '';
        }

        // Invariants
        if (!empty($class->invariants)) {
            $rows[] = '';
            $rows[] = 'h|*Invariants*';
            $invariants = $class->invariants;
            $last = end($invariants);
            foreach ($class->invariants as $name => $expr) {
                $rows[] = '2+a|__' . $name . '__: `' . $this->formatText($expr) . '`';
                if ($expr !== $last) {
                    $rows[] = '';
                    $rows[] = 'h|';
                }
            }
        }

        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    private function formatInterface(BmmInterface $class, string $prefix): string
    {
        $rows = [];

        if ($this->legacyFormat) {
            $rows[] = '=== ' . $class->name . ' Interface';
            $rows[] = '';
        }

        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Interface*';
        $rows[] = '2+^h|*' . $class->name . '*';

        // Description
        if (!empty($class->documentation)) {
            $rows[] = '';
            $rows[] = 'h|*Description*';
            $rows[] = '2+a|' . $this->formatText($class->documentation);
        }

        // Functions
        if ($class->functions->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Functions*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var BmmFunction $function */
            foreach ($class->functions as $function) {
                $rows[] = '';
                [$card, $signature] = $this->formatFunctionSignature($function, $prefix);
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . $this->formatText($function->documentation ?? '');
            }
        }

        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    /**
     * @return array{0:string,1:string} [cardinality, signature]
     */
    private function formatConstantSignature(BmmConstant $constant, string $prefix): array
    {
        $minOccurs = 1;
        $maxOccurs = 1;
        $type = $this->formatType($constant->type, $prefix);
        $card = $minOccurs . '..' . $maxOccurs;
        $signature = '*' . $constant->name . '*: `' . $type . '{nbsp}={nbsp}' . $constant->value . '`';
        return [$card, $signature];
    }

    /**
     * @return array{0:string,1:string} [cardinality, signature]
     */
    private function formatPropertySignature(AbstractBmmProperty $property, string $prefix): array
    {
        $type = '';
        $minOccurs = (int)($property->isMandatory ?? 0);
        $maxOccurs = 1;
        if ($property instanceof BmmContainerProperty) {
            $type = $this->formatContainerType($property->typeDef, $prefix);
            $maxOccurs = $property->cardinality->upperUnbounded ? '1' : $property->cardinality->upper;
        } elseif ($property instanceof BmmGenericProperty) {
            $type = $this->formatGenericType($property->typeDef, $prefix);
        } elseif ($property instanceof BmmSingleProperty || $property instanceof BmmSinglePropertyOpen) {
            $type = $this->formatType($property->type, $prefix);
        }
        $card = $minOccurs . '..' . $maxOccurs;
        if (isset($property->default)) {
            $default = is_bool($property->default) ? ($property->default ? 'true' : 'false') : $property->default;
            $type .= " +\n" . '{default{nbsp}={nbsp}' . $default . '}';
        }
        $signature = '*' . $property->name . '*: `' . $type . '`';
        return [$card, $signature];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function formatFunctionSignature(BmmFunction $function, string $prefix): array
    {
        $type = '';
        $minOccurs = $function->isNullable ? 0 : 1;
        $maxOccurs = 1;
        if ($function->result instanceof BmmContainerType) {
            $type = $this->formatContainerType($function->result, $prefix);
            $maxOccurs = '1';
        } elseif ($function->result instanceof BmmGenericType) {
            $type = $this->formatGenericType($function->result, $prefix);
        } elseif ($function->result instanceof BmmSimpleType) {
            $type = $this->formatType($function->result->type, $prefix);
        }
        $args = implode(", +\n", array_map(function ($parameter) use ($prefix) {
            if ($parameter instanceof BmmContainerFunctionParameter) {
                return $parameter->name . ': `' . $this->formatContainerType($parameter->typeDef, $prefix) . ($parameter->isNullable ? '' : '[1]') . '`';
            } elseif ($parameter instanceof BmmGenericFunctionParameter) {
                return $parameter->name . ': `' . $this->formatGenericType($parameter->typeDef, $prefix) . ($parameter->isNullable ? '' : '[1]') . '`';
            } elseif ($parameter instanceof BmmSingleFunctionParameter || $parameter instanceof BmmSingleFunctionParameterOpen) {
                return $parameter->name . ': `' . $this->formatType($parameter->type, $prefix) . ($parameter->isNullable ? '' : '[1]') . '`';
            }
            return '';
        }, $function->parameters->getArrayCopy()));
        if ($args) {
            $args = " +\n" . $args . " +\n";
        }
        $aliases = '';
        if ($function->aliases) {
            $aliases = '__alias__ "' . implode('", "', $function->aliases) . '" ';
        }
        $signature = '*' . $function->name . '* ' . $aliases . '(' . $args . '): `' . $type . '`';
        if ($function->preConditions) {
            $signature .= " +\n +\n" . implode(" +\n", array_map(function ($key, $value) {
                    return '__' . $key . '__: `' . $this->formatText($value) . '`';
                }, array_keys($function->preConditions), array_values($function->preConditions)));
        }
        if ($function->postConditions) {
            $signature .= " +\n +\n" . implode(" +\n", array_map(function ($key, $value) {
                    return '__' . $key . '__: `' . $this->formatText($value) . '`';
                }, array_keys($function->postConditions), array_values($function->postConditions)));
        }
        $card = $minOccurs . '..' . $maxOccurs;
        // parameter docs
        $parameterDocs = array_filter(array_map(function ($parameter) {
            return $parameter->documentation ?? false;
        }, $function->parameters->getArrayCopy()));

        return [$card, $signature, $parameterDocs];
    }

    private function formatContainerType(BmmContainerType $type, string $prefix): string
    {
        if ($type->typeDef instanceof BmmGenericType) {
            return $this->formatType($type->containerType, $prefix) . '<' . $this->formatGenericType($type->typeDef, $prefix) . '>';
        } elseif ($type->typeDef instanceof BmmContainerType) {
            return $this->formatType($type->containerType, $prefix) . '<' . $this->formatContainerType($type->typeDef, $prefix) . '>';
        }
        return $this->formatType($type->containerType, $prefix) . '<' . $this->formatType($type->type ?? 'Any', $prefix) . '>';
    }

    private function formatGenericType(BmmGenericType $type, string $prefix): string
    {
        if (!empty($type->genericParameters)) {
            $genericParameters = implode(',', array_map(function ($t) use ($prefix) {
                return $this->formatType($t, $prefix);
            }, $type->genericParameters));
        } elseif (!empty($type->genericParameterDefs)) {
            $genericParameters = implode(',', array_map(function ($t) use ($prefix) {
                if ($t instanceof BmmGenericType) {
                    return $this->formatGenericType($t, $prefix);
                } elseif ($t instanceof BmmSimpleType) {
                    return $this->formatType($t->type, $prefix);
                }
                return '';
            }, $type->genericParameterDefs->getArrayCopy()));
        } else {
            $genericParameters = '';
        }
        return $this->formatType($type->rootType, $prefix) . '<' . $genericParameters . '>';
    }

    public function formatXref(string $packageQname, array &$results = []): string
    {
        $packageQnameSuffix = explode('org.openehr.', $packageQname)[1] ?? '';
        if (!$packageQnameSuffix) {
            return '';
        }

        $m = explode('.', $packageQnameSuffix);
        $component = $m[0] ?? '';
        $module = $m[1] ?? '';
        $page = $m[2] ?? '';
        $results = [strtoupper($component), $module, $page];

        return implode(':', array_filter($results));
    }

    public function formatType(string $type, string $prefix): string
    {
        if (strlen($type) === 1 || in_array(strtolower($type), ['operation', 'void', 'null', 'false', 'true'])) {
            return $type;
        }
        $packageQname = Globals::getClassPackageQName($type) ?? '';
        $m = [];
        $xref = $this->formatXref($packageQname, $m);
        //echo "XX $xref [$type] prefix $prefix\n";
        if ($xref) {
            $class = Globals::getClass($type);
            if ($class instanceof BmmInterface) {
                $classType = 'interface';
            } elseif ($class instanceof BmmEnumerationInteger || $class instanceof BmmEnumerationString) {
                $classType = 'enumeration';
            } else {
                $classType = 'class';
            }

            if ($this->legacyFormat) {
                $prefixXref = $this->formatXref($prefix);
                if ($xref === $prefixXref) {
                    // type is on the same spec page, an example format is '<<_boolean_class,Boolean>>'
                    return '<<_' . strtolower($type) . '_' . $classType . ',' . $type . '>>';
                }
                // an example format is 'link:/releases/BASE/{base_release}/foundation_types.html#_boolean_class[Boolean^]'
                return 'link:/releases/' . $m[0] . '/{' . strtolower($m[0]) . '_release}/' . $m[1] . '.html#_' . strtolower($type) . '_' . $classType . '[' . $type . ']';
            }

            // an example format is 'xref:/releases/BASE/{base_release}/foundation_types.html#_boolean_class[Boolean^]'
            $xref = match ($xref) {
                'BASE:foundation_types' => 'BASE:foundation_types:overview',
                'BASE:foundation_types:time' => 'BASE:foundation_types:time_types',
                'BASE:foundation_types:structures', 'BASE:foundation_types:structure', 'BASE:foundation_types:structure_package' => 'BASE:foundation_types:structure_types',
                'BASE:foundation_types:interval' => 'BASE:foundation_types:interval',
                'BASE:foundation_types:primitive_types' => 'BASE:foundation_types:primitive_types',
                'BASE:foundation_types:functional' => 'BASE:foundation_types:functional',
                'BASE:foundation_types:terminology' => 'BASE:foundation_types:terminology',
                'BASE:resource' => 'BASE:resource:resource_package',
                'AM:aom14:archetype' => 'AM:AOM1.4:archetype_package',
                'AM:aom14:openehr_archetype_profile' => 'AM:AOM1.4:domain_extension',
                'AM:aom2:archetype' => 'AM:AOM2:archetype_package',
                'AM:aom2:constraint_model' => 'AM:AOM2:constraint_model-class_definitions',
                'AM:aom2:definitions' => 'AM:AOM2:model_overview',
                'AM:aom2:terminology' => 'AM:AOM2:terminology_package',
                'AM:aom2:rm_overlay', 'AM:aom2:profile' => 'AM:AOM2:rm_adaptation',
                'AM:aom2:rules' => 'AM:AOM2:rules_package',
                'RM:ehr_extract:sync_extract' => 'RM:ehr_extract:synchronisation_package',
                'RM:composition' => 'RM:ehr:composition_package',
                'RM:composition:content' => 'RM:ehr:content_package',
                'RM:demographic' => 'RM:demographic:demographic_package',
                'RM:data_structures' => 'RM:data_structures:item_structure_package',
                'RM:ehr' => 'RM:ehr:ehr_package',
                default => $xref . '_package',
            };
            return 'xref:' . $xref . '.adoc#_' . strtolower($type) . '_' . $classType . '[' . $type . ']';
        }
        return 'link:/classes/' . $type . '[' . $type . '] ' . $packageQname;
    }

    public function formatText(string $text): string
    {
        $text = preg_replace('/(\{[\w.]*})/', '\\\$1', $text, -1, $count);
        return str_replace(array_keys(self::TEXT_REPLACEMENT), array_values(self::TEXT_REPLACEMENT), trim($text));
    }

    public function formatPropertyOverride(BmmClass $class, AbstractBmmProperty $property): string
    {
        foreach ($class->ancestors as $ancestor) {
            $ancestorClass = Globals::getClass($ancestor);
            if ($ancestorClass instanceof BmmClass) {
                if ($ancestorClass->properties->offsetExists($property->name)) {
                    return " +\n(" . ($ancestorClass->isAbstract ? 'effected' : 'redefined') . ')';
                }
                $parent = $this->formatPropertyOverride($ancestorClass, $property);
                if ($parent) {
                    return $parent;
                }
            }
        }
        return '';
    }

    public function formatFunctionOverride(BmmClass $class, BmmFunction $function): string
    {
        if ($function->isAbstract) {
            return " +\n(abstract)";
        }
        foreach ($class->ancestors as $ancestor) {
            $ancestorClass = Globals::getClass($ancestor);
            if ($ancestorClass instanceof BmmClass) {
                /** @var BmmFunction $ancestorFunction */
                $ancestorFunction = $ancestorClass->functions->get($function->name);
                if ($ancestorFunction) {
                    return " +\n(" . ($ancestorFunction->isAbstract ? 'effected' : 'redefined') . ')';
                }
                $parent = $this->formatFunctionOverride($ancestorClass, $function);
                if ($parent) {
                    return $parent;
                }
            }
        }
        return '';
    }
}

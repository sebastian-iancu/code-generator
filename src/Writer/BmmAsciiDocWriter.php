<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmContainerType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationInteger;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmEnumerationString;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmFunction;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmGenericType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSimpleType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameterOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSinglePropertyOpen;

/**
 * Writer class for converting BMM objects to AsciiDoc tables
 */
class BmmAsciiDocWriter extends AbstractWriter
{
    // Target directory for AsciiDoc outputs
    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'Adoc' . DIRECTORY_SEPARATOR;

    public function write(): void
    {
        $this->assureOutputDir();
        /** @var BmmSchema $schema */
        foreach ($this->reader->files as $schema) {
            // Build prefix e.g. org.openehr.rm
            /** @var BmmPackage $package */
            foreach ($schema->packages as $package) {
                $this->writePackage($schema, $package, '');
                /** @var BmmPackage $subPackage */
                foreach ($package->packages as $subPackage) {
                    $this->writePackage($schema, $subPackage, $package->name . '.');
                    // one level deeper for sub-packages (consistent with other writers)
                    /** @var BmmPackage $subSubPackage */
                    foreach ($subPackage->packages as $subSubPackage) {
                        $this->writePackage($schema, $subSubPackage, $package->name . '.' . $subPackage->name . '.');
                    }
                }
            }
        }
    }

    private function writePackage(BmmSchema $schema, BmmPackage $package, string $namePrefix): void
    {
        $prefix = 'org.openehr.' . strtolower($schema->schemaName) . '.';
        $prefix .= explode('.', str_replace($prefix, '', $namePrefix . $package->name))[0] . '.';
        $packageDir = self::DIR . $schema->getSchemaId() . '/';
        $this->assureOutputDir($packageDir);
        foreach ($package->classes as $className) {
            /** @var AbstractBmmClass $class */
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            if (!$class) {
                self::log('Class %s not found in schema', $className);
                continue;
            }
            $filename = $packageDir . $prefix . strtolower($className) . '.adoc';
            self::log('Writing to [%s] filename.', $filename);
            if ($class instanceof BmmEnumerationString || $class instanceof BmmEnumerationInteger) {
                $content = $this->enumToAsciiDoc($class);
            } elseif ($class instanceof BmmClass) {
                $content = $this->classToAsciiDoc($class);
            } else {
                // Fallback minimal
                $content = '=== ' . strtoupper($class->name) . " Class\n\n|===\n|Unsupported type\n|===\n";
            }
            $bytes = file_put_contents($filename, $content);
            self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
        }
    }

    private function enumToAsciiDoc(BmmEnumerationString|BmmEnumerationInteger $enum): string
    {
        $rows = [];
        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Enumeration*';
        $rows[] = '2+^h|*' . strtoupper($enum->name) . '*';
        $rows[] = 'h|*Items*';
        $rows[] = '2+a|' . implode(", ", array_map(function ($i, $name) use ($enum) {
                $val = isset($enum->itemValues[$i]) ? ' = ' . $enum->itemValues[$i] : '';
                return '`' . $name . $val . '`';
            }, array_keys($enum->itemNames), $enum->itemNames));
        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    private function classToAsciiDoc(BmmClass $class): string
    {
        $rows = [];
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
            $rows[] = '2+a|' . trim($class->documentation);
        }

        // Inherit
        if (!empty($class->ancestors)) {
            $rows[] = '';
            $rows[] = 'h|*Inherit*';
            $parts = array_map(fn($ancestorName): string => $this->formatType($ancestorName), $class->ancestors);
            $rows[] = '2+|`' . implode(', ', $parts) . '`';
        }

        // Attributes header
        if ($class->properties->count() > 0) {
            $rows[] = '';
            $rows[] = 'h|*Attributes*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var AbstractBmmProperty $property */
            foreach ($class->properties as $property) {
                [$card, $signature] = $this->formatPropertySignature($property);
                $rows[] = '';
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $doc = property_exists($property, 'documentation') ? rtrim($property->documentation ?? '') : '';
                $rows[] = 'a|' . $doc;
            }
        }

        // Functions header
        if ($class->functions->count() > 0) {
            $rows[] = 'h|*Functions*';
            $rows[] = '^h|*Signature*';
            $rows[] = '^h|*Meaning*';

            /** @var BmmFunction $function */
            foreach ($class->functions as $function) {
                $rows[] = '';
                [$card, $signature] = $this->formatFunctionSignature($function);
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . rtrim($function->documentation ?? '');
            }
        }

        // Invariants
        if (!empty($class->invariants)) {
            $rows[] = '';
            $rows[] = 'h|*Invariants*';
            $invariants = $class->invariants;
            $last = end($invariants);
            foreach ($class->invariants as $name => $expr) {
                $rows[] = '2+a|__' . $name . '__: `' . $expr . '`';
                if ($expr !== $last) {
                    $rows[] = '';
                    $rows[] = 'h|';
                }
            }
        }

        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    /**
     * @return array{0:string,1:string} [cardinality, signature]
     */
    private function formatPropertySignature(AbstractBmmProperty $property): array
    {
        $type = '';
        $minOccurs = (int)($property->isMandatory ?? 0);
        $maxOccurs = 1;
        if ($property instanceof BmmContainerProperty) {
            $type = $this->formatContainerType($property->typeDef);
            $maxOccurs = $property->cardinality->upperUnbounded ? '*' : $property->cardinality->upper;
        } elseif ($property instanceof BmmGenericProperty) {
            $type = $this->formatGenericType($property->typeDef);
        } elseif ($property instanceof BmmSingleProperty || $property instanceof BmmSinglePropertyOpen) {
            $type = $this->formatType($property->type);
        }
        $card = $minOccurs . '..' . $maxOccurs;
        $signature = '*' . $property->name . '*: `' . $type . '`';
        return [$card, $signature];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function formatFunctionSignature(BmmFunction $function): array
    {
        $type = '';
        $minOccurs = 1;//(int)($function->isNullable ?? 0);
        $maxOccurs = 1;
        if ($function->result instanceof BmmContainerType) {
            $type = $this->formatContainerType($function->result);
            $maxOccurs = '*';
        } elseif ($function->result instanceof BmmGenericType) {
            $type = $this->formatGenericType($function->result);
        } elseif ($function->result instanceof BmmSimpleType) {
            $type = $this->formatType($function->result->type);
        }
        $args = implode(", +\n", array_map(function ($parameter) {
            if ($parameter instanceof BmmContainerFunctionParameter) {
                return $parameter->name . ': `' . $this->formatContainerType($parameter->typeDef) . ($parameter->isNullable ? '' : '[1]') . '`';
            } elseif ($parameter instanceof BmmGenericFunctionParameter) {
                return $parameter->name . ': `' . $this->formatGenericType($parameter->typeDef) . ($parameter->isNullable ? '' : '[1]') . '`';
            } elseif ($parameter instanceof BmmSingleFunctionParameter || $parameter instanceof BmmSingleFunctionParameterOpen) {
                return $parameter->name . ': `' . $this->formatType($parameter->type) . ($parameter->isNullable ? '' : '[1]') . '`';
            }
            return '';
        }, $function->parameters->getArrayCopy()));
        if ($args) {
            $args = " +\n" . $args . " +\n";
        }
        $signature = '*' . $function->name . '* (' . $args . '): `' . $type . '`';
        if ($function->preConditions) {
            $signature .= " +\n +\n" . implode(" +\n", array_map(function ($key, $value) {
                    return '__' . $key . '__: `' . $value . '`';
                }, array_keys($function->preConditions), array_values($function->preConditions)));
        }
        if ($function->postConditions) {
            $signature .= " +\n +\n" . implode(" +\n", array_map(function ($key, $value) {
                    return '__' . $key . '__: `' . $value . '`';
                }, array_keys($function->postConditions), array_values($function->postConditions)));
        }
        $card = $minOccurs . '..' . $maxOccurs;
        if ($function->isAbstract) {
            $card .= " +\n(abstract)";
        }

        return [$card, $signature];
    }

    private function formatContainerType(BmmContainerType $type): string
    {
        if ($type->typeDef instanceof BmmGenericType) {
            return $this->formatType($type->containerType) . '<' . $this->formatGenericType($type->typeDef) . '>';
        } elseif ($type->typeDef instanceof BmmContainerType) {
            return $this->formatType($type->containerType) . '<' . $this->formatContainerType($type->typeDef) . '>';
        }
        return $this->formatType($type->containerType) . '<' . $this->formatType($type->type ?? 'Any') . '>';
    }

    private function formatGenericType(BmmGenericType $type): string
    {
        if (!empty($type->genericParameters)) {
            $genericParameters = implode(',', $type->genericParameters);
        } elseif (!empty($type->genericParameterDefs)) {
            $genericParameters = implode(',', array_map(function ($t) {
                if ($t instanceof BmmGenericType) {
                    return $this->formatGenericType($t);
                } elseif ($t instanceof BmmSimpleType) {
                    return $this->formatType($t->type);
                }
                return '';
            }, $type->genericParameterDefs->getArrayCopy()));
        } else {
            $genericParameters = '';
        }
        return $this->formatType($type->rootType) . '<' . $genericParameters . '>';
    }

    public function formatType(string $type): string
    {
        if (strlen($type) === 1 || $type === 'Operation') {
            return $type;
        }
        return 'link:/classes/' . $type . '[' . $type . ']';
    }
}

<?php /** @noinspection DuplicatedCode */

namespace OpenEHR\Tools\CodeGen\Writer;

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
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSimpleType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameterOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSinglePropertyOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\Globals;

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
        $prefix .= explode('.', str_replace($prefix, '', $namePrefix . $package->name))[0];
        $packageClassesDir = self::DIR . $schema->getSchemaId() . '/definitions/';
        $this->assureOutputDir($packageClassesDir);
        $packageTabsDir = self::DIR . $schema->getSchemaId() . '/classes/';
        $this->assureOutputDir($packageTabsDir);
        $packageBmmJsonDir = self::DIR . $schema->getSchemaId() . '/BMMs/';
        $this->assureOutputDir($packageBmmJsonDir);
        foreach ($package->classes as $className) {
            /** @var AbstractBmmClass $class */
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            if (!$class) {
                self::log('Class %s not found in schema', $className);
                continue;
            }
            $filename = $prefix . '.' . strtolower($className) . '.adoc';
            self::log('Writing %s ...', $filename);
            // writing class definition file
            if ($class instanceof BmmEnumerationString || $class instanceof BmmEnumerationInteger) {
                $content = $this->formatEnumAsDefinition($class, $prefix);
            } elseif ($class instanceof BmmClass) {
                $content = $this->formatClassAsDefinition($class, $prefix);
            } elseif ($class instanceof BmmInterface) {
                $content = $this->formatInterfaceAsDefinition($class, $prefix);
            } else {
                $content = $this->formatAsUnsupported($class->name, 'as-definition');
            }
            $this->writeFile($packageClassesDir . $filename, $content);
            // writing class tabs file
            $content = $this->formatAsTabs($class, $filename);
            $this->writeFile($packageTabsDir . $filename, $content);
            // writing BMM json file
            $content = $this->formatAsBmmJson($class);
            $this->writeFile($packageBmmJsonDir . $filename, $content);
        }
    }

    private function formatAsUnsupported(string $className, string $errorContext): string
    {
        return "Unsupported *{$className}*, context *{$errorContext}*";
    }

    private function writeFile(string $filename, string $content): void
    {
        $bytes = file_put_contents($filename, $content);
        self::log('  Wrote %s bytes to %s tabs file.', $bytes, $filename);
    }

    private function formatAsTabs(AbstractBmmClass $class, string $classFilename): string
    {
        $rows = [];
        $rows[] = '=== ' . $class->getName() . ' Class';
        $rows[] = '';
        $rows[] = '[tabs]';
        $rows[] = '====';
        $rows[] = 'Definition::';
        $rows[] = '+';
        $rows[] = 'include::../definitions/' . $classFilename . '[]';
        $rows[] = '';
        $rows[] = 'BMM::';
        $rows[] = '+';
        $rows[] = 'include::../BMMs/' . $classFilename . '[]';
        $rows[] = '';
        $rows[] = 'UML::';
        $rows[] = '+';
        $rows[] = 'include::../plantUML/classes/' . $classFilename . '[]';
        $rows[] = '';
        $rows[] = '====';
        return implode(PHP_EOL, $rows);
    }

    private function formatAsBmmJson(AbstractBmmClass $class): string
    {
        $data = $class->jsonSerialize();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return $this->formatAsUnsupported($class->getName(), 'Failed to encode JSON for class - ' . json_last_error_msg());
        }
        $rows = [];
        $rows[] = '[source,json]';
        $rows[] = '--------';
        $rows[] = $json;
        $rows[] = '--------';
        return implode(PHP_EOL, $rows);
    }

    private function formatEnumAsDefinition(BmmEnumerationString|BmmEnumerationInteger $enum, string $prefix): string
    {
        $rows = [];

        // todo remove this once all classes are converted to BmmClass
        $rows[] = '=== '. $enum->name.' Enumeration';
        $rows[] = '';
        // end-remove

        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Enumeration*';
        $rows[] = '2+^h|*' . $enum->name . '*';

        // Description
        if (!empty($enum->documentation)) {
            $rows[] = '';
            $rows[] = 'h|*Description*';
            $rows[] = '2+a|' . trim($enum->documentation);
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
                    $signature = '*' . $name . '*: `' . $this->formatType('Integer', $prefix) . '{nbsp}={nbsp}'.($enum->itemValues[$i]??'').'`';
                } else {
                    $signature = $name;
                }
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . $enum->itemDocumentations[$i] ?? '';
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
                $rows[] = 'a|' . rtrim($function->documentation ?? '');
            }
        }

        $rows[] = '|===';
        return implode("\n", $rows) . "\n";
    }

    private function formatClassAsDefinition(BmmClass $class, string $prefix): string
    {
        $rows = [];

        // todo remove this once all classes are converted to BmmClass
        $rows[] = '=== '. $class->name.' Class';
        $rows[] = '';
        // end-remove

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
            $parts = array_map(fn($ancestorName): string => $this->formatType($ancestorName, $prefix), $class->ancestors);
            $rows[] = '2+|`' . implode(', ', $parts) . '`';
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
                $doc = property_exists($constant, 'documentation') ? rtrim($constant->documentation ?? '') : '';
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
                $rows[] = '';
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $doc = property_exists($property, 'documentation') ? rtrim($property->documentation ?? '') : '';
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
                [$card, $signature] = $this->formatFunctionSignature($function, $prefix);
                $rows[] = 'h|*' . $card . '*';
                $rows[] = '|' . $signature;
                $rows[] = 'a|' . rtrim($function->documentation ?? '');
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

    private function formatInterfaceAsDefinition(BmmInterface $class, string $prefix): string
    {
        $rows = [];

        // todo remove this once all classes are converted to BmmClass
        $rows[] = '=== '. $class->name.' Interface';
        $rows[] = '';
        // end-remove

        $rows[] = '[cols="^1,3,5"]';
        $rows[] = '|===';
        $rows[] = 'h|*Interface*';
        $rows[] = '2+^h|*' . $class->name . '*';

        // Description
        if (!empty($class->documentation)) {
            $rows[] = '';
            $rows[] = 'h|*Description*';
            $rows[] = '2+a|' . trim($class->documentation);
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
                $rows[] = 'a|' . rtrim($function->documentation ?? '');
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
            $maxOccurs = $property->cardinality->upperUnbounded ? '*' : $property->cardinality->upper;
        } elseif ($property instanceof BmmGenericProperty) {
            $type = $this->formatGenericType($property->typeDef, $prefix);
        } elseif ($property instanceof BmmSingleProperty || $property instanceof BmmSinglePropertyOpen) {
            $type = $this->formatType($property->type, $prefix);
        }
        $card = $minOccurs . '..' . $maxOccurs;
        $signature = '*' . $property->name . '*: `' . $type . '`';
        return [$card, $signature];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function formatFunctionSignature(BmmFunction $function, string $prefix): array
    {
        $type = '';
        $minOccurs = 1;//(int)($function->isNullable ?? 0);
        $maxOccurs = 1;
        if ($function->result instanceof BmmContainerType) {
            $type = $this->formatContainerType($function->result, $prefix);
            $maxOccurs = '*';
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
        $signature = '*' . $function->name . '* '.$aliases.'(' . $args . '): `' . $type . '`';
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
            $genericParameters = implode(',', $type->genericParameters);
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

    public function formatType(string $type, string $prefix): string
    {
        if (strlen($type) === 1 || $type === 'Operation') {
            return $type;
        }
        $packageQname = Globals::getClassPackageQName($type);
        // todo adapt this once all pages are in Antora
        if ($packageQname && preg_match('/^[\w.]+\.org\.openehr\.(\w+)\.(\w+)(?:\.\w+){0,2}$/', $packageQname, $m) === 1) {
            if (preg_match('/^org\.openehr\.(\w+)\.(\w+)(?:\.\w+){0,2}$/', $prefix, $p) && $m[2] === $p[2]) {
                // tpe is on the same spec page, an example format is '<<_boolean_class,Boolean>>'
                return '<<_' . strtolower($type)  . '_class,' . $type . '>>';
            }
            // an example format is 'link:/releases/BASE/{base_release}/foundation_types.html#_boolean_class[Boolean^]'
            return 'link:/releases/' . strtoupper($m[1]) . '/{' . $m[1] . '_release}/' . $m[2] . '.html#_' . strtolower($type) . '_class[' . $type . '^]';
        }
        return 'link:/classes/' . $type . '[' . $type . ']';
    }
}

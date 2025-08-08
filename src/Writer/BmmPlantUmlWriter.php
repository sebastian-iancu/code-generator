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
use RuntimeException;

/**
 * Writer class for converting BMM objects to PlantUML format
 */
class BmmPlantUmlWriter extends AbstractWriter
{
    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'PlantUML' . DIRECTORY_SEPARATOR;

    /**
     * Write the BMM schema to a PlantUML file
     *
     * @return void
     * @throws RuntimeException If the schema is not set
     */
    public function write(): void
    {
        $this->assureOutputDir();
        /** @var BmmSchema $schema */
        foreach ($this->reader->files as $schema) {
            /** @var BmmPackage $package */
            foreach ($schema->packages as $package) {
                if (count($package->classes)) {
                    $this->createPackageDiagram($package, $schema);
                }
                /** @var BmmPackage $subPackage */
                foreach ($package->packages as $subPackage) {
                    if (count($subPackage->classes)) {
                        $this->createPackageDiagram($subPackage, $schema, $package->name . '.');
                    }
                }
            }
        }
    }

    /**
     * Wraps the diagram content with PlantUML start/end tags and adds title
     *
     * @param string $diagramContent The main PlantUML diagram content
     * @param string $diagramFor Identifier for what the diagram represents
     * @param string $title The title to display in the diagram
     * @return string The complete PlantUML diagram with wrapping tags
     */
    private function wrapDiagram(string $diagramContent, string $diagramFor, string $title): string
    {
        return "@startuml\n"
            . "' PlantUML diagram for " . $diagramFor . "\n"
            . "title " . $title . "\n\n"
            . $diagramContent . "\n"
            . "@enduml\n";
    }

    /**
     * Creates a PlantUML package diagram file for the given package
     *
     * @param BmmPackage $package The BMM package to create diagram for
     * @param BmmSchema $schema The parent BMM schema containing class definitions
     * @param string $namePrefix Optional prefix for the package name (default: '')
     * @return void
     */
    private function createPackageDiagram(BmmPackage $package, BmmSchema $schema, string $namePrefix = ''): void
    {
        $name = $namePrefix . $package->name;
        $filename = self::DIR . 'package-' . $name . '.puml';
        self::log('Writing to [%s] filename.', $filename);
        $plantUml = $this->wrapDiagram(
            diagramContent: $this->fromBmmPackage($package, $schema, $namePrefix),
            diagramFor: $name,
            title: $name . ' package',
        );
        $bytes = file_put_contents($filename, $plantUml);
        self::log('  Wrote %s bytes to %s file.', $bytes, $filename);
    }

    /**
     * Creates a PlantUML class diagram file for the given class
     *
     * @param string $classOutput The PlantUML class definition content
     * @param AbstractBmmClass $class The BMM class to create diagram for
     * @param BmmPackage $package The package containing the class
     * @param BmmSchema $schema The parent BMM schema containing class definitions
     * @param string $namePrefix Optional prefix for the package name (default: '')
     * @return void
     */
    private function createClassDiagram(string $classOutput, AbstractBmmClass $class, BmmPackage $package, BmmSchema $schema, string $namePrefix = ''): void
    {
        if ($class instanceof BmmClass) {
            $classOutput .= $this->generateAncestors($class, $schema);
        }
        $name = $namePrefix . $package->name;
        $filename = self::DIR . 'class-' . $name . '-' . $class->name . '.puml';
        self::log('      Writing to [%s] filename.', $filename);
        $plantUml = $this->wrapDiagram(
            diagramContent: $classOutput,
            diagramFor: $name . '-' . $class->name,
            title: $class->name . ' class',
        );
        $bytes = file_put_contents($filename, $plantUml);
        self::log('        Wrote %s bytes to %s file.', $bytes, $filename);
    }

    /**
     * Converts a BMM package to PlantUML format, processing all classes and generating relationships
     *
     * @param BmmPackage $package The BMM package to convert
     * @param BmmSchema $schema The parent BMM schema containing class definitions
     * @param string $namePrefix Optional prefix for the package name (default: '')
     * @return string The PlantUML representation of the package
     */
    private function fromBmmPackage(BmmPackage $package, BmmSchema $schema, string $namePrefix = ''): string
    {
        $name = $namePrefix . $package->name;
        self::log('    package %s', $name);
        $classesOutput = [];
        $relationshipOutput = [];
        foreach ($package->classes as $className) {
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            self::log('      class %s', $className);
            if ($class instanceof BmmEnumerationString || $class instanceof BmmEnumerationInteger) {
                $classOutput = $this->fromBmmEnumeration($class);
            } elseif ($class instanceof BmmClass) {
                $classOutput = $this->fromBmmClass($class);
            } else {
                self::log("Error: Class $className not found in schema");
                continue;
            }

            // collecting output for package diagram
            $classesOutput[] = $classOutput;
            if ($class instanceof BmmClass) {
                $relationshipOutput[] = $this->generateRelationships($class);
            }

            // create also class diagram
            $this->createClassDiagram($classOutput, $class, $package, $schema, $namePrefix);
        }
        return implode('', $classesOutput) . implode('', $relationshipOutput);
    }

    /**
     * Generate PlantUML class definition
     *
     * @param BmmClass $class The BMM class to convert
     * @return string The PlantUML class definition
     */
    private function fromBmmClass(BmmClass $class): string
    {
        $output = '';

        if ($class->isAbstract) {
            $output .= "abstract ";
        }
        $output .= "class " . $class->name . " ";
        if ($class->genericParameterDefs->count() > 0) {
            $genericParameterDefs = array_map(fn($item) => $item->getName(), $class->genericParameterDefs->getArrayCopy());
            $output .= '<<' . implode(', ', $genericParameterDefs) . '>> ';
        }
        $output .= "{\n";

        /** @var AbstractBmmProperty $property */
        foreach ($class->properties as $property) {
            $output .= "  " . $this->formatProperty($property) . "\n";
        }

        /** @var BmmFunction $function */
        foreach ($class->functions as $function) {
            $output .= "  " . $this->formatFunction($function) . "\n";
        }

        $output .= "}\n\n";

        return $output;
    }

    /**
     * Generate PlantUML enumeration definition
     *
     * @param BmmEnumerationString|BmmEnumerationInteger $enum The BMM enumeration to convert
     * @return string The PlantUML enumeration definition
     */
    private function fromBmmEnumeration(BmmEnumerationString|BmmEnumerationInteger $enum): string
    {
        $output = "enum " . $enum->name . " {\n";
        foreach ($enum->itemNames as $i => $itemName) {
            $itemValue = isset($enum->itemValues[$i]) ? "= {$enum->itemValues[$i]} " : '';
            $output .= "  $itemName $itemValue\n";
        }
        $output .= "}\n\n";
        return $output;
    }

    /**
     * Format a BMM class property for PlantUML
     *
     * @param AbstractBmmProperty $property The property to format
     * @return string The formatted property
     */
    private function formatProperty(AbstractBmmProperty $property): string
    {
        $type = '';
        $minOccurs = (int)($property->isMandatory ?? 0);
        $maxOccurs = 1;
        if ($property instanceof BmmContainerProperty) {
            $type = $this->formatContainerParameterType($property->typeDef);
            $maxOccurs = $property->cardinality->upperUnbounded ? '*' : $property->cardinality->upper;
        } elseif ($property instanceof BmmGenericProperty) {
            $type = $this->formatGenericParameterType($property->typeDef);
        } elseif ($property instanceof BmmSingleProperty || $property instanceof BmmSinglePropertyOpen) {
            $type = $property->type;
        }

        return "+ " . $property->name . " : " . $type . " [" . $minOccurs . ".." . $maxOccurs . "]";
    }

    /**
     * Formats a BMM function for PlantUML representation
     *
     * @param BmmFunction $function The BMM function to format
     * @return string The formatted function signature in PlantUML syntax
     */
    private function formatFunction(BmmFunction $function): string
    {
        $abstract = $function->isAbstract ? '{abstract} ' : '';
        $type = '';
        $minOccurs = (int)($function->isNullable ?? 0);
        $maxOccurs = 1;
        if ($function->result instanceof BmmContainerType) {
            $type = $this->formatContainerParameterType($function->result);
            $maxOccurs = '*';
        } elseif ($function->result instanceof BmmGenericType) {
            $type = $this->formatGenericParameterType($function->result);
        } elseif ($function->result instanceof BmmSimpleType) {
            $type = $function->result->type;
        }
        $arguments = implode(', ', array_map(function ($parameter) {
            if ($parameter instanceof BmmContainerFunctionParameter) {
                return $this->formatContainerParameterType($parameter->typeDef) . ' ' . $parameter->name;
            } elseif ($parameter instanceof BmmGenericFunctionParameter) {
                return $this->formatGenericParameterType($parameter->typeDef) . ' ' . $parameter->name;
            } elseif ($parameter instanceof BmmSingleFunctionParameter || $parameter instanceof BmmSingleFunctionParameterOpen) {
                return $parameter->type . ' ' . $parameter->name;
            }
            return '';
        }, $function->parameters->getArrayCopy()));

        return "+ " . $abstract . $function->name . "(" . $arguments . ") : " . $type . " [" . $minOccurs . ".." . $maxOccurs . "]";
    }

    /**
     * Formats a BMM container type for PlantUML representation
     *
     * @param BmmContainerType $type The BMM container type to format
     * @return string The formatted container type with generic parameters
     */
    private function formatContainerParameterType(BmmContainerType $type): string
    {
        if ($type->typeDef instanceof BmmGenericType) {
            return $type->containerType . '<' . $this->formatGenericParameterType($type->typeDef) . '>';
        } elseif ($type->typeDef instanceof BmmContainerType) {
            return $type->containerType . '<' . $this->formatContainerParameterType($type->typeDef) . '>';
        }
        return $type->containerType . '<' . $type->type . '>';
    }

    /**
     * Formats a BMM generic type for PlantUML representation
     *
     * @param BmmGenericType $type The BMM generic type to format
     * @return string The formatted generic type with parameters
     */
    private function formatGenericParameterType(BmmGenericType $type): string
    {
        if (!empty($type->genericParameters)) {
            $genericParameters = implode(',', $type->genericParameters);
        } elseif (!empty($type->genericParameterDefs)) {
            $genericParameters = implode(',', array_map(function ($t) {
                if ($t instanceof BmmGenericType) {
                    return $this->formatGenericParameterType($t);
                } elseif ($t instanceof BmmSimpleType) {
                    return $t->type;
                }
                return '';
            }, $type->genericParameterDefs->getArrayCopy()));
        } else {
            $genericParameters = '';
        }
        return $type->rootType . '<' . $genericParameters . '>';
    }

    /**
     * Determines if a class name should be hidden from the diagram
     *
     * @param string $className The class name to check
     * @return bool True if the class should be hidden, false otherwise
     */
    private function isHidden(string $className): bool
    {
        return in_array(strtolower($className), ['openehr_definitions', 'any']);
    }

    /**
     * Generates PlantUML representation for all ancestor classes and their inheritance relationships
     *
     * @param BmmClass $class The BMM class to generate ancestors for
     * @param BmmSchema $schema The parent BMM schema containing class definitions
     * @return string The PlantUML representation of ancestors and inheritance relationships
     */
    private function generateAncestors(BmmClass $class, BmmSchema $schema): string
    {
        $output = '';
        foreach ($class->ancestors as $ancestorName) {
            if ($this->isHidden($ancestorName)) {
                continue;
            }
            $ancestor = $schema->classDefinitions->get($ancestorName) ?? $schema->primitiveTypes->get($ancestorName);
            if ($ancestor instanceof BmmClass) {
                $output .= $this->fromBmmClass($ancestor);
                $output .= $this->generateAncestors($ancestor, $schema);
            }
            $output .= $ancestorName . " <|-- " . $class->name . "\n\n";
        }
        return $output;
    }

    /**
     * Generate PlantUML relationships for a class
     *
     * @param BmmClass $class The BMM class to generate relationships for
     * @return string The PlantUML relationships
     */
    private function generateRelationships(BmmClass $class): string
    {
        $output = '';

        // Inheritance
        if ($class->ancestors) {
            foreach ($class->ancestors as $ancestorName) {
                if ($this->isHidden($ancestorName)) {
                    continue;
                }
                $output .= $ancestorName . " <|-- " . $class->name . "\n";
            }
        }

//        // Associations (properties that reference other classes)
//        foreach ($class->properties as $property) {
//            if ($property instanceof BmmContainerProperty) {
//                $output .= $class->name . " o-- \"*\" " . $property->typeDef->type . " : " . $property->name . "\n";
//            } elseif ($property instanceof BmmGenericProperty) {
//                $output .= $class->name . " --> " . $property->typeDef->rootType . " : " . $property->name . "\n";
//            } elseif ($property instanceof BmmSingleProperty || $property instanceof BmmSinglePropertyOpen) {
//                $output .= $class->name . " --> " . $property->type . " : " . $property->name . "\n";
//            }
//        }

        return $output;
    }
}

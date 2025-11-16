<?php

namespace OpenEHR\Tools\CodeGen\Writer\Formatter;

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
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmInterface;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSimpleType;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameter;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleFunctionParameterOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSingleProperty;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSinglePropertyOpen;
use OpenEHR\Tools\CodeGen\Model\Bmm\Globals;

class PlantUml
{
    public function format(AbstractBmmClass|BmmPackage $bmmItem, string $prefix): string
    {
        $content = "Unsupported *{$bmmItem->name}*, context *format-plantUML*";
        if ($bmmItem instanceof BmmInterface) {
            $content = $this->formatInterface($bmmItem);
            $purpose = $prefix . '.' . $bmmItem->name;
            $title = $bmmItem->name . ' Interface';
        }
        if ($bmmItem instanceof BmmClass) {
            $content = $this->formatClass($bmmItem) . $this->formatClassAncestors($bmmItem);
            $purpose = $prefix . '.' . $bmmItem->name;
            $title = $bmmItem->name . ' Class';
        }
        if ($bmmItem instanceof BmmEnumerationString || $bmmItem instanceof BmmEnumerationInteger) {
            $content = $this->formatEnum($bmmItem);
            $purpose = $prefix . '.' . $bmmItem->name;
            $title = $bmmItem->name . ' Enumeration';
        }
        if ($bmmItem instanceof BmmPackage && count($bmmItem->classes)) {
            $classesOutput = [];
            $relationshipOutput = [];
            foreach ($bmmItem->classes as $className) {
                $class = Globals::getClass($className);
                if ($class instanceof BmmClass) {
                    $classesOutput[] = $this->formatClass($class, $prefix);
                    $relationshipOutput[] = $this->generateRelationships($class);
                }
                if ($class instanceof BmmInterface) {
                    $classesOutput[] = $this->formatInterface($class);
                }
                if ($class instanceof BmmEnumerationString || $class instanceof BmmEnumerationInteger) {
                    $classesOutput[] = $this->formatEnum($class);
                }
            }
            $content = implode('', $classesOutput) . implode('', $relationshipOutput);
            $purpose = $prefix;
            $title = $bmmItem->name . ' Package';
        }

        return <<<EOD
@startuml
' PlantUML diagram for {$purpose}
title {$title}

{$content}
@enduml
EOD;
    }

    /**
     * Generate PlantUML class definition
     *
     * @param BmmClass $class The BMM class to convert
     * @return string The PlantUML class definition
     */
    private function formatClass(BmmClass $class): string
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

    private function formatInterface(BmmInterface $interface): string
    {
        $output = "interface " . $interface->name . " {\n";
        /** @var BmmFunction $function */
        foreach ($interface->functions as $function) {
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
    private function formatEnum(BmmEnumerationString|BmmEnumerationInteger $enum): string
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
     * @return string The PlantUML representation of ancestors and inheritance relationships
     */
    private function formatClassAncestors(BmmClass $class): string
    {
        $output = '';
        foreach ($class->ancestors as $ancestorName) {
            if ($this->isHidden($ancestorName)) {
                continue;
            }
            $ancestor = Globals::getClass($ancestorName);
            if ($ancestor instanceof BmmClass) {
                $output .= $this->formatClass($ancestor);
                $output .= $this->formatClassAncestors($ancestor);
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

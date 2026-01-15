<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use OpenEHR\Tools\CodeGen\Writer\Formatter\AsciidocBmmJson;
use OpenEHR\Tools\CodeGen\Writer\Formatter\AsciidocDefinition;
use OpenEHR\Tools\CodeGen\Writer\Formatter\AsciidocPlantUml;
use OpenEHR\Tools\CodeGen\Writer\Formatter\AsciidocTab;
use RuntimeException;

/**
 * Writer class for converting BMM objects to AsciiDoc tables
 */
class BmmAsciidocWriter extends AbstractWriter
{
    // Target directory for AsciiDoc outputs
    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'Adoc' . DIRECTORY_SEPARATOR;

    private AsciidocTab $tab;
    private AsciidocDefinition $definition;
    private AsciidocBmmJson $bmmJson;
    private AsciidocPlantUml $plantUml;

    public function __construct(private readonly bool $legacyFormat = false)
    {
        $this->tab = new AsciidocTab();
        $this->definition = new AsciidocDefinition($this->legacyFormat);
        $this->bmmJson = new AsciidocBmmJson();
        $this->plantUml = new AsciidocPlantUml();
    }

    public function write(): void
    {
        $this->assureOutputDir();
        /** @var BmmSchema $schema */
        foreach ($this->reader->files as $schema) {
            // Build prefix e.g. org.openehr.rm
            /** @var BmmPackage $package */
            foreach ($schema->packages as $package) {
                $this->writePackage($package, $schema, '');
                /** @var BmmPackage $subPackage */
                foreach ($package->packages as $subPackage) {
                    $this->writePackage($subPackage, $schema, $package->name . '.');
                    // one level deeper for sub-packages (consistent with other writers)
                    /** @var BmmPackage $subSubPackage */
                    foreach ($subPackage->packages as $subSubPackage) {
                        $this->writePackage($subSubPackage, $schema, $package->name . '.' . $subPackage->name . '.');
                    }
                }
            }
        }
    }

    private function writePackage(BmmPackage $package, BmmSchema $schema, string $namePrefix): void
    {
        if (!count($package->classes)) {
            self::log('WARN: Empty package %s.', $package->name);
            return;
        }
        $prefix = 'org.openehr.' . strtolower($schema->schemaName) . '.';
        $prefix .= explode('.', str_replace($prefix, '', $namePrefix . $package->name))[0];
        if (!$this->legacyFormat && $schema->schemaName === 'am') {
            $parts = explode('.', $prefix);
            $pkg = end($parts) . '.';
        } else {
            $pkg = '';
        }
        $definitionsDir = self::DIR . $schema->getSchemaId() . '/definitions/';
        $this->assureOutputDir($definitionsDir);
        $tabsDir = self::DIR . $schema->getSchemaId() . '/classes/';
        $this->assureOutputDir($tabsDir);
        $bmmJsonDir = self::DIR . $schema->getSchemaId() . '/BMMs/';
        $this->assureOutputDir($bmmJsonDir);
        $plantUmlClassesDir = self::DIR . $schema->getSchemaId() . '/plantUML/classes/';
        $this->assureOutputDir($plantUmlClassesDir);
        $plantUmlPackagesDir = self::DIR . $schema->getSchemaId() . '/plantUML/packages/';
        $this->assureOutputDir($plantUmlPackagesDir);
        foreach ($package->classes as $className) {
            /** @var AbstractBmmClass $class */
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            if (!$class) {
                throw new RuntimeException(sprintf('WARN: Class %s not found in schema', $className));
            }
            if ($this->legacyFormat) {
                $filename = $prefix . '.' . strtolower($className) . '.adoc';
            } else {
                $filename = $pkg . strtolower($className) . '.adoc';
            }
            self::log('Writing %s class ...', $filename);
            $this->writeFile($definitionsDir . $filename, $this->definition->format($class, $prefix));
            $this->writeFile($tabsDir . $filename, $this->tab->format($class, $filename));
            $this->writeFile($bmmJsonDir . $filename, $this->bmmJson->format($class));
            $this->writeFile($plantUmlClassesDir . $filename, $this->plantUml->format($class, $prefix));
        }
        $prefix = 'org.openehr.' . strtolower($schema->schemaName) . '.';
        $namePrefix = $prefix . str_replace($prefix, '', $namePrefix);
        if ($this->legacyFormat) {
            $packageName = rtrim($namePrefix . str_replace($namePrefix, '', $package->name), '.');
        } else {
            $packageName = strtoupper($schema->schemaName) . '-' . $pkg . rtrim(str_replace($namePrefix, '', $package->name), '.');
        }
        self::log('Writing %s package ...', $packageName);
        $this->writeFile($plantUmlPackagesDir . $packageName . '.adoc', $this->plantUml->format($package, $packageName));
    }

}

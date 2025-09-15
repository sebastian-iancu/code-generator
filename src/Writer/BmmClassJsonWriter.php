<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;

/**
 * Writer to export each BMM class/enum from schema files into individual .bmm.json files.
 *
 * Output files are written to code/BMM-JSON/classes with the pattern:
 *   {schemaPath}/{packagePath}.{class}.bmm.json
 */
class BmmClassJsonWriter extends AbstractWriter
{
    /**
     * Output directory for class json files.
     */
    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'BMM-JSON' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;

    public function write(): void
    {
        $this->assureOutputDir();
        /** @var BmmSchema $schema */
        foreach ($this->reader->files as $schema) {
            /** @var BmmPackage $package */
            foreach ($schema->packages as $package) {
                $this->exportPackage($schema, $package, '');
                // one level deeper for sub-packages (consistent with other writers)
                /** @var BmmPackage $subPackage */
                foreach ($package->packages as $subPackage) {
                    $this->exportPackage($schema, $subPackage, $package->name . '.');
                    // one level deeper for sub-packages (consistent with other writers)
                    /** @var BmmPackage $subSubPackage */
                    foreach ($subPackage->packages as $subSubPackage) {
                        $this->exportPackage($schema, $subSubPackage, $package->name . '.' . $subPackage->name . '.');
                    }
                }
            }
        }
    }

    private function exportPackage(BmmSchema $schema, BmmPackage $package, string $namePrefix): void
    {
        $prefix = 'org.openehr.'.strtolower($schema->schemaName).'.';
        $prefix .= explode('.', str_replace($prefix, '', $namePrefix . $package->name))[0] . '.';
        $packageDir = self::DIR . $schema->getSchemaId() . '/';
        $this->assureOutputDir($packageDir);
        foreach ($package->classes as $className) {
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            if (!$class) {
                self::log('Warning: Class %s not found in schema %s', $className, $schema->getSchemaId());
                continue;
            }

            $filename = $packageDir . $prefix . strtolower($className) . '.bmm.json';
            $data = $class->jsonSerialize();
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                self::log('Warning: Failed to encode JSON for class %s', $className);
                continue;
            }
            $bytes = file_put_contents($filename, $json . PHP_EOL);
            self::log('Wrote %d bytes to %s', (int)$bytes, $filename);
        }
    }
}

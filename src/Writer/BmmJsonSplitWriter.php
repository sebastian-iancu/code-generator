<?php

namespace OpenEHR\Tools\CodeGen\Writer;

use OpenEHR\Tools\CodeGen\Model\Bmm\AbstractBmmClass;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmPackage;
use OpenEHR\Tools\CodeGen\Model\Bmm\BmmSchema;
use RuntimeException;

/**
 * Writer that exports a BMM schema into per-type BMM JSON files.
 *
 * Output pattern (inside repository root):
 *   code/BMM-JSON-development-types/{COMPONENT}/
 * Each file name is: {org.openehr.<schema>.<full.package.path>}.{type-name-lowercase}.bmm.json
 */
class BmmJsonSplitWriter extends AbstractWriter
{
    public const string DIR = __WRITER_DIR__ . DIRECTORY_SEPARATOR . 'BMM-JSON-development-types' . DIRECTORY_SEPARATOR;

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
            self::log('WARN: Empty package [%s] found.', $package->name);
            return;
        }
        $packagePrefixName = 'org.openehr.' . strtolower($schema->schemaName) . '.';
        $packageName = $packagePrefixName . str_replace($packagePrefixName, '', $namePrefix . $package->name);
        if (($schema->schemaName === 'am')) {
            $outputDir = self::DIR . 'AM' . (str_starts_with($schema->rmRelease, '2') ? '2' : '') . '/';
        } else {
            $outputDir = self::DIR . strtoupper($schema->schemaName) . '/';
        }
        $this->assureOutputDir($outputDir);
        foreach ($package->classes as $className) {
            /** @var AbstractBmmClass $class */
            $class = $schema->classDefinitions->get($className) ?? $schema->primitiveTypes->get($className);
            if (!$class) {
                throw new RuntimeException(sprintf('WARN: Class %s not found in schema', $className));
            }
            $filename = strtoupper($className) . '.bmm.json';
            self::log('Preparing %s class ...', $filename);
            $data = $class->jsonSerialize();
            $data['package'] = $packageName;
            $parts = array_reverse(explode('.', str_replace($packagePrefixName, '', $packageName)));
            if (!empty($parts)) {
                $fragment = match ($parts[0]) {
                    'assertion' => '_the_assertion_package',
                    'archetype' => '_the_archetype_package',
                    'rm_overlay' => '_the_rm_overlay_package',
                    default => '_'.$parts[0].'_package',
                };
                $page = $parts[2] ?? $parts[1] ?? $parts[0];
                $page = match ($page) {
                    'aom14' => 'AOM1.4.html',
                    'aom2' => 'AOM2.html',
                    default => $page . '.html',
                };
                $data['specUrl'] = sprintf('https://specifications.openehr.org/releases/%s/development/%s#%s', strtoupper($schema->schemaName), $page, $fragment);
            }
            self::log('Writing %s class ...', $filename);
            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $this->writeFile($outputDir . $filename, $content);
        }
    }
}

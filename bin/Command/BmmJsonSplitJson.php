<?php

namespace Console\Command;

use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Reader\BmmJsonReader;
use OpenEHR\Tools\CodeGen\Writer\BmmJsonSplitWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate per-type BMM JSON files from the latest BMM schemas of each openEHR component.
 *
 * Usage:
 *   generate bmm:split
 */
class BmmJsonSplitJson extends Command
{
    protected function configure(): void
    {
        $this->setName('bmm:split');
        $this->setDescription('Split latest BMM JSON of each component into per-type .bmm.json files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $latest = $this->findLatestSchemas();
            if (empty($latest)) {
                $output->writeln('<comment>No BMM JSON files found.</comment>');
                return Command::SUCCESS;
            }

            $reader = new BmmJsonReader();
            foreach ($latest as $filename) {
                $reader->read(basename($filename));
            }
            $reader->read('openehr_am_1.4.0');
            $generator = new CodeGenerator($reader);
            $generator->addWriter(new BmmJsonSplitWriter());
            $generator->generate();
        } catch (\Throwable $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string> full paths to latest schema files by component
     */
    private function findLatestSchemas(): array
    {
        $files = glob(BmmJsonReader::DIR . '*.bmm.json');
        $byComponent = [];
        foreach ($files as $path) {
            $base = basename($path, '.bmm.json'); // e.g., openehr_am_2.4.0
            $parts = explode('_', $base);
            if (count($parts) < 2) {
                // No version component; treat whole as component with version 0
                $component = $base;
                $version = '0.0.0';
            } else {
                $version = array_pop($parts);
                $component = implode('_', $parts);
            }
            $current = $byComponent[$component] ?? null;
            if (!$current) {
                $byComponent[$component] = [$version, $path];
            } else {
                if (version_compare($version, $current[0]) > 0) {
                    $byComponent[$component] = [$version, $path];
                }
            }
        }
        return array_map(fn($tuple) => $tuple[1], array_values($byComponent));
    }
}

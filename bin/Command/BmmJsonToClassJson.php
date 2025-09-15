<?php

namespace Console\Command;

use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Reader\BmmJsonReader;
use OpenEHR\Tools\CodeGen\Writer\BmmClassJsonWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to split BMM JSON package files into individual class JSON files.
 *
 * For each provided BMM schema file, it delegates writing logic to a Writer
 * that exports one JSON file per contained class or enumeration into code/BMM-classes
 * with the filename pattern: "class-{package}-{class}.bmm.json".
 */
class BmmJsonToClassJson extends Command
{
    protected function configure(): void
    {
        $this->setName('bmm:split');
        $this->setAliases(['bmm:classes']);
        $this->setDescription('Export each BMM class/enum from schema files into individual .bmm.json files.');
        $this->addArgument(
            'filename',
            InputArgument::IS_ARRAY,
            "File(s) with .bmm.json extension to be processed. \n"
            . "Example: <info>generate bmm:split openehr_base_1.2.0 openehr_rm_1.1.0</info>. \n"
            . "Alternatively, use 'all' to process all .bmm.json files in the directory. \n"
            . "Example: <info>generate bmm:split all</info>.",
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $toRead = $input->getArgument('filename');
        if (empty($toRead)) {
            $output->writeln('<error>Please specify which BMM file(s) should be read. See usage with --help.</error>');
            return Command::INVALID;
        }

        if ($toRead[0] === 'all') {
            $toRead = array_map(fn($filename) => basename($filename), glob(BmmJsonReader::DIR . '*.bmm.json'));
        }

        try {
            $reader = new BmmJsonReader();
            foreach ($toRead as $schema) {
                $reader->read($schema);
            }
            $generator = new CodeGenerator($reader);
            $generator->addWriter(new BmmClassJsonWriter());
            $generator->generate();
        } catch (\Throwable $e) {
            $output->writeln('<error>' . (string)$e . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

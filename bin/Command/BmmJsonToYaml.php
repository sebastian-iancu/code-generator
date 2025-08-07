<?php

namespace Console\Command;

use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Reader\BmmJsonReader;
use OpenEHR\Tools\CodeGen\Writer\BmmYamlWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to convert BMM JSON files to YAML format
 */
class BmmJsonToYaml extends Command
{
    protected function configure(): void
    {
        $this->setName('bmm:yaml');
        $this->setDescription('Convert BMM JSON files to YAML format.');
        $this->addArgument(
            'filename',
            InputArgument::IS_ARRAY,
            "File(s) with .bmm.json extension to be converted to YAML. \n"
            . "Example: <info>generate bmm:yaml BASE-v1.2.0 RM-v1.1.0</info>. \n"
            . "Alternatively, use 'all' to convert all .bmm.json files in the directory. \n"
            . "Example: <info>generate bmm:yaml all</info>.",
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
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new BmmYamlWriter());
            $writer->generate();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php

namespace Console\Command;

use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Reader\BmmJsonReader;
use OpenEHR\Tools\CodeGen\Writer\BmmPlantUmlWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to convert BMM JSON files to PlantUML format
 */
class BmmJsonToPlantUml extends Command
{
    protected function configure(): void
    {
        $this->setName('bmm:plantuml');
        $this->setDescription('Convert BMM JSON files to PlantUML format.');
        $this->addArgument(
            'filename',
            InputArgument::IS_ARRAY,
            "File(s) with .bmm.json extension to be converted to PlantUML. \n"
            . "Example: <info>generate bmm:plantuml BASE-v1.2.0 RM-v1.1.0</info>. \n"
            . "Alternatively, use 'all' to convert all .bmm.json files in the directory. \n"
            . "Example: <info>generate bmm:plantuml all</info>.",
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
            $writer->addWriter(new BmmPlantUmlWriter());
            $writer->generate();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php

namespace Console\Command;

use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Reader\BmmJsonReader;
use OpenEHR\Tools\CodeGen\Writer\BmmAsciidocWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command class responsible for converting BMM JSON files to AsciiDoc format.
 *
 * Usage examples:
 *  generate bmm:adoc openehr_rm_1.1.0
 *  generate bmm:adoc all
 */
class BmmJsonToAsciiDoc extends Command
{
    protected function configure(): void
    {
        $this->setName('bmm:adoc');
        $this->setAliases(['bmm:asciidoc']);
        $this->setDescription('Convert BMM JSON files to AsciiDoc tables.');
        $this->addArgument(
            'filename',
            InputArgument::IS_ARRAY,
            "File(s) with .bmm.json extension to be converted to AsciiDoc. \n"
            . "Example: <info>generate bmm:adoc openehr_rm_1.1.0</info>. \n"
            . "Alternatively, use 'all' to convert all .bmm.json files in the directory. \n"
            . "Example: <info>generate bmm:adoc all</info>.",
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $legacyFormat = false;
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
                if ($schema === 'legacy') {
                    $legacyFormat = true;
                    continue;
                }
                $reader->read($schema);
            }
            $generator = new CodeGenerator($reader);
            $generator->addWriter(new BmmAsciidocWriter($legacyFormat));
            $generator->generate();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

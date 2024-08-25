<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\ReadManager;
use OpenEHR\Tools\CodeGen\WriteManager;
use OpenEHR\Tools\CodeGen\Writer\BMM;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBmm extends Command
{

    protected function configure(): void
    {
        $this->setName('generate:bmm');
        $this->setAliases(['bmm']);
        $this->setDescription('Generate BMM files based on indicated XMI schema(s).');
        $this->addArgument(
            'read',
            InputArgument::IS_ARRAY,
            'XMI schema(s) to read; multiple schemas are supported when given as multiple arguments. '
            . 'Dependencies should be read first (i.e. first BASE then RM). '
            . 'Example: <info>generate bmm BASE-v1.2.0 RM-v1.1.0</info>.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $toRead = $input->getArgument('read');
        if (empty($toRead)) {
            $output->writeln('<error>Please specify which XMI schema should be read. See usage with --help.</error>');
            return Command::INVALID;
        }
        try {
            $reader = new ReadManager();
            foreach ($toRead as $schema) {
                $reader->read($schema . '.xmi');
            }
            $writer = new WriteManager($reader);
            $writer->addWriter(new BMM());
            $writer->write();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

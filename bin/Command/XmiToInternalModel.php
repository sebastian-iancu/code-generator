<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\Reader\XmiReader;
use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class XmiToInternalModel extends Command
{

    protected function configure(): void
    {
        $this->setName('xmi:internal-model');
        $this->setAliases(['internal-model', 'internal', 'dump']);
        $this->setDescription('Dump internal model of read the XMI schema(s).');
        $this->addArgument(
            'write',
            InputArgument::REQUIRED,
            'Filename to dump the internal model. '
            . 'Example: <info>generate internal RM-1.1.0</info>.',
        );
        $this->addArgument(
            'read',
            InputArgument::IS_ARRAY,
            'XMI schema(s) to read; multiple schemas are supported when given as multiple arguments. '
            . 'Dependencies should be read first. '
            . 'Example: <info>generate internal BASE_and_RM-v1.1.0 BASE-v1.2.0 RM-v1.1.0</info>.',
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getArgument('write');
        $toRead = $input->getArgument('read') ?: [$target];
        try {
            $reader = new XmiReader();
            foreach ($toRead as $schema) {
                $reader->read($schema);
            }
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel($target));
            $writer->generate();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

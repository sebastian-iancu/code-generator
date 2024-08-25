<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\ReadManager;
use OpenEHR\Tools\CodeGen\WriteManager;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateInternalModel extends Command
{

    protected function configure(): void
    {
        $this->setName('generate:internal-model');
        $this->setAliases(['internal-model', 'internal', 'dump']);
        $this->setDescription('Generate and dump internal model of read XMI schema(s).');
        $this->addArgument(
            'write',
            InputArgument::REQUIRED,
            'Filename to dump the internal model. '
            . 'Example: <info>generate internal BASE_and_RM-1.1.0</info>.',
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
        $ext = '.internal.json';
        $target = str_replace($ext, '', $input->getArgument('write'));
        $toRead = $input->getArgument('read') ?: [$target];
        try {
            $reader = new ReadManager();
            foreach ($toRead as $schema) {
                $reader->read($schema . '.xmi');
            }
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel($target . $ext));
            $writer->write();
        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

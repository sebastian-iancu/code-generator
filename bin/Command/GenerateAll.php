<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\ReadManager;
use OpenEHR\Tools\CodeGen\WriteManager;
use OpenEHR\Tools\CodeGen\Writer\BMM;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAll extends Command
{

    protected function configure(): void
    {
        $this->setName('generate:all');
        $this->setAliases(['all']);
        $this->setDescription('Generate and dump BMM and internal model for all schema.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $reader = new ReadManager();
            $reader->read('BASE-v1.2.0.xmi');
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel('BASE-v1.2.0.internal.json'));
            $writer->write();
            $reader->read('RM-v1.1.0.xmi');
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel('BASE_and_RM-v1.1.0.internal.json'));
            $writer->write();

            // older BASE
            $reader = new ReadManager();
            $reader->read('BASE-v1.1.0.xmi');
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel('BASE-v1.1.0.internal.json'));
            $writer->addWriter(new BMM());
            $writer->write();

            // AM
            $reader = new ReadManager();
            $reader->read('BASE-v1.2.0.xmi');
            $reader->read('AM-v2.2.0.xmi');
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel('BASE_and_AM-v2.2.0.internal.json'));
            $writer->write();
            $reader->read('RM-v1.1.0.xmi');
            $writer = new WriteManager($reader);
            $writer->addWriter(new InternalModel('all.internal.json'));
            $writer->write();
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

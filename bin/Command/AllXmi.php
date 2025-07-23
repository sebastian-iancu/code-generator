<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\Reader\XmiReader;
use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use OpenEHR\Tools\CodeGen\Writer\UmlToBmmWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AllXmi extends Command
{

    protected function configure(): void
    {
        $this->setName('xmi:all');
        $this->setAliases(['all']);
        $this->setDescription('Generate BMM files and dump internal model for all XMI schemas.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $reader = new XmiReader();
            $reader->read('BASE-v1.2.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE-v1.2.0'));
            $writer->generate();
            $reader->read('RM-v1.1.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.2.0_and_RM-v1.1.0'));
            $writer->generate();

            // older BASE
            $reader = new XmiReader();
            $reader->read('BASE-v1.1.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE-v1.1.0'));
            $writer->addWriter(new UmlToBmmWriter());
            $writer->generate();

            // AM
            $reader = new XmiReader();
            $reader->read('BASE-v1.2.0.xmi');
            $reader->read('AM-v2.2.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.2.0_and_AM-v2.2.0'));
            $writer->generate();
            $reader->read('RM-v1.1.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.2.0_and_AM-v2.2.0_and_RM-v1.1.0'));
            $writer->addWriter(new UmlToBmmWriter());
            $writer->generate();
            $reader->read('AM-v2.3.0.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.2.0_and_AM-v2.3.0_and_RM-v1.1.0'));
            $writer->addWriter(new UmlToBmmWriter());
            $writer->generate();

            // development
            $reader = new XmiReader();
            $reader->read('BASE-v1.3.0-dev.xmi');
            $reader->read('AM-v2.4.0-dev.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.3.0-dev_and_AM-v2.4.0-dev'));
            $writer->generate();
            $reader->read('RM-v1.2.0-dev.xmi');
            $writer = new CodeGenerator($reader);
            $writer->addWriter(new InternalModel('BASE_v1.3.0-dev_and_AM-v2.4.0-dev_and_RM-v1.2.0-dev'));
            $writer->addWriter(new UmlToBmmWriter());
            $writer->generate();

        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

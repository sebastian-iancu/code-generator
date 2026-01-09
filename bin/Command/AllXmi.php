<?php

namespace Console\Command;


use OpenEHR\Tools\CodeGen\Reader\XmiReader;
use OpenEHR\Tools\CodeGen\CodeGenerator;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use OpenEHR\Tools\CodeGen\Writer\UmlToBmmWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AllXmi extends Command
{

    protected function configure(): void
    {
        $this->setName('xmi:all');
        $this->setAliases(['all']);
        $this->setDescription('Generate BMM files and dump internal model for all XMI schemas.');
        $this->addArgument(
            'type',
            InputArgument::OPTIONAL,
            'Type of model to generate; one of: `internal` or `bmm`. '
            . 'Example: <info>generate all internal</info>.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type') ?: '';
        try {
            if ($type === 'internal') {
                // BASE_v1.0.4_and_RM-v1.0.4
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.0.4.xmi');
                $writer->addWriter(new InternalModel('BASE-v1.0.4'));
                $writer->generate();
                $reader->read('RM-v1.0.4.xmi');
                $writer = new CodeGenerator($reader);
                $writer->addWriter(new InternalModel('BASE_v1.0.4_and_RM-v1.0.4'));
                $writer->generate();
                // BASE-v1.1.0
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.1.0.xmi');
                $writer->addWriter(new InternalModel('BASE-v1.1.0'));
                $writer->generate();
                // BASE_v1.2.0_and_RM-v1.1.0
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.2.0.xmi');
                $writer->addWriter(new InternalModel('BASE-v1.2.0'));
                $writer->generate();
                $reader->read('RM-v1.1.0.xmi');
                $writer = new CodeGenerator($reader);
                $writer->addWriter(new InternalModel('BASE_v1.2.0_and_RM-v1.1.0'));
                $writer->generate();
                // BASE_v1.2.0_and_AM-v2.3.0
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.2.0.xmi');
                $reader->read('AM-v2.3.0.xmi');
                $writer->addWriter(new InternalModel('BASE_v1.2.0_and_AM-v2.3.0'));
                $writer->generate();
                // BASE_v1.3.0-dev_and_AM-v2.4.0-dev
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.3.0-dev.xmi');
                $writer->addWriter(new InternalModel('BASE-v1.3.0-dev'));
                $writer->generate();
                $reader->read('AM-v2.4.0-dev.xmi');
                $writer = new CodeGenerator($reader);
                $writer->addWriter(new InternalModel('BASE_v1.3.0-dev_and_AM-v2.4.0-dev'));
                $writer->generate();
                // BASE_v1.3.0-dev_and_RM-v1.2.0-dev
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.3.0-dev.xmi');
                $reader->read('RM-v1.2.0-dev.xmi');
                $writer->addWriter(new InternalModel('BASE_v1.3.0-dev_and_RM-v1.2.0-dev'));
                $writer->generate();
                // LANG-v1.0.0.xmi
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('LANG-v1.0.0.xmi');
                $writer->addWriter(new InternalModel('LANG-v1.0.0'));
                $writer->generate();
                // LANG-v1.1.0-dev.xmi
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('LANG-v1.1.0-dev.xmi');
                $writer->addWriter(new InternalModel('LANG-v1.1.0-dev'));
                $writer->generate();
                // TERM-v3.0.0.xmi
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('TERM-v3.0.0.xmi');
                $writer->addWriter(new InternalModel('TERM-v3.0.0'));
                $writer->generate();
                // TERM-v3.1.0-dev.xmi
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('TERM-v3.1.0-dev.xmi');
                $writer->addWriter(new InternalModel('TERM-v3.1.0-dev'));
                $writer->generate();
            } else {
                //
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.0.4.xmi');
                $reader->read('RM-v1.0.4.xmi');
                $writer->addWriter(new UmlToBmmWriter());
                $writer->generate();
                //
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.1.0.xmi');
                $reader->read('AM-v2.2.0.xmi');
                $writer->addWriter(new UmlToBmmWriter(['aom14']));
                $writer->generate();
                //
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.2.0.xmi');
                $reader->read('LANG-v1.0.0.xmi');
                $reader->read('TERM-v3.0.0.xmi');
                $reader->read('RM-v1.1.0.xmi');
                $reader->read('AM-v2.3.0.xmi');
                $writer->addWriter(new UmlToBmmWriter(['aom14']));
                $writer->generate();
                //
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.3.0-dev.xmi');
                $reader->read('LANG-v1.1.0-dev.xmi');
                $reader->read('TERM-v3.1.0-dev.xmi');
                $reader->read('AM-v2.4.0-dev.xmi');
                $writer->addWriter(new UmlToBmmWriter(['aom14']));
                $writer->generate();
                // AOM14
                $reader = new XmiReader();
                $writer = new CodeGenerator($reader);
                $reader->read('BASE-v1.3.0-dev.xmi');
                $reader->read('RM-v1.2.0-dev.xmi');
                $reader->read('AM-v1.4.0-dev.xmi');
                $writer->addWriter(new UmlToBmmWriter(['aom2']));
                $writer->generate();
            }

        } catch (\UnhandledMatchError $e) {
            $output->writeln((string)$e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

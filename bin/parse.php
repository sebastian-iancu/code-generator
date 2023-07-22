#!/usr/bin/env php
<?php

use OpenEHR\Tools\CodeGen\Helper;
use OpenEHR\Tools\CodeGen\Writer\InternalModel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';


//$reader = new Helper\XMIReader();
//$reader->read('BASE-v1.2.0.xmi');
//$reader->read('RM-v1.1.0.xmi');
//$reader->read('AM-v2.2.0.xmi');
//$reader->read('LANG-v1.0.0.xmi');
//
//$writer = new Helper\Writer($reader);
//$writer->addWriter(new InternalModel('all.json'));
//$writer->write();


$reader = new Helper\XMIReader();
$reader->read('BASE-v1.2.0.xmi');
$reader->read('RM-v1.1.0.xmi');

$writer = new Helper\Writer($reader);
$writer->addWriter(new InternalModel('BASE_and_RM-1.1.0.json'));
$writer->write();

//$reader = new Helper\XMIReader();
//$reader->read('BASE-v1.0.3-AM.xmi');
//$reader->read('AM-latest.xmi');
//
//$writer = new Helper\Writer($reader);
//$writer->addWriter(new AllJson('AM.json'));
//$writer->write();


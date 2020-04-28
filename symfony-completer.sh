#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use LiquidLight\SymfonyAutocomplete\Command\Completer;
use LiquidLight\SymfonyAutocomplete\Command\Install;

$application = new Application();

$application->add(new Completer());
$application->add(new Install());

$application->run();

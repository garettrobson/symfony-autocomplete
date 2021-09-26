#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use LiquidLight\SymfonyAutocomplete\Command\Completer;
use LiquidLight\SymfonyAutocomplete\Command\Install;

$application = new Application('Symfony Autocomplete', '0.0.1');

$application->add(new Completer());

if(!Phar::running()) {
    $application->add(new Install());
}

$application->run();

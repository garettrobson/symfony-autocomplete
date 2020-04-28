#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use LiquidLight\SymfonyAutocomplete\Command\Completer;

$application = new Application();

$application->add(new Completer());

$application->run();

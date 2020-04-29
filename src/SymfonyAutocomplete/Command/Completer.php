<?php
// src/Command/CreateUserCommand.php
namespace LiquidLight\SymfonyAutocomplete\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Completer extends Command
{

    protected static $defaultName = 'completer';

    protected function configure()
    {
        $this
            ->setDescription('Generated autocomplete values for a symfony console command.')
            ->setHelp('Generated autocomplete values for a symfony console command using the JSON formatted output it provides.')
            ->addArgument(
                'COMP_WORDS',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output->getErrorOutput();

        $command = $input->getArgument('COMP_WORDS');
        $extra = explode(' ', $command);
        if (count($extra)) {
            $app = array_shift($extra);
        }
        if (count($extra)) {
            $com = array_shift($extra);
        }

        $cmd = $app.' --format=json';
        $json = exec($cmd, $json, $code);
        $description = json_decode($json);

        $coms = $this->getCommands($description);

        if ($com) {
            $coms = $this->filterStartingWith($coms, $com);
        }

        if (count($coms) === 1) {
            echo implode(PHP_EOL, array_filter($coms)).PHP_EOL;
            return 0;
        }

        if (count($extra)) {
            if (strpos(end($extra), '-') !== 0) {
                return 1;
            }
            $cmd = $app.' help '.$com.' --format=json';
            $json = exec($cmd, $json, $code);
            $description = json_decode($json);
            $coms = $this->getOptions($description);
        }
        echo implode(PHP_EOL, array_filter($coms)).PHP_EOL;
        return 0;
    }

    protected function getCommands($description)
    {
        $commands = [];
        foreach ($description->commands as $command) {
            $commands[] = $command->name;
        }
        return array_values(array_unique($commands));
    }

    protected function getOptions($description)
    {
        $options = [];
        foreach ($description->definition->options as $option) {
            $options[] = $option->name;
            foreach (explode('|', $option->shortcut) as $short) {
                $options[] = $short;
            }
        }
        return array_values(array_unique($options));
    }

    protected function filterStartingWith(array $haystack, string $needle)
    {
        $result = array_values(array_filter($haystack, function ($str) use ($needle) {
            return strpos($str, $needle) === 0;
        }));
        if (strpos($needle, ':') !== false) {
            array_walk($result, function (&$str) use ($needle) {
                $str = preg_replace('/^.*:/', '', $str);
            });
        }
        return $result;
    }
}

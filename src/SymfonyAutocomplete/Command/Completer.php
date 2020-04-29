<?php
// src/Command/CreateUserCommand.php
namespace LiquidLight\SymfonyAutocomplete\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Completer extends Command
{
    protected static $defaultName = 'completer';

    protected function configure()
    {
        $this
            ->setDescription('Generated autocomplete values for a symfony console command.')
            ->setHelp('Generated autocomplete values for a symfony console command using the JSON formatted output it provides.')
            ->addOption(
                'COMP_CWORD',
                null,
                InputOption::VALUE_REQUIRED,
                'An index into ${COMP_WORDS} of the word containing the current cursor position.'
            )
            ->addOption(
                'COMP_LINE',
                null,
                InputOption::VALUE_REQUIRED,
                'The current command line.'
            )
            ->addOption(
                'COMP_POINT',
                null,
                InputOption::VALUE_REQUIRED,
                'The index of the current cursor position relative to the beginning of the current command. If the current cursor position is at the end of the current command, the value of this variable is equal to ${#COMP_LINE}.'
            )
            ->addOption(
                'COMP_WORDBREAKS',
                null,
                InputOption::VALUE_REQUIRED,
                'The set of characters that the Readline library treats as word separators when performing word completion.'
            )
            ->addOption(
                'COMP_WORDS',
                null,
                InputOption::VALUE_REQUIRED,
                'An array variable consisting of the individual words in the current command line, ${COMP_LINE}.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output->getErrorOutput();

        $command = $input->getOption('COMP_WORDS');
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

<?php
// src/Command/CreateUserCommand.php
namespace LiquidLight\SymfonyAutocomplete\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Completer extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'completer';

    protected function configure()
    {
        $this
            ->setDescription('Generated autocomplete values for a symfony console command.')
            ->setHelp('Generated autocomplete values for a symfony console command using the JSON formatted output it provides.')
            ->addArgument(
                'app',
                InputArgument::REQUIRED,
                'How much of the command has been written so far'
            )
            ->addArgument(
                'com',
                InputArgument::OPTIONAL,
                'How much of the command has been written so far'
            )
            ->addArgument(
                'colon',
                InputArgument::OPTIONAL,
                'How much of the command has been written so far'
            )
            ->addArgument(
                'sub',
                InputArgument::OPTIONAL,
                'How much of the command has been written so far'
            )
            ->addArgument(
                'ext',
                InputArgument::IS_ARRAY,
                'How much of the command has been written so far'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stdErr = $output->getErrorOutput() ?? $output;
        //$stdErr->writeln(var_export($input->getArguments(),1));

        $app = $input->getArgument('app');
        $com = $input->getArgument('com');
        $colon = $input->getArgument('colon');
        $sub = $input->getArgument('sub');
        if ($colon) {
            $com.=$colon;
        }
        if ($sub) {
            $com.=$sub;
        }
        $ext = $input->getArgument('ext');

        $cmd = $app.' --format=json';
        $json = exec($cmd, $json, $code);
        $description = json_decode($json);

        $coms = $this->getCommands($description);

        if ($com) {
            $coms = $this->filterStartingWith($coms, $com);
        }

        if (
            count($coms) === 1 &&
            $sub === $coms[0]
        ) {
            if (strpos(end($ext), '-') !== 0) {
                return 1;
            }

            $cmd = $app.' help '.$com.' --format=json';
            var_dump($cmd);

            $json = exec($cmd, $json, $code);
            $description = json_decode($json);
            $coms = $this->getOptions($description);
        }
        //$stdErr->writeln(var_export($coms,1));
        echo implode("\n", array_filter($coms));
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

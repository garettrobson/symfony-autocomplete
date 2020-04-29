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

    protected $shellCommand = null;
    protected $symfonyCommand = null;
    protected $availableCommands = [];

    protected $tokenIndex = 0;
    protected $tokens = null;

    protected $COMP_CWORD = false;
    protected $COMP_LINE = false;
    protected $COMP_POINT = false;
    protected $COMP_WORDBREAKS = false;
    protected $COMP_WORDS = false;

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
            )
            ->addOption(
                'COMP_CURR',
                null,
                InputOption::VALUE_REQUIRED,
                "The current word being processed."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadProperties($input);

        // Output command options
        if($input->getOption('verbose')) {
            $output->getErrorOutput()->writeln(sprintf(
                "\nOptions: <info>%s</info>",
                json_encode($input->getOptions(), JSON_PRETTY_PRINT)
            ));
        }

        // Output generated values
        if($input->getOption('verbose')) {
            $output->getErrorOutput()->writeln(sprintf(
                "\nComputed: <info>%s</info>",
                json_encode([
                    'shellCommand' => $this->shellCommand,
                    'symfonyCommand' => $this->symfonyCommand,
                    'availableCommands' => $this->availableCommands,
                    'tokens' => $this->tokens,
                    'tokenIndex' => $this->tokenIndex,
                ], JSON_PRETTY_PRINT)
            ));
        }

        if ($this->tokenIndex===0) {
            $commands = $this->availableCommands;
            if ($this->symfonyCommand) {
                $commands = $this->filterStartingWith(
                    $commands,
                    $this->symfonyCommand
                );
            }

            // Output command suggestions
            if($input->getOption('verbose')) {
                $output->getErrorOutput()->writeln(sprintf(
                    "\nCommand Suggestions: <info>%s</info>",
                    json_encode($commands, JSON_PRETTY_PRINT)
                ));
            }

            echo implode(PHP_EOL, array_filter($commands)).PHP_EOL;
            return 0;
        } elseif (preg_match('/^-/', $this->COMP_CURR)) {
            // We are looking up an option
            $cmd = $this->shellCommand.' help '.$this->symfonyCommand.' --format=json';
            $json = exec($cmd, $json, $code);
            $description = json_decode($json);
            $coms = $this->getOptions($description);
            $coms = $this->filterStartingWith(
                $coms,
                preg_match('/^-$/', $this->COMP_CURR) ? '--' : $this->COMP_CURR
            );

            // Output option suggestions
            if($input->getOption('verbose')) {
                $output->getErrorOutput()->writeln(sprintf(
                    "\nOption Suggestions: <info>%s</info>",
                    json_encode($coms, JSON_PRETTY_PRINT)
                ));
            }

            echo implode(PHP_EOL, array_filter($coms)).PHP_EOL;
            return 0;
        }

        return 1;
    }

    protected function loadProperties(InputInterface $input){
        // Bind all the command line options up
        $this->COMP_CWORD = (int)$input->getOption('COMP_CWORD');
        $this->COMP_LINE = $input->getOption('COMP_LINE');
        $this->COMP_POINT = (int)$input->getOption('COMP_POINT');
        $this->COMP_WORDBREAKS = $input->getOption('COMP_WORDBREAKS');
        $this->COMP_WORDS = $input->getOption('COMP_WORDS');
        $this->COMP_CURR = preg_replace('/^\'(.*)\'$/i', '$1', $input->getOption('COMP_CURR'));

        //$this->tokens = preg_split('/[^\s"\']+|"([^"]*)"|\'([^\']*)\'/', $this->COMP_LINE, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $this->tokens = preg_split('/\s+/', $this->COMP_LINE, -1, PREG_SPLIT_OFFSET_CAPTURE);

        foreach ($this->tokens as $index => $token) {
            $this->tokenIndex = $index - 1;
            if ($token[1] > $this->COMP_POINT) {
                break;
            }
        }

        if (isset($this->tokens[0]) && !$this->shellCommand) {
            $this->shellCommand = $this->tokens[0][0];
        }

        $json = null;
        exec($this->shellCommand.' --format=json', $json, $code);
        $this->availableCommands = $this->getCommands(json_decode(implode(PHP_EOL, $json)));

        if (isset($this->tokens[1]) && $this->tokens[1][0] && !$this->symfonyCommand) {
            $this->symfonyCommand = $this->tokens[1][0];
        }
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
        return $result;
    }
}

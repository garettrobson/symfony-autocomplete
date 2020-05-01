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
    protected $shellCommandOptions = [];
    protected $shellCommandArguments = [];

    protected $symfonyCommandsAvailable = [];

    protected $symfonyCommand = null;
    protected $symfonyCommandOptions = [];
    protected $symfonyCommandArguments = [];


    protected $tokenIndex = 0;
    protected $tokens = null;

    protected $COMP_CWORD = false;
    protected $COMP_LINE = false;
    protected $COMP_POINT = false;
    protected $COMP_WORDBREAKS = false;
    protected $COMP_WORDS = false;

    protected const TOKEN_MODE_AP    = -1; # 111
    protected const TOKEN_TYPE_APCMD = -4; # 100
    protected const TOKEN_TYPE_APARG = -3; # 101
    protected const TOKEN_TYPE_APOPT = -2; # 110
    protected const TOKEN_TYPE_SFCMD =  0; # 000
    protected const TOKEN_TYPE_SFARG =  1; # 001
    protected const TOKEN_TYPE_SFOPT =  2; # 010
    protected const TOKEN_MODE_COMPO =  3; # 011

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
        //$input->setOption('verbose', 1);
        $this->loadProperties($input);

        // Output command options
        if ($input->getOption('verbose')) {
            $output->getErrorOutput()->writeln(sprintf(
                "\nOptions: <info>%s</info>",
                json_encode([
                    'COMP_CWORD' => $this->COMP_CWORD,
                    'COMP_LINE' => $this->COMP_LINE,
                    'COMP_LINE#' => strlen($this->COMP_LINE),
                    'COMP_WORDBREAKS' => $this->COMP_WORDBREAKS,
                    'COMP_POINT' => $this->COMP_POINT,
                    'COMP_WORDS' => $this->COMP_WORDS,
                    'COMP_CURR' => $this->COMP_CURR,
                ], JSON_PRETTY_PRINT)
            ));
        }

        // Output generated values
        if ($input->getOption('verbose')) {
            $output->getErrorOutput()->writeln(sprintf(
                "\nComputed: <info>%s</info>",
                json_encode([
                    'shellCommand' => $this->shellCommand,
                    'symfonyCommand' => $this->symfonyCommand,
                    'shellCommandOptions' => implode(', ', array_keys($this->shellCommandOptions)),
                    'shellCommandArguments' => implode(', ', array_keys($this->shellCommandArguments)),
                    'symfonyCommandsAvailable' => implode(', ', array_keys($this->symfonyCommandsAvailable)),
                    'symfonyCommandOptions' => implode(', ', array_keys($this->symfonyCommandOptions ?? [])),
                    'symfonyCommandArguments' => implode(', ', array_keys($this->symfonyCommandArguments ?? [])),
                    'tokens' => $this->tokens,
                    'tokenIndex' => $this->tokenIndex,
                ], JSON_PRETTY_PRINT)
            ));
        }

        $suggestions = [];

        $currentToken = $this->tokens[$this->tokenIndex];
        // Token Type
        switch ($currentToken[2]) {
            case static::TOKEN_TYPE_APCMD:
            case static::TOKEN_TYPE_APARG:
            case static::TOKEN_TYPE_APOPT:
            case static::TOKEN_TYPE_SFCMD:
                $suggestions = array_merge(
                    $suggestions,
                    array_keys($this->shellCommandOptions),
                    array_keys($this->shellCommandArguments),
                    array_keys($this->symfonyCommandsAvailable)
                );
                break;
            case static::TOKEN_TYPE_SFARG:
            case static::TOKEN_TYPE_SFOPT:
                $suggestions = array_merge(
                    $suggestions,
                    array_keys($this->symfonyCommandArguments),
                    array_keys($this->symfonyCommandOptions)
                );
                break;
            default:
                $output->getErrorOutput()->writeln(sprintf(
                    "\nHow did we get here?"
                ));
                break;
        }

        $exitCode = 0;

        if ($needle = $currentToken[0]) {
            $suggestions = array_filter($suggestions, function ($str) use ($needle) {
                return strpos($str, $needle) === 0;
            });
            if(strpos($this->COMP_WORDBREAKS, $this->COMP_CURR)){
                $trim = $this->COMP_POINT - $currentToken[1];
                $suggestions = array_map(function ($suggestion) use ($trim, $output) {
                    return substr($suggestion, $trim);
                }, $suggestions);
            }
        }

        // If we get this far without a suggestion, throw an error to stop processing
        switch ($currentToken[0]) {
            case static::TOKEN_TYPE_APOPT:
            case static::TOKEN_TYPE_SFOPT:
                if (!count($suggestions)) {
                    $exitCode = 2;
                }
                break;
        }

        if (count($suggestions)) {
            echo implode(PHP_EOL, array_filter($suggestions)).PHP_EOL;
            //$exitCode = 0;
        } elseif ($exitCode === 0) {
            $exitCode = 1;
        }

        if ($input->getOption('verbose')) {
            $output->getErrorOutput()->writeln(sprintf(
                "\nFinal: <info>%s</info>",
                json_encode([
                    'suggesting' => implode(', ', $suggestions),
                    'suggesting#' => count($suggestions),
                    'exitCode' => $exitCode,
                    'time' => time(),
                ], JSON_PRETTY_PRINT)
            ));
        }
        return $exitCode;
    }

    protected function loadProperties(InputInterface $input)
    {
        // Bind all the command line options up
        $this->COMP_CWORD = (int)$input->getOption('COMP_CWORD');
        $this->COMP_LINE = $input->getOption('COMP_LINE');
        $this->COMP_POINT = (int)$input->getOption('COMP_POINT');
        $this->COMP_WORDBREAKS = $input->getOption('COMP_WORDBREAKS');
        $this->COMP_WORDS = $input->getOption('COMP_WORDS');
        $this->COMP_CURR = preg_replace('/^\'(.*)\'$/i', '$1', $input->getOption('COMP_CURR'));

        //$this->tokens = preg_split('/\s+/', $this->COMP_LINE, -1, PREG_SPLIT_OFFSET_CAPTURE);
        preg_match_all('/[^\s"\']+|"([^"]*)"|\'([^\']*)|\'/', $this->COMP_LINE, $this->tokens, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL);
        $this->tokens = $this->tokens[0];

        // The first parameter will be the shell command to work with
        if (isset($this->tokens[0]) && !$this->shellCommand) {
            $this->shellCommand = $this->tokens[0][0];
        }

        $json = null;
        exec($this->shellCommand.' --format=json', $json, $code);
        $data = json_decode(implode(PHP_EOL, $json), true);
        $this->symfonyCommandsAvailable = array_column($data['commands'], null, 'name');
        $this->shellCommandArguments = [];
        $this->shellCommandOptions = [];

        if (strlen($this->COMP_LINE) <= $this->COMP_POINT && $this->COMP_CURR === '') {
            $this->tokens[] = ['', $this->COMP_POINT];
        }

        $lastTokenType = static::TOKEN_TYPE_APCMD;
        foreach ($this->tokens as $i => $token) {
            $index = count($this->tokens[$i]);
            $this->tokens[$i][]=null;
            $this->tokens[$i][]=null;
            if ($this->COMP_POINT >= $token[1]) {
                $this->tokenIndex = $i;
            }

            $thisTokenType = static::TOKEN_TYPE_APCMD;
            if ($i === 0) {
                // The first one is the shell command
                $thisTokenType = static::TOKEN_TYPE_APCMD;
            } else {
                $value = $token[0];
                $position = $token[1];
                // is part of the base command
                if ($lastTokenType & static::TOKEN_TYPE_APCMD) {
                    $this->tokens[$i][$index+1] = false;
                    if (preg_match('/^-/', $value)) {
                        $thisTokenType = static::TOKEN_TYPE_APOPT;
                    } else {
                        if (in_array($value, array_keys($this->symfonyCommandsAvailable))) {
                            $thisTokenType = static::TOKEN_TYPE_SFCMD;
                            $this->symfonyCommand = $value;
                        } else {
                            $thisTokenType = static::TOKEN_TYPE_APARG;
                        }
                    }
                } else {
                    $this->tokens[$i][$index+1] = true;
                    if (preg_match('/^-/', $value)) {
                        $thisTokenType = static::TOKEN_TYPE_SFOPT;
                    } else {
                        if (in_array($value, array_keys($this->symfonyCommandsAvailable))) {
                            $thisTokenType = static::TOKEN_TYPE_SFCMD;
                            $this->symfonyCommand = $value;
                        } else {
                            $thisTokenType = static::TOKEN_TYPE_SFARG;
                        }
                    }
                }
            }
            $lastTokenType = $this->tokens[$i][$index] = $thisTokenType;
        }

        if ($this->symfonyCommand && isset($this->symfonyCommandsAvailable[$this->symfonyCommand])) {
            $data = $this->symfonyCommandsAvailable[$this->symfonyCommand];
            $this->symfonyCommandArguments = array_change_key_case(array_column($data['definition']['arguments'], null, 'name'), CASE_UPPER);
            $this->symfonyCommandOptions = array_column($data['definition']['options'], null, 'name');
        }

        $this->shellCommandOptions = $this->decorateOptions($this->shellCommandOptions, static::TOKEN_TYPE_APOPT);
        $this->symfonyCommandOptions = $this->decorateOptions($this->symfonyCommandOptions, static::TOKEN_TYPE_SFOPT);
    }

    protected function decorateOptions(array $options, int $type)
    {
        $output = [];
        foreach($this->getBlockedOptions($options, $type) as $key) {
            unset($options[$key]);
        }

        foreach ($options as $name => $config) {

            if ($config['accept_value']) {
                $output[$name.'='] = $config;
                if (isset($config['default'])) {
                    $output[$name.'='.$config['default']] = $config;
                }
            }
            if (!$config['is_value_required']) {
                $output[$name] = $config;
            }
        }
        return $output;
    }

    protected function getBlockedOptions(array $options, int $type) {
        $blocked = [];
        foreach($this->tokens as $i => $token){
            if(
                $this->tokenIndex !== $i &&
                $token[2] === $type &&
                isset($options[$token[0]]) &&
                !$options[$token[0]]['is_multiple']
            ){
                $blocked[] = $token[0];
            }
        }
        return $blocked;
    }
}

<?php

// src/Command/CreateUserCommand.php

namespace LiquidLight\SymfonyAutocomplete\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Install extends Command
{
    protected static $defaultName = 'install';

    protected const BIN_DIR = '/usr/local/bin/';

    protected const SCRIPT_DIR = '/etc/bash_completion.d/';

    protected const SOURCE_DIR = __DIR__.'/../../../';

    protected const SCRIPT = [
        'app' => [
            'symfony-completer.sh'
        ],
        'script' => [
            '00-symfony-completer-complete',
            'symfony-completer-composer',
        ],
        'typo3' => [
            'symfony-completer-typo3cms',
        ],
    ];

    protected function configure()
    {
        $this
            ->setDescription('Generated autocomplete values for a symfony console command.')
            ->setHelp('Generated autocomplete values for a symfony console command using the JSON formatted output it provides.')
            ->addOption('app', 'a', InputOption::VALUE_NONE, 'Use the application scripts.')
            ->addOption('script', 's', InputOption::VALUE_NONE, 'Use the base scripts.')
            ->addOption('typo3', 't', InputOption::VALUE_NONE, 'Use the typo3 scripts.')
            ->addOption('status', 'S', InputOption::VALUE_NONE, 'Generate commands to list status of all files.')
            ->addOption('purge', 'p', InputOption::VALUE_NONE, 'Generate purge commands')
            ->addOption('link', 'l', InputOption::VALUE_NONE, 'Generate link commands')
            ->addOption('exec', 'e', InputOption::VALUE_NONE, 'Perform the operations via shell_exec()');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $links = $this->getLinks($input, $output);

        $this->showStatus($links, $input, $output);

        $sh = array_merge(
            $this->getPurgeCommands($links, $input, $output),
            $this->getLinkCommands($links, $input, $output)
        );
        $this->printCommands($sh, $input, $output);
        $this->executeCommands($sh, $input, $output);

        return 0;
    }

    protected static function getLinks(InputInterface $input, OutputInterface $output)
    {
        $links = [];
        $output->writeln('');
        $output->writeln('<comment>Gathering links</comment>');
        if ($input->getOption('app')) {
            $output->writeln(' +app');
            foreach (static::SCRIPT['app'] as $file) {
                $links[realpath(static::SOURCE_DIR.$file)] = static::BIN_DIR.preg_replace('/\.sh$/', '', $file);
            }
        }
        if ($input->getOption('script')) {
            $output->writeln(' +script');
            foreach (static::SCRIPT['script'] as $file) {
                $links[realpath(static::SOURCE_DIR.'resources/'.$file)] = static::SCRIPT_DIR.$file;
            }
        }
        if ($input->getOption('typo3')) {
            $output->writeln(' +typo3');
            foreach (static::SCRIPT['typo3'] as $file) {
                $links[realpath(static::SOURCE_DIR.'resources/'.$file)] = static::SCRIPT_DIR.$file;
            }
        }
        if (!count($links)) {
            $output->writeln('-no script-');
        }
        return $links;
    }

    protected function showStatus(array $links, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('status')) {
            $output->writeln('');
            $output->writeln('<comment>File status</comment>');
            foreach ($links as $from => $to) {
                $output->writeln($this->fileStatus($from, $input, $output));
            }
            foreach ($links as $from => $to) {
                $output->writeln($this->fileStatus($to, $input, $output));
            }
        }
    }

    protected function fileStatus(string $path, InputInterface $input, OutputInterface $output)
    {
        $str = sprintf(
            '<%s>%s</%1$s>',
            file_exists($path) ? 'info' : 'error',
            $path
        );
        if (is_link($path)) {
            $str .= sprintf(
                ' -> <%s>%s</%1$s>',
                'info',
                readlink($path)
            );
        }
        return $str;
    }

    protected static function getPurgeCommands(array $links, InputInterface $input, OutputInterface $output)
    {
        $sh = [];
        if ($input->getOption('purge')) {
            foreach ($links as $from => $to) {
                $sh[] = "sudo rm $to";
            }
        }
        return $sh;
    }

    protected static function getLinkCommands(array $links, InputInterface $input, OutputInterface $output)
    {
        $sh = [];
        if ($input->getOption('link')) {
            foreach ($links as $from => $to) {
                $sh[] = "sudo ln -s $from $to";
            }
        }
        return $sh;
    }

    protected static function printCommands(array $sh, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<comment>Commands to perfrom</comment>');
        if (count($sh)) {
            $output->writeln(sprintf(
                '<info>%s</info>',
                ' '.trim(implode(PHP_EOL.' ', $sh))
            ));
        } else {
            $output->writeln('-no commands-');
        }
    }

    protected static function executeCommands(array $sh, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('exec') && count($sh)) {
            $output->writeln('');
            $output->writeln('<comment>Executing</comment>');
            foreach ($sh as $i => $cmd) {
                $output->writeln('<info> '.$cmd.'</info>');
                passthru($cmd);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\ask;
use function Termwind\render;

use XditnModule\XditnModule;

abstract class XditnModuleCommand extends Command
{
    protected $name = null;

    public function __construct()
    {
        parent::__construct();

        if (!property_exists($this, 'signature')
            && property_exists($this, 'name')
            && $this->name
        ) {
            $this->signature = $this->name.' {module}';
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->hasArgument('module') && !XditnModule::isModulePathExist(lcfirst($input->getArgument('module')))) {
            $this->error(sprintf('Module [%s] Not Found', $input->getArgument('module')));
            exit;
        }
    }

    /**
     * Ask for user input.
     */
    public function askFor(string $question, string|int|null $default = null, bool $isChoice = false): string|int|null
    {
        $_default = $default ? "<em class='pl-1 text-rose-600'>[$default]</em>" : '';

        $choice = $isChoice ? '<em class="bg-indigo-600 w-5 pl-1 ml-1 mr-1">Yes</em>OR<em class="bg-rose-600 w-4 pl-1 ml-1">No</em>' : '';

        $answer = ask(
            <<<HTML
            <div>
                <div class="px-1 bg-indigo-700">XditnModule</div>
                <em class="ml-1">
                    <em class="text-green-700">$question</em>
                    $_default
                    $choice
                    <em class="ml-1">:</em><em class="ml-1"></em>
                </em>
            </div>
HTML
        );

        $this->newLine();

        if ($default && !$answer) {
            return $default;
        }

        return $answer;
    }

    /**
     * Display info message.
     */
    public function info($string, $verbosity = null)
    {
        render(
            <<<HTML
            <div>
                <div class="px-1 bg-indigo-700">XditnModule</div>
                <em class="ml-1">
                    <em class="text-green-700">$string</em>
                </em>
            </div>
HTML
        );
    }

    /**
     * Display error message.
     */
    public function error($string, $verbosity = null)
    {
        render(
            <<<HTML
            <div>
                <div class="px-1 bg-indigo-700">XditnModule</div>
                <em class="ml-1">
                    <em class="text-rose-700">$string</em>
                </em>
            </div>
HTML
        );
    }
}

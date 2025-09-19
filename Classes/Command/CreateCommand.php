<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace T3thi\TranslationHandling\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use T3thi\TranslationHandling\Generator\Exception;
use T3thi\TranslationHandling\Generator\Generator;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Generate TCA for Styleguide backend (create)
 *
 * @internal
 */
#[AsCommand(
    name: 'translation-handling:create',
    description: 'Create page tree(s) with translation handling examples'
)]
final class CreateCommand extends Command
{
    public function __construct(
        private readonly Generator $generator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, 'Create page tree data (valid arguments are "fallback", "strict", "free" and "all")');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        switch ($input->getArgument('type')) {
            case 'fallback':
                $this->create('fallback', $output);
                break;

            case 'strict':
                $this->create('strict', $output);
                break;

            case 'free':
                $this->create('free', $output);
                break;

            case 'all':
                $this->create('fallback', $output);
                $this->create('strict', $output);
                $this->create('free', $output);
                break;

            default:
                $output->writeln('<error>Please specify a valid action. Choose "fallback", "strict", "free" or "all"</error>');
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function create(string $type, OutputInterface $output): void
    {
        $output->writeln($this->generator->create($type));
    }
}

<?php

declare(strict_types=1);

namespace App\Command;

use App\Testing\FixturesManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Load Doctrine Fixtures with sample data'
)]
final class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly FixturesManager $fixturesManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->fixturesManager->loadFixtures();
            $output->writeln('<info>Fixtures loaded successfully!</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to load fixtures: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
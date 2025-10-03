<?php

declare(strict_types=1);

namespace App\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FixturesManager
{
    private ORMExecutor $executor;
    private Loader $loader;
    private ORMPurger $purger;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->loader = new Loader();
        $this->purger = new ORMPurger($entityManager);
        $this->executor = new ORMExecutor($entityManager, $this->purger);

        $this->loadFixturesFromDirectory();
    }

    private function loadFixturesFromDirectory(): void
    {
        $fixturesPath = __DIR__ . '/../DataFixtures';
        
        if (!is_dir($fixturesPath)) {
            return;
        }

        $files = glob($fixturesPath . '/*.php');
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = "App\\DataFixtures\\{$className}";
            
            if (class_exists($fullClassName)) {
                $this->loader->addFixture(new $fullClassName());
            }
        }
    }

    public function loadFixtures(bool $append = false): void
    {
        $this->executor->execute($this->loader->getFixtures(), $append);
    }
}

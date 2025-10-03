<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Contract\SearchServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'search:reindex',
    description: 'Reindex all products in search service'
)]
final class ReindexCommand extends Command
{
    public function __construct(
        private readonly ProductRepository      $productRepository,
        private readonly SearchServiceInterface $searchService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->searchService->isAvailable()) {
            $output->writeln('<error>Search service is not available</error>');
            return Command::FAILURE;
        }

        /** @var Product[] $products */
        $products = $this->productRepository->findAll();

        if (empty($products)) {
            $output->writeln('<info>No products to index</info>');
            return Command::SUCCESS;
        }

        $indexed = 0;
        foreach ($products as $product) {
            $this->searchService->index($product->toArray());
            $indexed++;
        }

        $output->writeln("<info>Indexed $indexed products</info>");
        return Command::SUCCESS;
    }
}

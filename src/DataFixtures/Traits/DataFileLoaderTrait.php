<?php

declare(strict_types=1);

namespace App\DataFixtures\Traits;

use RuntimeException;
use function sprintf;

trait DataFileLoaderTrait
{
    /**
     * Load data from CSV file if it exists
     *
     * @param string $filename Name of the CSV file
     * @param string|null $basePath Base path for data files
     * @param bool $hasHeader Whether the first row contains headers
     * @return array Array of data or empty array if file doesn't exist
     */
    private function loadDataFromCsvFile(string $filename, ?string $basePath = null, bool $hasHeader = true): array
    {
        $basePath = $basePath ?? (__DIR__ . '/../../../database/data/');
        $filePath = rtrim($basePath, '/') . '/' . $filename;

        if (!is_file($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open file "%s"', $filePath));
        }

        $data = [];
        $headers = [];
        $isFirstRow = true;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($isFirstRow && $hasHeader) {
                $headers = $row;
                $isFirstRow = false;
                continue;
            }

            if ($hasHeader && !empty($headers)) {
                $data[] = array_combine($headers, $row);
            } else {
                $data[] = $row;
            }
        }

        fclose($handle);

        return $data;
    }
}
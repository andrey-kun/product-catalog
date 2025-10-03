<?php

declare(strict_types=1);

namespace migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251001160201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database schema';
    }

    public function up(Schema $schema): void
    {
        $this->createCategoriesTable();
        $this->createProductsTable();
        $this->createProductCategoriesTable();
    }

    private function createCategoriesTable(): void
    {
        $sql = "
            CREATE TABLE categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                
                UNIQUE KEY idx_categories_name_unique (name),
                KEY idx_categories_created_at (created_at)
            ) ENGINE=InnoDB 
              DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci 
              COMMENT='Product categories'
        ";

        $this->addSql($sql);
    }

    private function createProductsTable(): void
    {
        $sql = "
            CREATE TABLE products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                inn VARCHAR(12) NOT NULL,
                barcode VARCHAR(13) NOT NULL,
                description TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                
                UNIQUE KEY idx_products_inn_unique (inn),
                UNIQUE KEY idx_products_barcode_unique (barcode),
                UNIQUE KEY idx_products_inn_barcode_unique (inn, barcode),
                KEY idx_products_name (name),
                KEY idx_products_created_at (created_at),
                KEY idx_products_updated_at (updated_at)
            ) ENGINE=InnoDB 
              DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci 
              COMMENT='Products catalog'
        ";

        $this->addSql($sql);
    }

    private function createProductCategoriesTable(): void
    {
        $sql = "
            CREATE TABLE product_categories (
                product_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                
                PRIMARY KEY (product_id, category_id),
                KEY idx_product_categories_product_id (product_id),
                KEY idx_product_categories_category_id (category_id),
                KEY idx_product_categories_created_at (created_at),
                
                CONSTRAINT fk_product_categories_product_id 
                    FOREIGN KEY (product_id) REFERENCES products (id) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    
                CONSTRAINT fk_product_categories_category_id 
                    FOREIGN KEY (category_id) REFERENCES categories (id) 
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB 
              DEFAULT CHARSET=utf8mb4 
              COLLATE=utf8mb4_unicode_ci 
              COMMENT='Many-to-many relationship between products and categories'
        ";

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS product_categories');
        $this->addSql('DROP TABLE IF EXISTS products');
        $this->addSql('DROP TABLE IF EXISTS categories');
    }
}
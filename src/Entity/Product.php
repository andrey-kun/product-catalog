<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use App\Repository\ProductRepository;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_products_inn', columns: ['inn'])]
#[ORM\Index(name: 'idx_products_barcode', columns: ['barcode'])]
#[ORM\Index(name: 'idx_products_name', columns: ['name'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    public ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', length: 12, unique: true)]
    public string $inn;

    #[ORM\Column(type: 'string', length: 13, unique: true)]
    public string $barcode;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public DateTime $updatedAt;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'product_categories')]
    public Collection $categories;

    public function __construct(
        string $name,
        string $inn,
        string $barcode,
        ?string $description = null
    ) {
        $this->name = $name;
        $this->inn = $inn;
        $this->barcode = $barcode;
        $this->description = $description;
        $this->categories = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    final public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    final public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }

        if (empty($this->inn)) {
            $errors[] = 'INN is required';
        } elseif (!$this->isValidInn($this->inn)) {
            $errors[] = 'Invalid INN format';
        }

        if (empty($this->barcode)) {
            $errors[] = 'Barcode is required';
        } elseif (!$this->isValidBarcode($this->barcode)) {
            $errors[] = 'Invalid barcode format';
        }

        return $errors;
    }

    private function isValidInn(string $inn): bool
    {
        return preg_match('/^\d{10}$|^\d{12}$/', $inn) === 1;
    }

    private function isValidBarcode(string $barcode): bool
    {
        return preg_match('/^\d{13}$/', $barcode) === 1;
    }

    final public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'inn' => $this->inn,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'categories' => array_map(fn($cat) => $cat->toArray(), $this->categories->toArray()),
        ];
    }

    public function toArraySimple(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'inn' => $this->inn,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
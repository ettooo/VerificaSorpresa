<?php

declare(strict_types=1);

namespace App\Domain\Supplier;

interface SupplierRepository
{
    /**
     * @return Supplier[]
     */
    public function findAll(): array;

    /**
     * @param string $id
     * @return Supplier
     */
    public function findSupplierOfId(string $id): Supplier;
    
    /**
     * @param string $fid
     * @param string $pid
     * @return array|null
     */
    public function findInCatalog(string $fid, string $pid): ?array;

    /**
     * @param string $fid
     * @param string $pid
     * @param float $costo
     * @return void
     */
    public function updateCatalog(string $fid, string $pid, float $costo): void;

    /**
     * @param string $fid
     * @param string $pid
     * @return void
     */
    public function removeFromCatalog(string $fid, string $pid): void;
}

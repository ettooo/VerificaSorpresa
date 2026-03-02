<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Supplier;

use App\Domain\Supplier\Supplier;
use App\Domain\Supplier\SupplierRepository;
use PDO;
use RuntimeException;

class PDOSupplierRepository implements SupplierRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(): array
    {
        $stmt = $this->connection->query("SELECT fid, fnome, indirizzo FROM Fornitori");
        $suppliers = [];
        while ($row = $stmt->fetch()) {
            $suppliers[] = new Supplier($row['fid'], $row['fnome'], $row['indirizzo']);
        }
        return $suppliers;
    }

    public function findSupplierOfId(string $id): Supplier
    {
        $stmt = $this->connection->prepare("SELECT fid, fnome, indirizzo FROM Fornitori WHERE fid = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException("Supplier not found");
        }

        return new Supplier($row['fid'], $row['fnome'], $row['indirizzo']);
    }

    public function findInCatalog(string $fid, string $pid): ?array
    {
        $stmt = $this->connection->prepare("SELECT fid, pid, costo FROM Catalogo WHERE fid = ? AND pid = ?");
        $stmt->execute([$fid, $pid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateCatalog(string $fid, string $pid, float $costo): void
    {
        $existing = $this->findInCatalog($fid, $pid);
        if ($existing) {
            $stmt = $this->connection->prepare("UPDATE Catalogo SET costo = ? WHERE fid = ? AND pid = ?");
            $stmt->execute([$costo, $fid, $pid]);
        } else {
            $stmt = $this->connection->prepare("INSERT INTO Catalogo (fid, pid, costo) VALUES (?, ?, ?)");
            $stmt->execute([$fid, $pid, $costo]);
        }
    }

    public function removeFromCatalog(string $fid, string $pid): void
    {
        $stmt = $this->connection->prepare("DELETE FROM Catalogo WHERE fid = ? AND pid = ?");
        $stmt->execute([$fid, $pid]);
    }
}

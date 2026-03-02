<?php

declare(strict_types=1);

namespace App\Application\Actions\Supplier;

use Psr\Http\Message\ResponseInterface as Response;

class ListSuppliersAction extends SupplierAction
{
    protected function action(): Response
    {
        $suppliers = $this->supplierRepository->findAll();
        $this->logger->info("Suppliers list was viewed.");
        return $this->respondWithData($suppliers);
    }
}

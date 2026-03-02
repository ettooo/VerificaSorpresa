<?php

declare(strict_types=1);

namespace App\Application\Actions\Supplier;

use Psr\Http\Message\ResponseInterface as Response;

class UpdateCatalogAction extends SupplierAction
{
    protected function action(): Response
    {
        $data = $this->getFormData();
        $fid = $data['fid'];
        $pid = $data['pid'];
        $costo = (float) $data['costo'];

        $this->supplierRepository->updateCatalog($fid, $pid, $costo);

        $this->logger->info("Catalog entry for supplier `$fid` and piece `$pid` was updated/created.");

        return $this->respondWithData(['success' => true]);
    }
}

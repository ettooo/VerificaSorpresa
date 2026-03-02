<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use App\Domain\Piece\Piece;
use Psr\Http\Message\ResponseInterface as Response;

class CreatePieceAction extends PieceAction
{
    protected function action(): Response
    {
        $data = $this->getFormData();
        $piece = new Piece($data['pid'], $data['pnome'], $data['colore']);
        
        $this->pieceRepository->save($piece);

        $this->logger->info("Piece `${data['pid']}` was created.");

        return $this->respondWithData($piece, 201);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use Psr\Http\Message\ResponseInterface as Response;

class ListPiecesAction extends PieceAction
{
    protected function action(): Response
    {
        $params = $this->request->getQueryParams();
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;
        $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

        $pieces = $this->pieceRepository->findAll($limit, $offset);
        $total = $this->pieceRepository->countAll();

        $this->logger->info("Pieces list was viewed.");

        return $this->respondWithData([
            'items' => $pieces,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }
}

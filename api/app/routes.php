<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {

    $json = function (Response $response, $data) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    };

    // Health check
    $app->get('/health', function (Request $request, Response $response) use ($json) {
        return $json($response, ["ok" => true]);
    });

    // Q1
    $app->get('/q1', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT p.pnome
            FROM Pezzi p
            JOIN Catalogo c ON c.pid = p.pid
        ");
        return $json($response, $stmt->fetchAll());
    });




 // Q2 corretta
$app->get('/q2', function ($request, $response) use ($json) {
    $pdo = $this->get(\PDO::class);

    $stmt = $pdo->query("
        SELECT f.fnome
        FROM Fornitori f
        JOIN Catalogo c ON c.fid = f.fid
        GROUP BY f.fid
        HAVING COUNT(DISTINCT c.pid) =
            (SELECT COUNT(DISTINCT pid) FROM Catalogo)
    ");

    return $json($response, $stmt->fetchAll());
});

    // Q3 (parametro colore)
    $app->get('/q3', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $colore = $request->getQueryParams()['colore'] ?? 'rosso';

        $stmt = $pdo->prepare("
            SELECT f.fnome
            FROM Fornitori f
            JOIN Catalogo c ON c.fid = f.fid
            JOIN Pezzi p ON p.pid = c.pid
            WHERE p.colore = :colore
            GROUP BY f.fid
            HAVING COUNT(DISTINCT p.pid) =
                (SELECT COUNT(*) FROM Pezzi WHERE colore = :colore)
        ");

        $stmt->execute(['colore' => $colore]);
        return $json($response, $stmt->fetchAll());
    });

    // Q4 (parametro nome fornitore)
    $app->get('/q4', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $nome = $request->getQueryParams()['nome'] ?? 'Acme';

        $stmt = $pdo->prepare("
            SELECT p.pnome
            FROM Pezzi p
            JOIN Catalogo c ON c.pid = p.pid
            JOIN Fornitori f ON f.fid = c.fid
            GROUP BY p.pid
            HAVING SUM(f.fnome = :nome) > 0
               AND COUNT(DISTINCT c.fid) = 1
        ");

        $stmt->execute(['nome' => $nome]);
        return $json($response, $stmt->fetchAll());
    });

    // Q5
    $app->get('/q5', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT fid
            FROM Catalogo c1
            WHERE costo > (
                SELECT AVG(costo)
                FROM Catalogo c2
                WHERE c2.pid = c1.pid
            )
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q6
    $app->get('/q6', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT p.pid, p.pnome, f.fnome, c.costo
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            JOIN Fornitori f ON f.fid = c.fid
            WHERE c.costo = (
                SELECT MAX(c2.costo)
                FROM Catalogo c2
                WHERE c2.pid = c.pid
            )
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q7
    $app->get('/q7', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            GROUP BY c.fid
            HAVING SUM(p.colore <> 'rosso') = 0
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q8
    $app->get('/q8', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            GROUP BY c.fid
            HAVING SUM(p.colore='rosso')>0
               AND SUM(p.colore='verde')>0
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q9
    $app->get('/q9', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            WHERE p.colore IN ('rosso','verde')
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q10
    $app->get('/q10', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT pid
            FROM Catalogo
            GROUP BY pid
            HAVING COUNT(DISTINCT fid) >= 2
        ");
        return $json($response, $stmt->fetchAll());
    });


    // Q11 - tutti i pezzi (anche senza fornitore)
$app->get('/q11', function ($request, $response) use ($json) {
    $pdo = $this->get(\PDO::class);

    $stmt = $pdo->query("
        SELECT pid, pnome, colore
        FROM Pezzi
        ORDER BY pnome
    ");

    return $json($response, $stmt->fetchAll());
});

};
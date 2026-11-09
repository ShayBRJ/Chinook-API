<?php
// GET /artistas.php  ->  los 5 artistas con más canciones
require __DIR__ . '/conexion.php';

$datos = $pdo->query("SELECT ar.ArtistId AS id, ar.Name AS nombre, COUNT(*) AS canciones
    FROM Track t
    JOIN Album a   ON a.AlbumId   = t.AlbumId
    JOIN Artist ar ON ar.ArtistId = a.ArtistId
    GROUP BY ar.ArtistId, ar.Name
    ORDER BY canciones DESC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

responder(['total' => count($datos), 'datos' => $datos]);

<?php
//////////////////////////
// bestandsnaam: db.php
// omschrijving: Database connectie (PDO) voor Veel Auto Planning
//////////////////////////

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'klantonderhoudsysteem';
        $user = 'root';
        $pass = '';

        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}

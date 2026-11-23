
<?php
//////////////////////////
// bestandsnaam: db.php
// omschrijving: Database connectie (PDO) voor Veel Auto Planning
//////////////////////////

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Lees de .env.local configuratie
        $env = parse_ini_file(__DIR__ . '/../.env.local');

        if ($env === false) {
            throw new RuntimeException('.env.local bestand niet gevonden of kan niet worden gelezen.');
        }

        // Haal databasegegevens op
        $host = $env['DB_HOST'] ?? 'localhost';
        $user = $env['DB_USER'] ?? 'root';
        $pass = $env['DB_PASSWORD'] ?? '';
        $dbname = $env['DB_NAME'] ?? 'klantonderhoudsysteem';
        $port = $env['DB_PORT'] ?? '3306';

        // Controleer verplichte configuratie
        if ($dbname === '' || $user === '') {
            throw new RuntimeException('Databaseconfiguratie ontbreekt in .env.local');
        }

        // Maak PDO connectie
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $pdo;
}

<?php
//////////////////////////
// bestandsnaam: export_facturen.php
// omschrijving: CSV-export van facturatie overzicht
//////////////////////////

require __DIR__ . '/includes/db.php';

$db = getDb();

$rijen = $db->query("
    SELECT k.naam AS klant_naam, r.ophaaladres, r.bestemming, r.datum_tijd,
           COALESCE(f.bedrag, r.prijs, 0) AS bedrag,
           CASE WHEN f.id IS NOT NULL THEN 'Gefactureerd' ELSE 'Nog niet gefactureerd' END AS factuurstatus,
           f.factuurnummer
    FROM ritten r
    JOIN klanten k ON k.id = r.klant_id
    LEFT JOIN facturen f ON f.rit_id = r.id
    WHERE r.status = 'voltooid'
    ORDER BY r.datum_tijd DESC
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="facturatie-overzicht.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Klant', 'Rit', 'Datum', 'Bedrag', 'Factuurstatus', 'Factuurnummer'], ';');

foreach ($rijen as $rij) {
    fputcsv($out, [
        $rij['klant_naam'],
        $rij['ophaaladres'] . ' -> ' . $rij['bestemming'],
        (new DateTime($rij['datum_tijd']))->format('d-m-Y'),
        number_format((float) $rij['bedrag'], 2, ',', ''),
        $rij['factuurstatus'],
        $rij['factuurnummer'] ?? '',
    ], ';');
}

fclose($out);

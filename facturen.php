<?php
//////////////////////////
// bestandsnaam: facturen.php
// omschrijving: facturen page voor Veel Auto Planning
// auteur: Strahinja Zoranovic
// datum: 14/09/2026
//////////////////////////

session_start();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$db = getDb();

// --- Factuur genereren (POST) -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['genereer_factuur'])) {
    $ritId = (int) $_POST['rit_id'];

    $stmt = $db->prepare('SELECT prijs FROM ritten WHERE id = ?');
    $stmt->execute([$ritId]);
    $rit = $stmt->fetch();

    if ($rit) {
        $volgnummer = (int) $db->query('SELECT COUNT(*) FROM facturen')->fetchColumn() + 1001;
        $factuurnummer = 'F-' . $volgnummer;
        $bedrag = $rit['prijs'] ?? 0;

        $stmt = $db->prepare("
            INSERT INTO facturen (rit_id, factuurnummer, bedrag, status, factuurdatum)
            VALUES (?, ?, ?, 'gefactureerd', CURDATE())
        ");
        $stmt->execute([$ritId, $factuurnummer, $bedrag]);
        $_SESSION['flash'] = "Factuur {$factuurnummer} aangemaakt.";
    }

    header('Location: facturen.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// --- Filter ---------------------------------------------------------------
$filters = [
    'alle' => 'Alle ritten',
    'gefactureerd' => 'Gefactureerd',
    'nog_niet' => 'Nog niet gefactureerd',
];
$actiefFilter = $_GET['status'] ?? 'alle';
if (!array_key_exists($actiefFilter, $filters)) {
    $actiefFilter = 'alle';
}

// --- Statistieken (deze maand) --------------------------------------------
$voltooideRittenMaand = (int) $db->query("
    SELECT COUNT(*) FROM ritten
    WHERE status = 'voltooid' AND MONTH(datum_tijd) = MONTH(CURDATE()) AND YEAR(datum_tijd) = YEAR(CURDATE())
")->fetchColumn();

$gefactureerdMaand = (int) $db->query("
    SELECT COUNT(*) FROM facturen
    WHERE MONTH(factuurdatum) = MONTH(CURDATE()) AND YEAR(factuurdatum) = YEAR(CURDATE())
")->fetchColumn();

$nogTeFactureren = (int) $db->query("
    SELECT COUNT(*) FROM ritten r
    LEFT JOIN facturen f ON f.rit_id = r.id
    WHERE r.status = 'voltooid' AND f.id IS NULL
      AND MONTH(r.datum_tijd) = MONTH(CURDATE()) AND YEAR(r.datum_tijd) = YEAR(CURDATE())
")->fetchColumn();

$totaalbedragMaand = (float) $db->query("
    SELECT COALESCE(SUM(bedrag), 0) FROM facturen
    WHERE MONTH(factuurdatum) = MONTH(CURDATE()) AND YEAR(factuurdatum) = YEAR(CURDATE())
")->fetchColumn();

// --- Voltooide ritten + factuurstatus --------------------------------------
$sql = "
    SELECT r.id AS rit_id, r.ophaaladres, r.bestemming, r.datum_tijd, r.prijs,
           k.naam AS klant_naam,
           f.factuurnummer, f.bedrag AS factuur_bedrag, f.status AS factuur_status
    FROM ritten r
    JOIN klanten k ON k.id = r.klant_id
    LEFT JOIN facturen f ON f.rit_id = r.id
    WHERE r.status = 'voltooid'
";

if ($actiefFilter === 'gefactureerd') {
    $sql .= ' AND f.id IS NOT NULL';
} elseif ($actiefFilter === 'nog_niet') {
    $sql .= ' AND f.id IS NULL';
}

$sql .= ' ORDER BY r.datum_tijd DESC';

$rijen = $db->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Facturatie overzicht - Veel Auto Planning</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/facturen.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside>
        <div class="brand">
            Veel Auto
            <span>Planning</span>
        </div>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="ritten.php">Alle ritten</a>
            <a href="chauffeur.php">Chauffeurs</a>
            <a href="facturen.php" class="active">Facturatie</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main>

        <div class="page-header">
            <div>
                <h1>Facturatie overzicht</h1>
                <p>Voltooide ritten en factuurstatus</p>
            </div>

            <a class="export-button" href="export_facturen.php">
                Exporteren
            </a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= e($flash) ?></div>
        <?php endif; ?>

        <!-- FILTERS -->
        <div class="filters">
            <?php foreach ($filters as $key => $label): ?>
                <a href="?status=<?= e($key) ?>" class="filter<?= $actiefFilter === $key ? ' active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- STATISTICS -->
        <section class="stats">

            <div class="stat-card">
                <strong><?= $voltooideRittenMaand ?></strong>
                <span>Voltooide ritten (deze maand)</span>
            </div>

            <div class="stat-card green">
                <strong><?= $gefactureerdMaand ?></strong>
                <span>Automatisch gefactureerd</span>
            </div>

            <div class="stat-card orange">
                <strong><?= $nogTeFactureren ?></strong>
                <span>Nog te factureren</span>
            </div>

            <div class="stat-card">
                <strong>€<?= number_format($totaalbedragMaand, 2, ',', '.') ?></strong>
                <span>Totaalbedrag deze maand</span>
            </div>
        </section>

        <!-- RIDES TABLE -->
        <section class="table-card">

            <div class="table-title">
                Voltooide ritten
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Klant</th>
                            <th>Rit</th>
                            <th>Datum</th>
                            <th>Bedrag</th>
                            <th>Factuurstatus</th>
                            <th>Factuurnummer</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rijen)): ?>
                        <tr>
                            <td colspan="6">Geen voltooide ritten gevonden.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($rijen as $rij): ?>
                        <tr>
                            <td><?= e($rij['klant_naam']) ?></td>
                            <td><?= e($rij['ophaaladres']) ?> → <?= e($rij['bestemming']) ?></td>
                            <td><?= e((new DateTime($rij['datum_tijd']))->format('d-m-Y')) ?></td>
                            <td class="amount">€<?= number_format((float) ($rij['factuur_bedrag'] ?? $rij['prijs'] ?? 0), 2, ',', '.') ?></td>
                            <td>
                                <?php if ($rij['factuurnummer']): ?>
                                    <span class="status invoiced">Gefactureerd</span>
                                <?php else: ?>
                                    <span class="status not-invoiced">Nog niet gefactureerd</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rij['factuurnummer']): ?>
                                    <?= e($rij['factuurnummer']) ?>
                                <?php else: ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="rit_id" value="<?= (int) $rij['rit_id'] ?>">
                                        <button type="submit" name="genereer_factuur" value="1" class="btn btn-primary">Genereer factuur</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>

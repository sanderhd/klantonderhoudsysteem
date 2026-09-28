<?php
//////////////////////////
// bestandsnaam: ritten.php
// omschrijving: ritten page voor Veel Auto Planning
// auteur: Strahinja Zoranovic
// datum: 14/09/2026
//////////////////////////

session_start();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$db = getDb();

// --- Rit toewijzen afhandelen (POST) ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rit'])) {
    $ritId = (int) $_POST['rit_id'];
    $chauffeurId = (int) $_POST['chauffeur_id'];

    $stmt = $db->prepare('SELECT naam FROM chauffeurs WHERE id = ?');
    $stmt->execute([$chauffeurId]);
    $chauffeur = $stmt->fetch();

    if ($chauffeur) {
        $stmt = $db->prepare("UPDATE ritten SET chauffeur_id = ?, status = 'toegewezen' WHERE id = ?");
        $stmt->execute([$chauffeurId, $ritId]);
        $_SESSION['flash'] = "Rit toegewezen aan {$chauffeur['naam']}.";
    }

    header('Location: ritten.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// --- Filter -------------------------------------------------------------
$filters = [
    'alle' => 'Alle ritten',
    'nieuw' => 'Nieuw',
    'toegewezen' => 'Toegewezen',
    'voltooid' => 'Voltooid',
];
$actiefFilter = $_GET['status'] ?? 'alle';
if (!array_key_exists($actiefFilter, $filters)) {
    $actiefFilter = 'alle';
}

// --- Statistieken ------------------------------------------------------
$rittenDezeWeek = (int) $db->query("SELECT COUNT(*) FROM ritten WHERE YEARWEEK(datum_tijd, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn();
$nogNietToegewezen = (int) $db->query("SELECT COUNT(*) FROM ritten WHERE status = 'nieuw'")->fetchColumn();
$toegewezenTotaal = (int) $db->query("SELECT COUNT(*) FROM ritten WHERE status IN ('toegewezen', 'onderweg')")->fetchColumn();
$voltooidTotaal = (int) $db->query("SELECT COUNT(*) FROM ritten WHERE status = 'voltooid'")->fetchColumn();

// --- Rittenlijst ---------------------------------------------------------
$sql = "
    SELECT r.id, r.ophaaladres, r.bestemming, r.datum_tijd, r.aantal_personen, r.status,
           k.naam AS klant_naam, c.naam AS chauffeur_naam
    FROM ritten r
    JOIN klanten k ON k.id = r.klant_id
    LEFT JOIN chauffeurs c ON c.id = r.chauffeur_id
";

if ($actiefFilter === 'toegewezen') {
    $sql .= " WHERE r.status IN ('toegewezen', 'onderweg')";
} elseif ($actiefFilter !== 'alle') {
    $sql .= ' WHERE r.status = ' . $db->quote($actiefFilter);
}

$sql .= ' ORDER BY r.datum_tijd DESC';

$ritten = $db->query($sql)->fetchAll();

// --- Toewijzen-paneel voorbereiden --------------------------------------
$toewijzenRit = null;
$beschikbareChauffeurs = [];

if (isset($_GET['toewijzen'])) {
    $stmt = $db->prepare("
        SELECT r.id, r.ophaaladres, r.bestemming, r.datum_tijd, r.aantal_personen, r.chauffeur_id,
               k.naam AS klant_naam
        FROM ritten r
        JOIN klanten k ON k.id = r.klant_id
        WHERE r.id = ?
    ");
    $stmt->execute([(int) $_GET['toewijzen']]);
    $toewijzenRit = $stmt->fetch();

    if ($toewijzenRit) {
        $beschikbareChauffeurs = $db->query("
            SELECT id, naam, status, beschikbaar_vanaf, rating
            FROM chauffeurs
            WHERE status != 'offline'
            ORDER BY status ASC, rating DESC
        ")->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Alle ritten - Veel Auto Planning</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/ritten.css">
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
            <a href="ritten.php" class="active">Alle ritten</a>
            <a href="chauffeur.php">Chauffeurs</a>
            <a href="facturen.php">Facturatie</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main>

        <div class="page-header">
            <div>
                <h1>Alle ritten</h1>
                <p>Overzicht van alle ritaanvragen en planning</p>
            </div>
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
                <strong><?= $rittenDezeWeek ?></strong>
                <span>Ritten deze week</span>
            </div>

            <div class="stat-card orange">
                <strong><?= $nogNietToegewezen ?></strong>
                <span>Nog niet toegewezen</span>
            </div>

            <div class="stat-card">
                <strong><?= $toegewezenTotaal ?></strong>
                <span>Toegewezen</span>
            </div>

            <div class="stat-card green">
                <strong><?= $voltooidTotaal ?></strong>
                <span>Voltooid</span>
            </div>
        </section>

        <!-- RIDES TABLE -->
        <section class="table-card">

            <div class="table-title">
                Ritten
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Klant</th>
                            <th>Route</th>
                            <th>Datum / Tijd</th>
                            <th>Pers.</th>
                            <th>Status</th>
                            <th>Actie</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($ritten)): ?>
                        <tr>
                            <td colspan="6">Geen ritten gevonden.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($ritten as $rit): ?>
                        <tr>
                            <td><?= e($rit['klant_naam']) ?></td>
                            <td><?= e($rit['ophaaladres']) ?> → <?= e($rit['bestemming']) ?></td>
                            <td><?= e(formatRitDatum($rit['datum_tijd'])) ?></td>
                            <td><?= (int) $rit['aantal_personen'] ?></td>
                            <td><?= statusBadge($rit['status'], $rit['chauffeur_naam']) ?></td>
                            <td>
                                <?php if ($rit['status'] === 'nieuw'): ?>
                                    <a class="btn btn-primary" href="?status=<?= e($actiefFilter) ?>&toewijzen=<?= (int) $rit['id'] ?>">Toewijzen</a>
                                <?php elseif (in_array($rit['status'], ['toegewezen', 'onderweg'], true)): ?>
                                    <a class="btn btn-outline" href="?status=<?= e($actiefFilter) ?>&toewijzen=<?= (int) $rit['id'] ?>">Bewerken</a>
                                <?php else: ?>
                                    <span class="btn btn-outline" style="opacity:.5;cursor:default;">Bekijken</span>
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

<?php if ($toewijzenRit): ?>
<!-- RIT TOEWIJZEN PANEEL -->
<div class="panel-overlay open" id="panelOverlay"></div>
<aside class="assign-panel open" id="assignPanel">
    <form method="post">
        <div class="assign-panel-header">
            <h2>Rit toewijzen</h2>
            <a class="panel-close" href="?status=<?= e($actiefFilter) ?>">✕</a>
        </div>

        <div class="ride-summary">
            <span class="summary-label">Ritgegevens</span>
            <div class="summary-row"><span>Klant</span><strong><?= e($toewijzenRit['klant_naam']) ?></strong></div>
            <div class="summary-row"><span>Route</span><strong><?= e($toewijzenRit['ophaaladres']) ?> → <?= e($toewijzenRit['bestemming']) ?></strong></div>
            <div class="summary-row"><span>Datum / tijd</span><strong><?= e(formatRitDatum($toewijzenRit['datum_tijd'])) ?></strong></div>
            <div class="summary-row"><span>Personen</span><strong><?= (int) $toewijzenRit['aantal_personen'] ?></strong></div>
        </div>

        <input type="hidden" name="rit_id" value="<?= (int) $toewijzenRit['id'] ?>">

        <p class="driver-picker-label">Kies een beschikbare chauffeur</p>

        <div class="driver-list">
            <?php foreach ($beschikbareChauffeurs as $c): ?>
            <label class="driver-item<?= $c['id'] == $toewijzenRit['chauffeur_id'] ? ' selected' : '' ?>">
                <span class="avatar"></span>
                <span class="driver-info">
                    <strong><?= e($c['naam']) ?></strong>
                    <small><?= $c['status'] === 'rijdt'
                        ? 'Rijdt momenteel · vrij om ' . e((new DateTime($c['beschikbaar_vanaf']))->format('H:i'))
                        : 'Beschikbaar · ' . number_format((float) $c['rating'], 1, ',', '') . ' ★' ?></small>
                </span>
                <input type="radio" name="chauffeur_id" value="<?= (int) $c['id'] ?>" <?= $c['id'] == $toewijzenRit['chauffeur_id'] ? 'checked' : '' ?>>
            </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="assign_rit" value="1" class="btn btn-primary btn-block">Bevestig toewijzing</button>
    </form>
</aside>
<?php endif; ?>

</body>
</html>

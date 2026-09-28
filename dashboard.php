<?php
//////////////////////////
// bestandsnaam: dashboard.php
// omschrijving: Dashboard page voor Veel Auto Planning
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

    header('Location: dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// --- Statistieken ------------------------------------------------------
$nieuweAanvragen = (int) $db->query("SELECT COUNT(*) FROM ritten WHERE status = 'nieuw'")->fetchColumn();

$toegewezenVandaag = (int) $db->query("
    SELECT COUNT(*) FROM ritten
    WHERE status IN ('toegewezen', 'onderweg') AND DATE(datum_tijd) = CURDATE()
")->fetchColumn();

$actieveChauffeurs = (int) $db->query("SELECT COUNT(*) FROM chauffeurs WHERE status IN ('beschikbaar', 'rijdt')")->fetchColumn();
$totaalChauffeurs = (int) $db->query('SELECT COUNT(*) FROM chauffeurs')->fetchColumn();

$voltooidVandaag = (int) $db->query("
    SELECT COUNT(*) FROM ritten
    WHERE status = 'voltooid' AND DATE(datum_tijd) = CURDATE()
")->fetchColumn();

// --- Ritaanvragen & planning (openstaande ritten) -----------------------
$ritten = $db->query("
    SELECT r.id, r.ophaaladres, r.bestemming, r.datum_tijd, r.aantal_personen, r.status,
           k.naam AS klant_naam, c.naam AS chauffeur_naam
    FROM ritten r
    JOIN klanten k ON k.id = r.klant_id
    LEFT JOIN chauffeurs c ON c.id = r.chauffeur_id
    WHERE r.status IN ('nieuw', 'toegewezen', 'onderweg')
    ORDER BY r.datum_tijd ASC
    LIMIT 10
")->fetchAll();

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

    <title>Dashboard - Veel Auto Planning</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="ritten.php">Alle ritten</a>
            <a href="chauffeur.php">Chauffeurs</a>
            <a href="facturen.php">Facturatie</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main>

        <div class="page-header">
            <div>
                <h1>Planningsoverzicht</h1>
                <p><?= e((new DateTime())->format('l j F Y')) ?></p>
            </div>

            <div class="searchbox">
                <input type="text" placeholder="🔍 Zoek op klant of adres">
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= e($flash) ?></div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <section class="stats">

            <div class="stat-card">
                <strong><?= $nieuweAanvragen ?></strong>
                <span>Nieuwe aanvragen</span>
            </div>

            <div class="stat-card">
                <strong><?= $toegewezenVandaag ?></strong>
                <span>Toegewezen vandaag</span>
            </div>

            <div class="stat-card">
                <strong><?= $actieveChauffeurs ?> / <?= $totaalChauffeurs ?></strong>
                <span>Actieve chauffeurs</span>
            </div>

            <div class="stat-card green">
                <strong><?= $voltooidVandaag ?></strong>
                <span>Voltooid vandaag</span>
            </div>
        </section>

        <!-- RIDES TABLE -->
        <section class="table-card">

            <div class="table-title">
                Ritaanvragen &amp; planning
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
                            <td colspan="6">Geen openstaande ritten.</td>
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
                                    <a class="btn btn-primary" href="?toewijzen=<?= (int) $rit['id'] ?>">Toewijzen</a>
                                <?php else: ?>
                                    <a class="btn btn-outline" href="?toewijzen=<?= (int) $rit['id'] ?>">Bewerken</a>
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
            <a class="panel-close" href="dashboard.php">✕</a>
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

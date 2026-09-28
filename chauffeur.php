<?php
//////////////////////////
// bestandsnaam: chauffeur.php
// omschrijving: Chauffeur page voor Veel Auto Planning
// auteur: Strahinja Zoranovic
// datum: 14/09/2026
//////////////////////////

session_start();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$db = getDb();

// --- Nieuwe chauffeur toevoegen (POST) ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_chauffeur'])) {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefoon = trim($_POST['telefoon'] ?? '');

    if ($naam !== '' && $email !== '') {
        $stmt = $db->prepare("
            INSERT INTO chauffeurs (naam, email, wachtwoord, telefoon, status, rating)
            VALUES (?, ?, ?, ?, 'offline', 5.0)
        ");
        $stmt->execute([$naam, $email, password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT), $telefoon]);
        $_SESSION['flash'] = "Chauffeur {$naam} toegevoegd.";
    }

    header('Location: chauffeur.php');
    exit;
}

// --- Status wijzigen (POST) ----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $chauffeurId = (int) $_POST['chauffeur_id'];
    $nieuweStatus = $_POST['nieuwe_status'] === 'beschikbaar' ? 'beschikbaar' : 'offline';

    $stmt = $db->prepare('UPDATE chauffeurs SET status = ? WHERE id = ?');
    $stmt->execute([$nieuweStatus, $chauffeurId]);

    header('Location: chauffeur.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// --- Statistieken ------------------------------------------------------
$actieveChauffeurs = (int) $db->query("SELECT COUNT(*) FROM chauffeurs WHERE status IN ('beschikbaar', 'rijdt')")->fetchColumn();
$totaalChauffeurs = (int) $db->query('SELECT COUNT(*) FROM chauffeurs')->fetchColumn();
$rijdtMomenteel = (int) $db->query("SELECT COUNT(*) FROM chauffeurs WHERE status = 'rijdt'")->fetchColumn();
$gemBeoordeling = (float) $db->query('SELECT AVG(rating) FROM chauffeurs')->fetchColumn();
$offline = (int) $db->query("SELECT COUNT(*) FROM chauffeurs WHERE status = 'offline'")->fetchColumn();

// --- Chauffeurslijst -----------------------------------------------------
$chauffeurs = $db->query("
    SELECT c.id, c.naam, c.email, c.telefoon, c.status, c.beschikbaar_vanaf, c.rating,
           k.naam AS klant_naam, r.ophaaladres, r.status AS rit_status
    FROM chauffeurs c
    LEFT JOIN ritten r ON r.chauffeur_id = c.id AND r.status IN ('toegewezen', 'onderweg')
    LEFT JOIN klanten k ON k.id = r.klant_id
    ORDER BY FIELD(c.status, 'rijdt', 'beschikbaar', 'offline'), c.rating DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chauffeurs - Veel Auto Planning</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/chauffeur.css">
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
            <a href="chauffeur.php" class="active">Chauffeurs</a>
            <a href="facturen.php">Facturatie</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main>

        <div class="page-header">
            <div>
                <h1>Chauffeurs</h1>
                <p>Beheer chauffeurs en bekijk hun beschikbaarheid</p>
            </div>

            <form class="inline-form" method="post">
                <input type="text" name="naam" placeholder="Naam" required>
                <input type="email" name="email" placeholder="E-mailadres" required>
                <input type="text" name="telefoon" placeholder="Telefoon">
                <button type="submit" name="add_chauffeur" value="1" class="btn btn-primary">+ Chauffeur toevoegen</button>
            </form>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= e($flash) ?></div>
        <?php endif; ?>

        <!-- STATISTICS -->
        <section class="stats">

            <div class="stat-card">
                <strong><?= $actieveChauffeurs ?> / <?= $totaalChauffeurs ?></strong>
                <span>Actieve chauffeurs</span>
            </div>

            <div class="stat-card">
                <strong><?= $rijdtMomenteel ?></strong>
                <span>Rijdt momenteel</span>
            </div>

            <div class="stat-card green">
                <strong><?= number_format($gemBeoordeling, 1, ',', '') ?></strong>
                <span>Gemiddelde beoordeling</span>
            </div>

            <div class="stat-card orange">
                <strong><?= $offline ?></strong>
                <span>Offline</span>
            </div>
        </section>

        <!-- DRIVERS TABLE -->
        <section class="table-card">

            <div class="table-title">
                Chauffeurslijst
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Chauffeur</th>
                            <th>Status</th>
                            <th>Beoordeling</th>
                            <th>Actieve rit</th>
                            <th>Contact</th>
                            <th>Actie</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($chauffeurs)): ?>
                        <tr>
                            <td colspan="6">Nog geen chauffeurs toegevoegd.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($chauffeurs as $c): ?>
                        <tr>
                            <td>
                                <div class="driver-cell">
                                    <span class="avatar"></span>
                                    <?= e($c['naam']) ?>
                                </div>
                            </td>
                            <td><?= chauffeurStatusBadge($c['status'], $c['beschikbaar_vanaf']) ?></td>
                            <td><?= number_format((float) $c['rating'], 1, ',', '') ?> ★</td>
                            <td><?= $c['klant_naam'] ? e($c['klant_naam']) . ' · ' . e($c['ophaaladres']) : '—' ?></td>
                            <td><?= e($c['telefoon']) ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="chauffeur_id" value="<?= (int) $c['id'] ?>">
                                    <?php if ($c['status'] === 'offline'): ?>
                                        <input type="hidden" name="nieuwe_status" value="beschikbaar">
                                        <button type="submit" name="toggle_status" value="1" class="btn btn-outline">Activeren</button>
                                    <?php elseif ($c['status'] === 'beschikbaar'): ?>
                                        <input type="hidden" name="nieuwe_status" value="offline">
                                        <button type="submit" name="toggle_status" value="1" class="btn btn-outline">Offline zetten</button>
                                    <?php else: ?>
                                        <span class="btn btn-outline" style="opacity:.5;cursor:default;">Rijdt</span>
                                    <?php endif; ?>
                                </form>
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

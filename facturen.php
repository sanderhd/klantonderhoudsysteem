<?php
//////////////////////////
// bestandsnaam: dashboard.php
// omschrijving: Dashboard page voor Veel Auto Planning
// auteur: Strahinja Zoranovic
// datum: 14/09/2026
//////////////////////////
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Facturatie overzicht - Veel Auto Planning</title>

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

            <button class="export-button">
                Exporteren
            </button>
        </div>


        <!-- FILTERS -->
        <div class="filters">
            <button class="filter active">
                Alle ritten
            </button>

            <button class="filter">
                Gefactureerd
            </button>

            <button class="filter">
                Nog niet gefactureerd
            </button>
        </div>


        <!-- STATISTICS -->
        <section class="stats">

            <div class="stat-card">
                <strong>0</strong>
                <span>Voltooide ritten (deze maand)</span>
            </div>

            <div class="stat-card green">
                <strong>0</strong>
                <span>Automatisch gefactureerd</span>
            </div>

            <div class="stat-card orange">
                <strong>0</strong>
                <span>Nog te factureren</span>
            </div>

            <div class="stat-card">
                <strong>€0</strong>
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

                        <tr>
                            <td>Naam</td>
                            <td>Addres</td>
                            <td>Datum</td>
                            <td class="amount">€0</td>
                            <td>
                                <span class="status invoiced">
                                    Placeholder
                                </span>
                            </td>
                            <td>F-0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

</body>
</html>
<?php
/**
 * Eenvoudige landingspagina voor Veel Auto Planning.
 */
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Veel Auto Planning</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
  <div class="brand">
    Veel Auto
    <span>Planning</span>
  </div>
  <nav>
    <a href="#features">Functies</a>
    <a href="dashboard.php" class="cta">Naar dashboard</a>
  </nav>
</header>

<section class="hero">
  <h1>Rittenplanning, simpel geregeld.</h1>
  <p>Beheer ritaanvragen, wijs chauffeurs toe en houd de planning overzichtelijk &mdash; alles op &eacute;&eacute;n plek.</p>
  <div class="buttons">
    <a href="dashboard.php" class="primary">Naar dashboard</a>
    <a href="#features" class="secondary">Meer informatie</a>
  </div>
</section>

<section class="features" id="features">
  <div class="feature">
    <div class="icon">📋</div>
    <h3>Ritaanvragen</h3>
    <p>Nieuwe aanvragen komen binnen en kunnen direct worden toegewezen aan een beschikbare chauffeur.</p>
  </div>
  <div class="feature">
    <div class="icon">🚗</div>
    <h3>Chauffeurs beheren</h3>
    <p>Zie in &eacute;&eacute;n oogopslag welke chauffeurs actief zijn en verdeel de ritten eerlijk.</p>
  </div>
  <div class="feature">
    <div class="icon">📄</div>
    <h3>Facturatie</h3>
    <p>Voltooide ritten zijn direct terug te vinden voor een snelle en correcte facturatie.</p>
  </div>
</section>

<footer>
  &copy; <?php echo date('Y'); ?> Veel Auto Planning
</footer>

</body>
</html>

<?php
//////////////////////////
// bestandsnaam: helpers.php
// omschrijving: Herbruikbare weergavefuncties voor Veel Auto Planning
//////////////////////////

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRitDatum(string $datumTijd): string
{
    $rit = new DateTime($datumTijd);
    $vandaag = new DateTime('today');
    $morgen = new DateTime('tomorrow');

    $tijd = $rit->format('H:i');

    if ($rit->format('Y-m-d') === $vandaag->format('Y-m-d')) {
        return "Vandaag · {$tijd}";
    }

    if ($rit->format('Y-m-d') === $morgen->format('Y-m-d')) {
        return "Morgen · {$tijd}";
    }

    return $rit->format('j M') . " · {$tijd}";
}

function statusBadge(string $status, ?string $chauffeurNaam = null): string
{
    $labels = [
        'nieuw' => 'Nieuw',
        'toegewezen' => 'Toegewezen' . ($chauffeurNaam ? ' · ' . e($chauffeurNaam) : ''),
        'onderweg' => 'Onderweg' . ($chauffeurNaam ? ' · ' . e($chauffeurNaam) : ''),
        'voltooid' => 'Voltooid',
        'geannuleerd' => 'Geannuleerd',
    ];

    $classes = [
        'nieuw' => 'new',
        'toegewezen' => 'assigned',
        'onderweg' => 'ongoing',
        'voltooid' => 'completed',
        'geannuleerd' => 'cancelled',
    ];

    $label = $labels[$status] ?? e($status);
    $class = $classes[$status] ?? '';

    return "<span class=\"status {$class}\">{$label}</span>";
}

function chauffeurStatusBadge(string $status, ?string $beschikbaarVanaf = null): string
{
    if ($status === 'beschikbaar') {
        return '<span class="status completed">Beschikbaar</span>';
    }

    if ($status === 'rijdt') {
        $extra = $beschikbaarVanaf ? ' · vrij om ' . (new DateTime($beschikbaarVanaf))->format('H:i') : '';
        return '<span class="status ongoing">Rijdt momenteel' . $extra . '</span>';
    }

    return '<span class="status cancelled">Offline</span>';
}

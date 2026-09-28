-- ==========================================================================
-- Veel Auto Planning - klantonderhoudsysteem
-- Database schema + demo data
--
-- Importeren:
--   phpMyAdmin  -> nieuwe database -> tabblad "Importeren" -> dit bestand
--   of via CLI  -> mysql -u root -p < klantonderhoudsysteem.sql
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS `klantonderhoudsysteem`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `klantonderhoudsysteem`;

-- Verwijdert eventuele oudere/afwijkende tabellen (bv. een losse CRM-opzet)
-- zodat dit script herhaaldelijk en schoon geïmporteerd kan worden.
-- Volgorde is belangrijk i.v.m. foreign keys (kind vóór ouder).
DROP TABLE IF EXISTS `facturen`;
DROP TABLE IF EXISTS `ritten`;
DROP TABLE IF EXISTS `medewerkers`;
DROP TABLE IF EXISTS `chauffeurs`;
DROP TABLE IF EXISTS `klanten`;

-- ==========================================================================
-- Klanten (klantportaal: inloggen, ritten aanvragen)
-- ==========================================================================

CREATE TABLE `klanten` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `naam` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `wachtwoord` VARCHAR(255) NOT NULL,
    `telefoon` VARCHAR(20) NULL,
    `adres` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_klanten_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Chauffeurs (chauffeur-app: ritten uitvoeren, beschikbaarheid)
-- ==========================================================================

CREATE TABLE `chauffeurs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `naam` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `wachtwoord` VARCHAR(255) NOT NULL,
    `telefoon` VARCHAR(20) NULL,
    `status` ENUM('beschikbaar', 'rijdt', 'offline') NOT NULL DEFAULT 'offline',
    `beschikbaar_vanaf` DATETIME NULL COMMENT 'Verwacht vrij-tijdstip wanneer status = rijdt',
    `rating` DECIMAL(2,1) NOT NULL DEFAULT 5.0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_chauffeurs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Medewerkers (planner / administratie - intern beheerpaneel)
-- ==========================================================================

CREATE TABLE `medewerkers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `naam` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `wachtwoord` VARCHAR(255) NOT NULL,
    `rol` ENUM('planner', 'administratie') NOT NULL DEFAULT 'planner',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_medewerkers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Ritten (ritaanvragen, planning & toewijzing)
-- ==========================================================================

CREATE TABLE `ritten` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `klant_id` INT UNSIGNED NOT NULL,
    `chauffeur_id` INT UNSIGNED NULL,
    `ophaaladres` VARCHAR(255) NOT NULL,
    `bestemming` VARCHAR(255) NOT NULL,
    `datum_tijd` DATETIME NOT NULL,
    `aantal_personen` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('nieuw', 'toegewezen', 'onderweg', 'voltooid', 'geannuleerd') NOT NULL DEFAULT 'nieuw',
    `prijs` DECIMAL(8,2) NULL,
    `opmerking` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ritten_status` (`status`),
    KEY `idx_ritten_datum_tijd` (`datum_tijd`),
    CONSTRAINT `fk_ritten_klant` FOREIGN KEY (`klant_id`) REFERENCES `klanten` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_ritten_chauffeur` FOREIGN KEY (`chauffeur_id`) REFERENCES `chauffeurs` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Facturen (administratie: facturatie van voltooide ritten)
-- ==========================================================================

CREATE TABLE `facturen` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rit_id` INT UNSIGNED NOT NULL,
    `factuurnummer` VARCHAR(20) NOT NULL,
    `bedrag` DECIMAL(8,2) NOT NULL,
    `status` ENUM('gefactureerd', 'nog_niet_gefactureerd') NOT NULL DEFAULT 'nog_niet_gefactureerd',
    `factuurdatum` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_facturen_rit` (`rit_id`),
    UNIQUE KEY `uq_facturen_factuurnummer` (`factuurnummer`),
    CONSTRAINT `fk_facturen_rit` FOREIGN KEY (`rit_id`) REFERENCES `ritten` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================================
-- Demo data (komt overeen met de Figma-wireframes, handig om de
-- schermen direct met representatieve gegevens te kunnen vullen)
-- ==========================================================================

INSERT INTO `klanten` (`naam`, `email`, `wachtwoord`, `telefoon`, `adres`) VALUES
('Fam. de Vries', 'devries@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-11111111', 'Stationsplein 1'),
('J. Bakker', 'j.bakker@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-22222222', 'Kerkstraat 22'),
('M. El Amrani', 'm.elamrani@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-33333333', 'Hoofdweg 5'),
('S. Visser', 's.visser@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-44444444', 'Marktplein 3'),
('T. Peters', 't.peters@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-55555555', 'Julianastraat 10'),
('H. Willems', 'h.willems@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-66666666', 'Dorpsstraat 8'),
('A. Jansen', 'a.jansen@voorbeeld.nl', '$2y$10$examplehashexamplehashexampleha', '06-77777777', 'Molenweg 14');

INSERT INTO `chauffeurs` (`naam`, `email`, `wachtwoord`, `telefoon`, `status`, `beschikbaar_vanaf`, `rating`) VALUES
('R. Jansen', 'r.jansen@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', '06-12345678', 'beschikbaar', NULL, 4.8),
('P. de Boer', 'p.deboer@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', '06-23456789', 'beschikbaar', NULL, 4.6),
('K. Smit', 'k.smit@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', '06-34567890', 'beschikbaar', NULL, 4.9),
('L. Mulder', 'l.mulder@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', '06-45678901', 'rijdt', '2026-09-07 15:10:00', 4.7),
('D. de Groot', 'd.degroot@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', '06-56789012', 'offline', NULL, 4.5);

INSERT INTO `medewerkers` (`naam`, `email`, `wachtwoord`, `rol`) VALUES
('Sander', 'sander@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', 'planner'),
('Strahinja', 'strahinja@veelauto.nl', '$2y$10$examplehashexamplehashexampleha', 'administratie');

INSERT INTO `ritten` (`klant_id`, `chauffeur_id`, `ophaaladres`, `bestemming`, `datum_tijd`, `aantal_personen`, `status`, `prijs`) VALUES
(1, NULL, 'Stationsplein 1', 'Grote Markt', '2026-09-07 14:30:00', 2, 'nieuw', NULL),
(2, 1, 'Kerkstraat 22', 'Ziekenhuis', '2026-09-07 09:15:00', 1, 'toegewezen', 18.50),
(3, NULL, 'Hoofdweg 5', 'Centraal Station', '2026-09-07 16:00:00', 3, 'nieuw', NULL),
(4, 2, 'Marktplein 3', 'Vliegveld', '2026-09-08 08:00:00', 2, 'toegewezen', 42.00),
(5, NULL, 'Julianastraat 10', 'Winkelcentrum', '2026-09-07 11:45:00', 1, 'nieuw', NULL),
(6, 4, 'Dorpsstraat 8', 'Sportcentrum', '2026-09-06 17:20:00', 4, 'voltooid', 27.75),
(7, NULL, 'Molenweg 14', 'Ziekenhuis', '2026-09-06 10:05:00', 1, 'geannuleerd', NULL);

INSERT INTO `facturen` (`rit_id`, `factuurnummer`, `bedrag`, `status`, `factuurdatum`) VALUES
(6, 'F-1001', 27.75, 'gefactureerd', '2026-09-06');

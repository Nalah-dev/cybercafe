<?php
require_once "../config/db.php";

date_default_timezone_set('Indian/Antananarivo');

/* =========================
   STATISTIQUES
========================= */

$postes = $bd->query("
    SELECT COUNT(*)
    FROM poste
")->fetchColumn();

$sessions = $bd->query("
    SELECT COUNT(*)
    FROM wifi_session
    WHERE statut='en_cours'
")->fetchColumn();

$clients = $bd->query("
    SELECT COUNT(*)
    FROM client
")->fetchColumn();

$revenu = $bd->query("
    SELECT SUM(montant)
    FROM paiement
    WHERE statut='payé'
")->fetchColumn();

if(!$revenu){
    $revenu = 0;
}

$lastPaiements = $bd->query("
    SELECT *
    FROM paiement
    ORDER BY id_paiement DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>Dashboard Cybercafé</title>

    <!-- BOOTSTRAP -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- FONT AWESOME -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS -->

    <link rel="stylesheet"
          href="dash.css">

    <!-- CHART JS -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-desktop"></i>
        CYBER NET

    </div>

    <div class="menu">

        <a href="#">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a href="../session/sessions.php">
            <i class="fa-solid fa-clock"></i>
            Sessions
        </a>

        <a href="../poste/postes.php">
            <i class="fa-solid fa-computer"></i>
            Postes
        </a>

        <a href="../client/clients.php">
            <i class="fa-solid fa-users"></i>
            Clients
        </a>

        <a href="../wifi/wifi_session.php">
            <i class="fa-solid fa-wifi"></i>
            WiFi
        </a>

        <a href="../paiement/paiement_wifi.php">
            <i class="fa-solid fa-money-bill"></i>
            Paiements
        </a>

    </div>

</div>

<!-- =========================
     MAIN
========================= -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h2>Dashboard</h2>

            <small>
                Gestion Cybercafé
            </small>

        </div>

        <div>

            <?= date('d/m/Y H:i:s') ?>

        </div>

    </div>

    <!-- CARDS -->

    <div class="cards">

        <div class="card-box">

            <i class="fa-solid fa-computer blue"></i>

            <p>Postes Totaux</p>

            <h2><?= $postes ?></h2>

        </div>

        <div class="card-box">

            <i class="fa-solid fa-wifi green"></i>

            <p>Sessions Actives</p>

            <h2><?= $sessions ?></h2>

        </div>

        <div class="card-box">

            <i class="fa-solid fa-users orange"></i>

            <p>Clients</p>

            <h2><?= $clients ?></h2>

        </div>

        <div class="card-box">

            <i class="fa-solid fa-money-bill purple"></i>

            <p>Revenu Total</p>

            <h2>

                <?= number_format($revenu,0,',',' ') ?> Ar

            </h2>

        </div>

    </div>

    <!-- CHARTS -->

    <div class="charts">

        <div class="chart-box">

            <h4>Évolution des sessions</h4>

            <canvas id="lineChart"></canvas>

        </div>

        <div class="chart-box">

            <h4>Paiements</h4>

            <canvas id="pieChart"></canvas>

        </div>

    </div>

    <!-- TABLE -->

    <div class="table-box">

        <h4>Derniers Paiements</h4>

        <br>

        <table>

            <tr>

                <th>ID</th>
                <th>Session</th>
                <th>Montant</th>
                <th>Mode</th>
                <th>Date</th>
                <th>Statut</th>

            </tr>

            <?php foreach($lastPaiements as $p){ ?>

            <tr>

                <td><?= $p['id_paiement'] ?></td>

                <td><?= $p['id_session'] ?></td>

                <td>

                    <?= number_format($p['montant'],0,',',' ') ?> Ar

                </td>

                <td><?= $p['mode_paiement'] ?></td>

                <td><?= $p['date_paiement'] ?></td>

                <td>

                    <span class="badge-success">

                        <?= $p['statut'] ?>

                    </span>

                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

</div>

<!-- JS -->

<script src="dash.js"></script>

</body>
</html>
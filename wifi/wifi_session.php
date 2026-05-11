<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');

/* ======================================================
   🔴 AUTO STOP SESSION
====================================================== */

$bd->query("
    UPDATE wifi_session
    SET statut='terminée'
    WHERE statut='en_cours'
    AND heure_fin <= NOW()
");

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>WiFi Sessions</title>
</head>
<body>

<h1>📡 Gestion Sessions WiFi</h1>

<hr>

<!-- ======================================================
     🔍 RECHERCHE + FILTRE
====================================================== -->

<h2>🔍 Recherche / Filtre</h2>

<form method="GET">

    <input type="text"
           name="search"
           placeholder="Code voucher">

    <select name="filtre">

        <option value="">Tous</option>

        <option value="en_cours">
            En cours
        </option>

        <option value="terminée">
            Terminée
        </option>

    </select>

    <button type="submit">
        OK
    </button>

</form>

<hr>

<?php

/* ======================================================
   📋 QUERY SESSION
====================================================== */

$where = "1=1";
$params = [];

if(!empty($_GET['search'])){

    $where .= " AND v.code LIKE ?";

    $params[] = "%".$_GET['search']."%";
}

if(!empty($_GET['filtre'])){

    $where .= " AND ws.statut=?";

    $params[] = $_GET['filtre'];
}

$sql = "
SELECT ws.*,
       v.code
FROM wifi_session ws
LEFT JOIN voucher v
ON ws.id_voucher = v.id_voucher
WHERE $where
ORDER BY ws.id_wifi_session DESC
";

$req = $bd->prepare($sql);
$req->execute($params);

$sessions = $req->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ======================================================
     📊 STATISTIQUES
====================================================== -->

<?php

$active = $bd->query("
    SELECT COUNT(*)
    FROM wifi_session
    WHERE statut='en_cours'
")->fetchColumn();

$finished = $bd->query("
    SELECT COUNT(*)
    FROM wifi_session
    WHERE statut='terminée'
")->fetchColumn();

$revenu = $bd->query("
    SELECT SUM(prix_total)
    FROM wifi_session
")->fetchColumn();

?>

<h2>📊 Statistiques</h2>

<p>🟢 Sessions actives : <?= $active ?></p>

<p>🔴 Sessions terminées : <?= $finished ?></p>

<p>💰 Revenu total : <?= $revenu ?> Ar</p>

<hr>

<!-- ======================================================
     📋 TABLE SESSION
====================================================== -->

<h2>📋 Liste Sessions WiFi</h2>

<table border="1" cellpadding="10">

<tr>

    <th>ID</th>

    <th>Voucher</th>

    <th>Début</th>

    <th>Fin</th>

    <th>Temps restant</th>

    <th>Durée</th>

    <th>Prix</th>

    <th>Statut</th>

</tr>

<?php foreach($sessions as $s){ ?>

<tr>

    <td><?= $s['id_wifi_session'] ?></td>

    <td><?= $s['code'] ?></td>

    <td><?= $s['heure_debut'] ?></td>

    <td><?= $s['heure_fin'] ?></td>

    <!-- ======================================================
         ⏱️ TEMPS RESTANT
    ====================================================== -->

    <td>

    <?php

    if($s['statut'] == 'en_cours'){

        $restant =
        strtotime($s['heure_fin']) - time();

        if($restant > 0){

            $h = floor($restant / 3600);

            $m = floor(($restant % 3600) / 60);

            echo "⏳ ".$h."h ".$m."min";

        }else{

            echo "⌛ Expiré";
        }

    }else{

        echo "✔ Terminé";
    }

    ?>

    </td>

    <td><?= $s['duree'] ?> min</td>

    <td><?= $s['prix_total'] ?> Ar</td>

    <td>

        <?= ($s['statut']=='en_cours')
            ? '🟢 En cours'
            : '🔴 Terminée'
        ?>

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📈 DERNIÈRE SESSION
====================================================== -->

<h2>📈 Dernière Session</h2>

<?php

$last = $bd->query("
    SELECT ws.*,
           v.code
    FROM wifi_session ws
    LEFT JOIN voucher v
    ON ws.id_voucher=v.id_voucher
    ORDER BY ws.id_wifi_session DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if($last){

?>

<p>🎟️ Voucher :
<b><?= $last['code'] ?></b></p>

<p>⏱️ Début :
<?= $last['heure_debut'] ?></p>

<p>⌛ Fin :
<?= $last['heure_fin'] ?></p>

<p>💰 Prix :
<?= $last['prix_total'] ?> Ar</p>

<?php } ?>

</body>
</html>
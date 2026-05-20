<?php
require_once "../config/db.php";

date_default_timezone_set('Indian/Antananarivo');

/* ======================================================
   🔴 AUTO STOP SESSION
====================================================== */

$req = $bd->query("
    SELECT *
    FROM wifi_session
    WHERE statut='en_cours'
    AND heure_fin <= NOW()
");

$sessionsExpirees = $req->fetchAll(PDO::FETCH_ASSOC);

foreach($sessionsExpirees as $s){

    $sec =
    strtotime($s['heure_fin']) -
    strtotime($s['heure_debut']);

    if($sec < 0){
        $sec = 0;
    }

    $duree = ceil($sec / 60);

    $update = $bd->prepare("
        UPDATE wifi_session
        SET statut='terminée',
            duree_sw=?
        WHERE id_wifi_session=?
    ");

    $update->execute([
        $duree,
        $s['id_wifi_session']
    ]);
}

/* ======================================================
   🔍 RECHERCHE + FILTRE
====================================================== */

$where = "1=1";
$params = [];

if(!empty($_GET['search'])){

    $where .= " AND v.code LIKE ?";

    $params[] =
    "%".$_GET['search']."%";
}

if(!empty($_GET['filtre'])){

    $where .= " AND ws.statut=?";

    $params[] =
    $_GET['filtre'];
}

/* ======================================================
   📋 QUERY SESSION
====================================================== */

$sql = "
SELECT ws.*,
       v.code,
       w.nom_wifi,
       w.bande
FROM wifi_session ws

LEFT JOIN voucher v
ON ws.id_voucher=v.id_voucher

LEFT JOIN wifi w
ON ws.id_wifi=w.id_wifi

WHERE $where

ORDER BY ws.id_wifi_session DESC
";

$req = $bd->prepare($sql);

$req->execute($params);

$sessions =
$req->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   📊 STATISTIQUES
====================================================== */

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

$paid = $bd->query("
    SELECT COUNT(*)
    FROM wifi_session
    WHERE statut='payé'
")->fetchColumn();

$revenu = $bd->query("
    SELECT SUM(prix_total)
    FROM wifi_session
")->fetchColumn();

/* ======================================================
   📌 DERNIÈRE SESSION
====================================================== */

$last = $bd->query("
    SELECT ws.*,
           v.code,
           w.nom_wifi,
           w.bande
    FROM wifi_session ws

    LEFT JOIN voucher v
    ON ws.id_voucher=v.id_voucher

    LEFT JOIN wifi w
    ON ws.id_wifi=w.id_wifi

    ORDER BY ws.id_wifi_session DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion WiFi Sessions</title>
</head>
<body>

<h1>📡 Gestion WiFi Sessions</h1>

<hr>

<!-- ======================================================
     🔍 RECHERCHE / FILTRE
====================================================== -->

<h2>🔍 Recherche / Filtre</h2>

<form method="GET">

    <input type="text"
           name="search"
           placeholder="Code voucher"
           value="<?= $_GET['search'] ?? '' ?>">

    <select name="filtre">

        <option value="">
            Tous
        </option>

        <option value="en_cours">
            En cours
        </option>

        <option value="terminée">
            Terminée
        </option>

        <option value="payé">
            Payé
        </option>

    </select>

    <button type="submit">
        Rechercher
    </button>

</form>

<hr>

<!-- ======================================================
     📊 STATISTIQUES
====================================================== -->

<h2>📊 Statistiques</h2>

<table border="1" cellpadding="10">

<tr>

    <th>
        Sessions actives
    </th>

    <th>
        Sessions terminées
    </th>

    <th>
        Sessions payées
    </th>

    <th>
        Revenu total
    </th>

</tr>

<tr>

    <td>
        🟢 <?= $active ?>
    </td>

    <td>
        🔴 <?= $finished ?>
    </td>

    <td>
        💰 <?= $paid ?>
    </td>

    <td>
        💵 <?= $revenu ?? 0 ?> Ar
    </td>

</tr>

</table>

<hr>

<!-- ======================================================
     📋 LISTE SESSIONS
====================================================== -->

<h2>📋 Liste Sessions WiFi</h2>

<table border="1" cellpadding="10">

<tr>

    <th>ID</th>

    <th>WiFi</th>

    <th>Bande</th>

    <th>Voucher</th>

    <th>Début</th>

    <th>Fin</th>

    <th>Temps restant</th>

    <th>Durée</th>

    <th>Prix</th>

    <th>Adresse IP</th>

    <th>Appareil</th>

    <th>Statut</th>

</tr>

<?php if(count($sessions) > 0){ ?>

<?php foreach($sessions as $s){ ?>

<tr>

    <td>
        <?= $s['id_wifi_session'] ?>
    </td>

    <td>
        <?= $s['nom_wifi'] ?>
    </td>

    <td>
        <?= $s['bande'] ?>
    </td>

    <td>
        <?= $s['code'] ?>
    </td>

    <td>
        <?= $s['heure_debut'] ?>
    </td>

    <td>
        <?= $s['heure_fin'] ?>
    </td>

    <!-- ======================================================
         ⏳ TEMPS RESTANT
    ====================================================== -->

    <td>

    <?php

    if($s['statut'] == 'en_cours'){

        $restant =
        strtotime($s['heure_fin']) - time();

        if($restant > 0){

            $h =
            floor($restant / 3600);

            $m =
            floor(($restant % 3600) / 60);

            echo "⏳ ".$h."h ".$m." min";

        }else{

            echo "⌛ Expiré";
        }

    }else{

        echo "✔ Terminé";
    }

    ?>

    </td>

    <!-- ======================================================
         ⏱️ DURÉE
    ====================================================== -->

    <td>

    <?php

    if($s['statut'] == 'en_cours'){

        $sec =
        time() -
        strtotime($s['heure_debut']);

        if($sec < 0){
            $sec = 0;
        }

        $minutes =
        ceil($sec / 60);

    }else{

        $minutes =
        $s['duree_sw'] ?? 0;
    }

    $heure =
    floor($minutes / 60);

    $minute =
    $minutes % 60;

    echo $heure."h ".$minute." min";

    ?>

    </td>

    <td>
        <?= $s['prix_total'] ?? 0 ?> Ar
    </td>

    <td>
        <?= $s['adresse_ip'] ?>
    </td>

    <td>
        <?= $s['appareil'] ?>
    </td>

    <td>

    <?php

    if($s['statut']=='en_cours'){

        echo "🟢 En cours";

    }elseif($s['statut']=='payé'){

        echo "💰 Payé";

    }else{

        echo "🔴 Terminée";
    }

    ?>

    </td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>

    <td colspan="12">

        ❌ Aucune session trouvée

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📌 DERNIÈRE SESSION
====================================================== -->

<h2>📌 Dernière Session</h2>

<?php if($last){ ?>

<table border="1" cellpadding="10">

<tr>

    <th>
        WiFi
    </th>

    <th>
        Bande
    </th>

    <th>
        Voucher
    </th>

    <th>
        Début
    </th>

    <th>
        Fin
    </th>

    <th>
        Prix
    </th>

    <th>
        Statut
    </th>

</tr>

<tr>

    <td>
        <?= $last['nom_wifi'] ?>
    </td>

    <td>
        <?= $last['bande'] ?>
    </td>

    <td>
        <?= $last['code'] ?>
    </td>

    <td>
        <?= $last['heure_debut'] ?>
    </td>

    <td>
        <?= $last['heure_fin'] ?>
    </td>

    <td>
        <?= $last['prix_total'] ?? 0 ?> Ar
    </td>

    <td>

    <?php

    if($last['statut']=='en_cours'){

        echo "🟢 En cours";

    }elseif($last['statut']=='payé'){

        echo "💰 Payé";

    }else{

        echo "🔴 Terminée";
    }

    ?>

    </td>

</tr>

</table>

<?php }else{ ?>

<p>
❌ Aucune session disponible
</p>

<?php } ?>

<hr>

</body>
</html>
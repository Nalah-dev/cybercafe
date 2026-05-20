<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement Wifi Cybercafé</title>
</head>
<body>

<h1>💰 Paiement WIFI Cybercafé</h1>

<hr>

<?php

$tarif_heure = 2000;

/* ======================================================
   💰 PAIEMENT WIFI
====================================================== */

if(isset($_POST['pay'])){

    $id_session = $_POST['id_wifi_session'];
    $mode = $_POST['mode_paiement_wifi'];

    // 🔍 check session
    $req = $bd->prepare("
        SELECT *
        FROM wifi_session
        WHERE id_wifi_session=?
        AND statut='terminée'
    ");
    $req->execute([$id_session]);

    $session = $req->fetch(PDO::FETCH_ASSOC);

    if(!$session){

        echo "❌ Session invalide ou déjà payée<br>";

    }else{

        // 💰 montant (utilise prix_total déjà calculé)
        $montant = $session['prix_total'];

        if($montant <= 0){
            $sec = strtotime($session['heure_fin']) - strtotime($session['heure_debut']);
            $duree = ceil($sec / 60);
            $montant = ($duree / 60) * $tarif_heure;
        }

        // 🟢 update session
        $bd->prepare("
            UPDATE wifi_session
            SET statut='payé'
            WHERE id_wifi_session=?
        ")->execute([$id_session]);

        // 💰 insert paiement_wifi
        $bd->prepare("
            INSERT INTO paiement_wifi
            (
                montant_wifi,
                date_paiement_wifi,
                mode_paiement_wifi,
                statut,
                id_wifi_session
            )
            VALUES
            (?, NOW(), ?, 'payé', ?)
        ")->execute([
            $montant,
            $mode,
            $id_session
        ]);

        echo "✅ Paiement WIFI validé<br>";
        echo "💰 Montant: $montant Ar<br>";
    }
}

?>

<!-- ======================================================
     💰 FORMULAIRE
====================================================== -->

<h2>➕ Paiement Session WIFI terminée</h2>

<form method="POST">

    <select name="id_wifi_session" required>

        <option value="">
            -- Session terminée --
        </option>

        <?php

        $sessions = $bd->query("
            SELECT ws.*,
                   v.code
            FROM wifi_session ws
            LEFT JOIN voucher v
            ON ws.id_voucher = v.id_voucher
            WHERE ws.statut='terminée'
            ORDER BY ws.id_wifi_session DESC
        ");

        foreach($sessions as $s){

            echo "<option value='{$s['id_wifi_session']}'>
                    #{$s['id_wifi_session']}
                    | Voucher {$s['code']}
                    | IP {$s['adresse_ip']}
                    | {$s['prix_total']} Ar
                  </option>";
        }

        ?>

    </select>

    <select name="mode_paiement_wifi" required>

        <option value="cash">Cash</option>
        <option value="mobile_money">Mobile Money</option>

    </select>

    <button type="submit" name="pay">
        Valider Paiement
    </button>

</form>

<hr>

<!-- ======================================================
     📋 HISTORIQUE
====================================================== -->

<h2>📋 Historique Paiements WIFI</h2>

<?php

$paiements = $bd->query("
    SELECT p.*,
           ws.id_wifi_session,
           ws.adresse_ip
    FROM paiement_wifi p
    LEFT JOIN wifi_session ws
    ON p.id_wifi_session = ws.id_wifi_session
    ORDER BY p.id_paiement_wifi DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<table border="1" cellpadding="8">

<tr>
    <th>ID</th>
    <th>Session</th>
    <th>Montant</th>
    <th>Mode</th>
    <th>Date</th>
    <th>Statut</th>
</tr>

<?php foreach($paiements as $p){ ?>

<tr>

    <td><?= $p['id_paiement_wifi'] ?></td>
    <td><?= $p['id_wifi_session'] ?></td>
    <td><?= $p['montant_wifi'] ?> Ar</td>
    <td><?= $p['mode_paiement_wifi'] ?></td>
    <td><?= $p['date_paiement_wifi'] ?></td>
    <td><?= $p['statut'] ?></td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📊 STATISTIQUES
====================================================== -->

<h2>📊 Statistiques WIFI</h2>

<?php

$total = $bd->query("SELECT SUM(montant_wifi) FROM paiement_wifi")->fetchColumn();

$cash = $bd->query("
    SELECT SUM(montant_wifi)
    FROM paiement_wifi
    WHERE mode_paiement_wifi='cash'
")->fetchColumn();

$mobile = $bd->query("
    SELECT SUM(montant_wifi)
    FROM paiement_wifi
    WHERE mode_paiement_wifi='mobile_money'
")->fetchColumn();

?>

<p>💰 Total : <?= $total ?> Ar</p>
<p>💵 Cash : <?= $cash ?> Ar</p>
<p>📱 Mobile Money : <?= $mobile ?> Ar</p>

</body>
</html>
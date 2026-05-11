<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement Cybercafé</title>
</head>
<body>

<h1>💰 Paiement Sécurisé Cybercafé</h1>

<hr>

<?php
$tarif_heure = 2000;

/* ======================================================
   💰 PAIEMENT SECURISÉ
====================================================== */

if(isset($_POST['pay'])){

    $id_session = $_POST['id_session'];
    $mode = $_POST['mode_paiement'];

    // 🔍 check session ACTIVE uniquement
    $req = $bd->prepare("
        SELECT *
        FROM wifi_session
        WHERE id_wifi_session=?
        AND statut='en_cours'
    ");
    $req->execute([$id_session]);

    $session = $req->fetch(PDO::FETCH_ASSOC);

    if(!$session){

        echo "❌ Session invalide ou déjà payée<br>";

    }else{

        // ⏱ calcul durée réelle
        $sec = strtotime(date('Y-m-d H:i:s')) - strtotime($session['heure_debut']);
        if($sec < 0) $sec = 0;

        $duree = ceil($sec / 60);

        // 💰 calcul automatique prix
        $montant = ($duree / 60) * $tarif_heure;

        // 🟢 update session
        $bd->prepare("
            UPDATE wifi_session
            SET heure_fin = NOW(),
                duree_ws = ?,
                prix_total = ?,
                statut = 'payé'
            WHERE id_wifi_session = ?
        ")->execute([$duree, $montant, $id_session]);

        // 💰 insert paiement sécurisé
        $bd->prepare("
            INSERT INTO paiement
            (
                id_session,
                montant,
                mode_paiement,
                statut
            )
            VALUES
            (?, ?, ?, 'payé')
        ")->execute([
            $id_session,
            $montant,
            $mode
        ]);

        echo "✅ Paiement validé automatiquement<br>";
        echo "⏱ Durée: $duree min<br>";
        echo "💰 Montant: $montant Ar<br>";
    }
}
?>

<!-- ======================================================
     💰 FORMULAIRE SECURISÉ
====================================================== -->

<h2>➕ Paiement session active</h2>

<form method="POST">

    <select name="id_session" required>

        <option value="">-- Session active --</option>

        <?php
        $sessions = $bd->query("
            SELECT * FROM wifi_session
            WHERE statut='en_cours'
            ORDER BY id_wifi_session DESC
        ");

        foreach($sessions as $s){
            echo "<option value='{$s['id_wifi_session']}'>
                    Session #{$s['id_wifi_session']} - IP {$s['adresse_ip']}
                  </option>";
        }
        ?>

    </select>

    <select name="mode_paiement" required>

        <option value="cash">Cash</option>

        <option value="mobile_money">Mobile Money</option>

    </select>

    <button type="submit" name="pay">
        Valider Paiement
    </button>

</form>

<hr>

<!-- ======================================================
     📋 LISTE PAIEMENTS
====================================================== -->

<h2>📋 Historique Paiements</h2>

<?php

$paiements = $bd->query("
    SELECT *
    FROM paiement
    ORDER BY id_paiement DESC
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

    <td><?= $p['id_paiement'] ?></td>
    <td><?= $p['id_session'] ?></td>
    <td><?= $p['montant'] ?> Ar</td>
    <td><?= $p['mode_paiement'] ?></td>
    <td><?= $p['date_paiement'] ?></td>
    <td><?= $p['statut'] ?></td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📊 STATISTIQUES
====================================================== -->

<h2>📊 Statistiques</h2>

<?php

$total = $bd->query("SELECT SUM(montant) FROM paiement")->fetchColumn();
$cash = $bd->query("SELECT SUM(montant) FROM paiement WHERE mode_paiement='cash'")->fetchColumn();
$mobile = $bd->query("SELECT SUM(montant) FROM paiement WHERE mode_paiement='mobile_money'")->fetchColumn();

?>

<p>💰 Total : <?= $total ?> Ar</p>
<p>💵 Cash : <?= $cash ?> Ar</p>
<p>📱 Mobile Money : <?= $mobile ?> Ar</p>

</body>
</html>
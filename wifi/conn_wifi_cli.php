<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion WiFi</title>
</head>
<body>

<h1>📶 Connexion WiFi Client</h1>

<?php

$ip = $_SERVER['REMOTE_ADDR'];
$appareil = $_SERVER['HTTP_USER_AGENT'];

/* ======================================================
   🔐 LOGIN WIFI
====================================================== */

if(isset($_POST['login'])){

    $code = trim($_POST['code']);

    // chercher voucher valide
    $req = $bd->prepare("
        SELECT *
        FROM voucher
        WHERE code=?
        AND statut='non_utilise'
    ");
    $req->execute([$code]);

    $voucher = $req->fetch(PDO::FETCH_ASSOC);

    if(!$voucher){

        echo "❌ Code invalide ou déjà utilisé <br>";

    }else{

        // durée voucher
        $duree = $voucher['duree_voucher'];

        // heure fin automatique
        $heure_fin = date(
            'Y-m-d H:i:s',
            strtotime("+$duree minutes")
        );

        // insert session wifi
        $bd->prepare("
            INSERT INTO wifi_session
            (
                id_voucher,
                heure_debut,
                heure_fin,
                duree_sw,
                prix_total,
                adresse_ip,
                appareil,
                statut
            )
            VALUES
            (
                ?, NOW(), ?, ?, ?, ?, ?, 'en_cours'
            )
        ")->execute([
            $voucher['id_voucher'],
            $heure_fin,
            $voucher['duree_voucher'],
            $voucher['prix_voucher'],
            $ip,
            $appareil
        ]);

        // voucher utilisé
        $bd->prepare("
            UPDATE voucher
            SET statut='utilise'
            WHERE id_voucher=?
        ")->execute([$voucher['id_voucher']]);

        echo "✅ WiFi activé <br>";
        echo "🎟️ Code : ".$voucher['code']." <br>";
        echo "⏱️ Durée : ".$voucher['duree_voucher']." min <br>";
        echo "💰 Prix : ".$voucher['prix_voucher']." Ar <br>";
        echo "🌐 IP : ".$ip." <br>";
        echo "📱 Appareil : ".$appareil." <br>";
        echo "⌛ Fin : ".$heure_fin." <br>";
    }
}
?>

<hr>

<!-- ======================================================
     📶 FORM LOGIN
====================================================== -->

<h2>🔐 Entrer Voucher WiFi</h2>

<form method="POST">

    <input type="text"
           name="code"
           placeholder="Code Voucher"
           required>

    <button type="submit" name="login">
        Connecter
    </button>

</form>

<hr>

<!-- ======================================================
     📋 SESSION ACTIVES
====================================================== -->

<h2>📋 Sessions WiFi Actives</h2>

<?php

$sessions = $bd->query("
    SELECT ws.*,
           v.code
    FROM wifi_session ws
    LEFT JOIN voucher v
    ON ws.id_voucher = v.id_voucher
    WHERE ws.statut='en_cours'
    ORDER BY ws.id_wifi_session DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<table border="1" cellpadding="5">

<tr>
    <th>ID</th>
    <th>Voucher</th>
    <th>Début</th>
    <th>Fin</th>
    <th>Durée</th>
    <th>Prix</th>
    <th>IP</th>
    <th>Appareil</th>
    <th>Statut</th>
</tr>

<?php foreach($sessions as $s){ ?>

<tr>

    <td><?= $s['id_wifi_session'] ?></td>

    <td><?= $s['code'] ?></td>

    <td><?= $s['heure_debut'] ?></td>

    <td><?= $s['heure_fin'] ?></td>

    <td><?= $s['duree_sw'] ?> min</td>

    <td><?= $s['prix_total'] ?> Ar</td>

    <td><?= $s['adresse_ip'] ?></td>

    <td><?= $s['appareil'] ?></td>

    <td><?= $s['statut'] ?></td>

</tr>

<?php } ?>

</table>

</body>
</html>
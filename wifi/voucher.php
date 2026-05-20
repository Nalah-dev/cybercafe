<?php
require_once "../config/db.php";

date_default_timezone_set('Indian/Antananarivo');

/* ======================================================
   🎟️ GENERATE CODE
====================================================== */

function generateCode(){

    return strtoupper(
        substr(md5(uniqid(rand(), true)), 0, 8)
    );
}

/* ======================================================
   ➕ GENERER VOUCHER
====================================================== */

if(isset($_POST['add_voucher'])){

    $id_forfait =
    $_POST['id_forfait'];

    // récupérer forfait
    $req = $bd->prepare("
        SELECT f.*,
               w.nom_wifi,
               w.bande

        FROM forfait_wifi f

        LEFT JOIN wifi w
        ON f.id_wifi = w.id_wifi

        WHERE f.id_forfait=?
        AND f.statut='actif'
    ");

    $req->execute([
        $id_forfait
    ]);

    $forfait =
    $req->fetch(PDO::FETCH_ASSOC);

    if(!$forfait){

        echo "❌ Forfait introuvable";

    }else{

        $code =
        generateCode();

        // insert voucher
        $insert = $bd->prepare("
            INSERT INTO voucher
            (
                id_forfait,
                code,
                statut,
                date_creation
            )
            VALUES
            (?, ?, 'non_utilise', NOW())
        ");

        $insert->execute([
            $id_forfait,
            $code
        ]);

        echo "
        ✅ Voucher généré avec succès
        <br><br>

        🎟️ Code :
        <b>$code</b>

        <br>

        📦 Forfait :
        ".$forfait['nom_forfait']."

        <br>

        📶 WiFi :
        ".$forfait['nom_wifi']."

        <br>

        📡 Bande :
        ".$forfait['bande']."

        <br>

        ⏱️ Durée :
        ".$forfait['duree']." min

        <br>

        💰 Prix :
        ".$forfait['prix']." Ar

        <br><br>
        ";
    }
}

/* ======================================================
   🗑️ DELETE VOUCHER
====================================================== */

if(isset($_POST['delete_voucher'])){

    $delete = $bd->prepare("
        DELETE FROM voucher
        WHERE id_voucher=?
    ");

    $delete->execute([
        $_POST['delete_id']
    ]);

    echo "🗑️ Voucher supprimé";
}

/* ======================================================
   📋 LISTE VOUCHERS
====================================================== */

$vouchers = $bd->query("
    SELECT v.*,

           f.nom_forfait,
           f.duree,
           f.prix,

           w.nom_wifi,
           w.bande

    FROM voucher v

    LEFT JOIN forfait_wifi f
    ON v.id_forfait = f.id_forfait

    LEFT JOIN wifi w
    ON f.id_wifi = w.id_wifi

    ORDER BY v.id_voucher DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   📊 STATS
====================================================== */

$total = $bd->query("
    SELECT COUNT(*)
    FROM voucher
")->fetchColumn();

$used = $bd->query("
    SELECT COUNT(*)
    FROM voucher
    WHERE statut='utilise'
")->fetchColumn();

$unused = $bd->query("
    SELECT COUNT(*)
    FROM voucher
    WHERE statut='non_utilise'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Voucher</title>
</head>
<body>

<h1>🎟️ Gestion Voucher WiFi</h1>

<hr>

<!-- ======================================================
     ➕ GENERER VOUCHER
====================================================== -->

<h2>➕ Générer Voucher</h2>

<form method="POST">

    <label>Forfait WiFi :</label>

    <select name="id_forfait" required>

        <option value="">
            Choisir forfait
        </option>

        <?php

        $forfaits = $bd->query("
            SELECT f.*,
                   w.nom_wifi,
                   w.bande

            FROM forfait_wifi f

            LEFT JOIN wifi w
            ON f.id_wifi = w.id_wifi

            WHERE f.statut='actif'

            ORDER BY f.id_forfait DESC
        ");

        foreach($forfaits as $f){

            $h =
            floor($f['duree'] / 60);

            $m =
            $f['duree'] % 60;

            echo "
            <option value='{$f['id_forfait']}'>

                {$f['nom_forfait']}
                -

                {$h}h {$m}min

                -

                {$f['prix']} Ar

                -

                {$f['nom_wifi']}
                ({$f['bande']})

            </option>
            ";
        }

        ?>

    </select>

    <br><br>

    <button type="submit"
            name="add_voucher">

        Générer Voucher

    </button>

</form>

<hr>

<!-- ======================================================
     📋 LISTE VOUCHERS
====================================================== -->

<h2>📋 Liste Voucher</h2>

<table border="1" cellpadding="10">

<tr>

    <th>ID</th>

    <th>Code</th>

    <th>Forfait</th>

    <th>WiFi</th>

    <th>Bande</th>

    <th>Durée</th>

    <th>Prix</th>

    <th>Création</th>

    <th>Utilisation</th>

    <th>Statut</th>

    <th>Delete</th>

</tr>

<?php if(count($vouchers) > 0){ ?>

<?php foreach($vouchers as $v){ ?>

<tr>

    <td>
        <?= $v['id_voucher'] ?>
    </td>

    <td>
        <?= $v['code'] ?>
    </td>

    <td>
        <?= $v['nom_forfait'] ?>
    </td>

    <td>
        <?= $v['nom_wifi'] ?>
    </td>

    <td>
        <?= $v['bande'] ?>
    </td>

    <td>

    <?php

    $h =
    floor($v['duree'] / 60);

    $m =
    $v['duree'] % 60;

    echo $h."h ".$m." min";

    ?>

    </td>

    <td>
        <?= $v['prix'] ?> Ar
    </td>

    <td>
        <?= $v['date_creation'] ?>
    </td>

    <td>

    <?php

    if($v['date_utilisation']){

        echo $v['date_utilisation'];

    }else{

        echo "-";
    }

    ?>

    </td>

    <td>

    <?php

    if($v['statut']=='non_utilise'){

        echo "🟢 Non utilisé";

    }else{

        echo "🔴 Utilisé";
    }

    ?>

    </td>

    <td>

        <form method="POST"
              onsubmit="return confirm('Supprimer ?')">

            <input type="hidden"
                   name="delete_id"
                   value="<?= $v['id_voucher'] ?>">

            <button type="submit"
                    name="delete_voucher">

                🗑️

            </button>

        </form>

    </td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>

    <td colspan="11">

        ❌ Aucun voucher

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📊 STATISTIQUES
====================================================== -->

<h2>📊 Statistiques</h2>

<table border="1" cellpadding="10">

<tr>

    <th>Total</th>

    <th>Non utilisés</th>

    <th>Utilisés</th>

</tr>

<tr>

    <td>
        🎟️ <?= $total ?>
    </td>

    <td>
        🟢 <?= $unused ?>
    </td>

    <td>
        🔴 <?= $used ?>
    </td>

</tr>

</table>

<hr>

</body>
</html>
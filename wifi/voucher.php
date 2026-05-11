<?php
require_once "../config/db.php";

/* ======================================================
   🔥 GENERATE CODE
====================================================== */
function generateCode(){

    return strtoupper(
        substr(md5(uniqid()), 0, 8)
    );
}
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
     ➕ AJOUT VOUCHER
====================================================== -->

<h2>➕ Générer Voucher</h2>

<?php
if(isset($_POST['add_voucher'])){

    $code = generateCode();

    $duree = $_POST['duree'];
    $prix = $_POST['prix'];

    $bd->prepare("
        INSERT INTO voucher
        (code, duree_voucher, prix_voucher, statut)
        VALUES (?, ?, ?, 'non_utilise')
    ")->execute([
        $code,
        $duree,
        $prix
    ]);

    echo "✅ Voucher généré : <b>$code</b>";
}
?>

<form method="POST">

    <input type="number"
           name="duree"
           placeholder="Durée en minutes"
           required>

    <input type="number"
           name="prix"
           placeholder="Prix"
           required>

    <button type="submit" name="add_voucher">
        Générer
    </button>

</form>

<hr>

<!-- ======================================================
     🔍 RECHERCHE VOUCHER
====================================================== -->

<h2>🔍 Recherche Voucher</h2>

<form method="GET">

    <input type="text"
           name="search"
           placeholder="Code ou statut">

    <button type="submit">
        Rechercher
    </button>

</form>

<hr>

<?php

$where = "1=1";
$params = [];

if(!empty($_GET['search'])){

    $where .= " AND (code LIKE ? OR statut LIKE ?)";

    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$sql = "
SELECT *
FROM voucher
WHERE $where
ORDER BY id_voucher DESC
";

$req = $bd->prepare($sql);
$req->execute($params);

$vouchers = $req->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ======================================================
     📊 STATS
====================================================== -->

<?php
$used = $bd->query("
    SELECT COUNT(*) FROM voucher
    WHERE statut='utilise'
")->fetchColumn();

$unused = $bd->query("
    SELECT COUNT(*) FROM voucher
    WHERE statut='non_utilise'
")->fetchColumn();
?>

<h2>📊 Statistiques</h2>

<p>🟢 Non utilisés : <?= $unused ?></p>
<p>🔴 Utilisés : <?= $used ?></p>

<hr>

<!-- ======================================================
     📋 LISTE VOUCHER
====================================================== -->

<h2>📋 Liste Voucher</h2>

<table border="1" cellpadding="10">

<tr>
    <th>ID</th>
    <th>Code</th>
    <th>Durée</th>
    <th>Prix</th>
    <th>Statut</th>
    <th>Action</th>
</tr>

<?php foreach($vouchers as $v){ ?>

<tr>

    <td><?= $v['id_voucher'] ?></td>

    <td>
        <b><?= $v['code'] ?></b>
    </td>

    <td>
        <?php
        $h = floor($v['duree_voucher']/60);
        $m = $v['duree_voucher']%60;

        echo $h."h ".$m."min";
        ?>
    </td>

    <td><?= $v['prix_voucher'] ?> Ar</td>

    <td>
        <?= ($v['statut']=='non_utilise')
            ? '🟢 Non utilisé'
            : '🔴 Utilisé'
        ?>
    </td>

    <td>

        <!-- DELETE -->
        <form method="POST"
              style="display:inline;"
              onsubmit="return confirm('Supprimer ?')">

            <input type="hidden"
                   name="delete_id"
                   value="<?= $v['id_voucher'] ?>">

            <button type="submit" name="delete_voucher">
                🗑️
            </button>

        </form>

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     🗑️ DELETE VOUCHER
====================================================== -->

<?php
if(isset($_POST['delete_voucher'])){

    $bd->prepare("
        DELETE FROM voucher
        WHERE id_voucher=?
    ")->execute([
        $_POST['delete_id']
    ]);

    echo "🗑️ Voucher supprimé";
}
?>

</body>
</html>
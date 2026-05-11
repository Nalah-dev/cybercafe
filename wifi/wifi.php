<?php
require_once "../config/db.php";
?>
<?php
require_once "../config/db.php";

$bd->query("
    UPDATE wifi_session
    SET statut = 'terminée'
    WHERE statut = 'en_cours'
    AND heure_fin <= NOW()
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion WiFi</title>
</head>
<body>

<h1>📶 Gestion WiFi</h1>

<hr>

<!-- ======================================================
     ➕ AJOUT WIFI
====================================================== -->

<h2>➕ Ajouter WiFi</h2>

<?php
if(isset($_POST['add_wifi'])){

    $nom = $_POST['nom_wifi'];
    $mdp = $_POST['mot_de_passe'];
    $bande = $_POST['bande'];

    $bd->prepare("
        INSERT INTO wifi
        (nom_wifi, mot_de_passe, bande, etat)
        VALUES (?, ?, ?, 'actif')
    ")->execute([$nom, $mdp, $bande]);

    echo "✅ WiFi ajouté";
}
?>

<form method="POST">

    <input type="text"
           name="nom_wifi"
           placeholder="Nom WiFi"
           required>

    <input type="text"
           name="mot_de_passe"
           placeholder="Mot de passe"
           required>

    <select name="bande">
        <option value="2.4GHz">2.4GHz</option>
        <option value="5GHz">5GHz</option>
    </select>

    <button type="submit" name="add_wifi">
        Ajouter
    </button>

</form>

<hr>

<!-- ======================================================
     🔍 RECHERCHE
====================================================== -->

<h2>🔍 Recherche WiFi</h2>

<form method="GET">

    <input type="text"
           name="search"
           placeholder="Nom ou bande">

    <button type="submit">
        Rechercher
    </button>

</form>

<hr>

<?php

$where = "1=1";
$params = [];

if(!empty($_GET['search'])){

    $where .= " AND (nom_wifi LIKE ? OR bande LIKE ?)";

    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$sql = "
SELECT *
FROM wifi
WHERE $where
ORDER BY id_wifi DESC
";

$req = $bd->prepare($sql);
$req->execute($params);

$wifi = $req->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ======================================================
     📊 STATS
====================================================== -->

<?php
$actif = $bd->query("
    SELECT COUNT(*) FROM wifi
    WHERE etat='actif'
")->fetchColumn();

$inactif = $bd->query("
    SELECT COUNT(*) FROM wifi
    WHERE etat='inactif'
")->fetchColumn();
?>

<h2>📊 Statistiques</h2>

<p>🟢 Actifs : <?= $actif ?></p>
<p>🔴 Inactifs : <?= $inactif ?></p>

<hr>

<!-- ======================================================
     📋 LISTE WIFI
====================================================== -->

<h2>📋 Liste WiFi</h2>

<table border="1" cellpadding="10">

<tr>
    <th>ID</th>
    <th>Nom WiFi</th>
    <th>Mot de passe</th>
    <th>Bande</th>
    <th>État</th>
    <th>Action</th>
</tr>

<?php foreach($wifi as $w){ ?>

<tr>

    <td><?= $w['id_wifi'] ?></td>

    <td><?= $w['nom_wifi'] ?></td>

    <td><?= $w['mot_de_passe'] ?></td>

    <td><?= $w['bande'] ?></td>

    <td>
        <?= ($w['etat']=='actif')
            ? '🟢 Actif'
            : '🔴 Inactif'
        ?>
    </td>

    <td>

        <!-- UPDATE -->
        <form method="POST" style="display:inline;">

            <input type="hidden"
                   name="id_wifi"
                   value="<?= $w['id_wifi'] ?>">

            <select name="etat">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>

            <button type="submit" name="update_wifi">
                OK
            </button>

        </form>

        <!-- DELETE -->
        <form method="POST"
              style="display:inline;"
              onsubmit="return confirm('Supprimer ?')">

            <input type="hidden"
                   name="delete_id"
                   value="<?= $w['id_wifi'] ?>">

            <button type="submit" name="delete_wifi">
                🗑️
            </button>

        </form>

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     🔄 UPDATE WIFI
====================================================== -->

<?php
if(isset($_POST['update_wifi'])){

    $bd->prepare("
        UPDATE wifi
        SET etat=?
        WHERE id_wifi=?
    ")->execute([
        $_POST['etat'],
        $_POST['id_wifi']
    ]);

    echo "🔄 WiFi mis à jour";
}
?>

<!-- ======================================================
     🗑️ DELETE WIFI
====================================================== -->

<?php
if(isset($_POST['delete_wifi'])){

    $bd->prepare("
        DELETE FROM wifi
        WHERE id_wifi=?
    ")->execute([
        $_POST['delete_id']
    ]);

    echo "🗑️ WiFi supprimé";
}
?>

</body>
</html>
<?php
require_once "../config/db.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Postes</title>
</head>
<body>

<h1>💻 Gestion des Postes</h1>

<hr>

<!-- ======================================================
     ➕ AJOUT POSTE
====================================================== -->
<h2>➕ Ajouter Poste</h2>

<?php
if(isset($_POST['add_poste'])){

    $num = $_POST['num_poste'];
    $desc = $_POST['description'];

    $bd->prepare("
        INSERT INTO poste (num_poste, description, etat)
        VALUES (?, ?, 'libre')
    ")->execute([$num,$desc]);

    echo "✅ Poste ajouté";
}
?>

<form method="POST">
    <input type="text" name="num_poste" placeholder="Numéro poste" required>
    <input type="text" name="description" placeholder="Description">
    <button type="submit" name="add_poste">Ajouter</button>
</form>

<hr>

<!-- ======================================================
     🔍 RECHERCHE POSTE
====================================================== -->
<h2>🔍 Recherche Poste</h2>

<form method="GET">
    <input type="text" name="search" placeholder="Num poste ou état">
    <button type="submit">Rechercher</button>
</form>

<hr>

<?php
$where = "1=1";
$params = [];

if(!empty($_GET['search'])){
    $where .= " AND (num_poste LIKE ? OR etat LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$sql = "SELECT * FROM poste WHERE $where ORDER BY id_poste DESC";
$req = $bd->prepare($sql);
$req->execute($params);
$postes = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ======================================================
     📊 STATS POSTE
====================================================== -->
<?php
$libre = $bd->query("SELECT COUNT(*) FROM poste WHERE etat='libre'")->fetchColumn();
$occupé = $bd->query("SELECT COUNT(*) FROM poste WHERE etat='occupé'")->fetchColumn();
$maintenance = $bd->query("SELECT COUNT(*) FROM poste WHERE etat='maintenance'")->fetchColumn();
?>

<h2>📊 Statistiques</h2>

<p>🟢 Libres : <?= $libre ?></p>
<p>🔴 Occupés : <?= $occupé ?></p>
<p>🛠 Maintenance : <?= $maintenance ?></p>

<hr>

<!-- ======================================================
     📋 LISTE POSTE
====================================================== -->
<h2>📋 Liste Postes</h2>

<table border="1" cellpadding="10">

<tr>
    <th>ID</th>
    <th>Num Poste</th>
    <th>Description</th>
    <th>État</th>
    <th>Action</th>
</tr>

<?php foreach($postes as $p){ ?>

<tr>

    <td><?= $p['id_poste'] ?></td>
    <td><?= $p['num_poste'] ?></td>
    <td><?= $p['description'] ?></td>

    <td>
        <?php
        if($p['etat']=='libre') echo "🟢 Libre";
        elseif($p['etat']=='occupé') echo "🔴 Occupé";
        else echo "🛠 Maintenance";
        ?>
    </td>

    <td>

        <!-- UPDATE ETAT -->
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id_poste" value="<?= $p['id_poste'] ?>">

            <select name="etat">
                <option value="libre">Libre</option>
                <option value="occupé">Occupé</option>
                <option value="maintenance">Maintenance</option>
            </select>

            <button type="submit" name="update">OK</button>
        </form>

        <!-- DELETE -->
        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="delete_id" value="<?= $p['id_poste'] ?>">
            <button type="submit" name="delete">🗑️</button>
        </form>

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     🔄 UPDATE POSTE ETAT
====================================================== -->
<?php
if(isset($_POST['update'])){

    $bd->prepare("
        UPDATE poste SET etat=?
        WHERE id_poste=?
    ")->execute([
        $_POST['etat'],
        $_POST['id_poste']
    ]);

    echo "🔄 Poste mis à jour";
}
?>

<!-- ======================================================
     🗑️ DELETE POSTE
====================================================== -->
<?php
if(isset($_POST['delete'])){

    $bd->prepare("
        DELETE FROM poste WHERE id_poste=?
    ")->execute([$_POST['delete_id']]);

    echo "🗑️ Poste supprimé";
}
?>

</body>
</html>
<?php
require_once "../config/db.php";

date_default_timezone_set('Indian/Antananarivo');

/* ======================================================
   ➕ AJOUT FORFAIT
====================================================== */

if(isset($_POST['add_forfait'])){

    $id_wifi = $_POST['id_wifi'];
    $nom = trim($_POST['nom_forfait']);
    $heure = (int) $_POST['heure'];
    $minute = (int) $_POST['minute'];

    // sécurité
    if($heure < 0) $heure = 0;
    if($minute < 0) $minute = 0;

    if($heure > 23) $heure = 23;
    if($minute > 59) $minute = 59;

    // durée totale
    $duree = ($heure * 60) + $minute;

    if($duree <= 0){

        echo "❌ Durée invalide";

    }else{

        // prix automatique
        $prix =
        ceil(($duree / 60) * 2000);

        // insert
        $req = $bd->prepare("
            INSERT INTO forfait_wifi
            (
                id_wifi,
                nom_forfait,
                duree,
                prix,
                statut
            )
            VALUES
            (?, ?, ?, ?, 'actif')
        ");

        $req->execute([
            $id_wifi,
            $nom,
            $duree,
            $prix
        ]);

        echo "✅ Forfait ajouté";
    }
}

/* ======================================================
   🗑️ DELETE
====================================================== */

if(isset($_POST['delete_forfait'])){

    $delete = $bd->prepare("
        DELETE FROM forfait_wifi
        WHERE id_forfait=?
    ");

    $delete->execute([
        $_POST['delete_id']
    ]);

    echo "🗑️ Forfait supprimé";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Forfait WiFi</title>
</head>
<body>

<h1>📦 Gestion Forfait WiFi</h1>

<hr>

<!-- ======================================================
     ➕ AJOUT FORFAIT
====================================================== -->

<h2>➕ Ajouter Forfait</h2>

<form method="POST">

    <!-- WIFI -->

    <label>WiFi :</label>

    <select name="id_wifi" required>

        <option value="">
            Choisir WiFi
        </option>

        <?php

        $wifi = $bd->query("
            SELECT *
            FROM wifi
            WHERE etat='actif'
        ");

        foreach($wifi as $w){

            echo "
            <option value='{$w['id_wifi']}'>
                {$w['nom_wifi']}
                ({$w['bande']})
            </option>
            ";
        }

        ?>

    </select>

    <br><br>

    <!-- NOM -->

    <label>Nom forfait :</label>

    <input type="text"
           name="nom_forfait"
           placeholder="Ex: Standard"
           required>

    <br><br>

    <!-- HEURE -->

    <label>Heure :</label>

    <input type="number"
           name="heure"
           id="heure"
           min="0"
           max="23"
           value="0">

    <br><br>

    <!-- MINUTE -->

    <label>Minute :</label>

    <input type="number"
           name="minute"
           id="minute"
           min="0"
           max="59"
           value="30">

    <br><br>

    <!-- PRIX -->

    <label>Prix auto :</label>

    <input type="text"
           id="prix"
           readonly>

    <br><br>

    <button type="submit"
            name="add_forfait">

        Ajouter Forfait

    </button>

</form>

<hr>

<!-- ======================================================
     📋 LISTE FORFAIT
====================================================== -->

<h2>📋 Liste Forfaits</h2>

<?php

$forfaits = $bd->query("
    SELECT f.*,
           w.nom_wifi,
           w.bande

    FROM forfait_wifi f

    LEFT JOIN wifi w
    ON f.id_wifi = w.id_wifi

    ORDER BY f.id_forfait DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<table border="1" cellpadding="10">

<tr>

    <th>ID</th>

    <th>WiFi</th>

    <th>Bande</th>

    <th>Nom</th>

    <th>Durée</th>

    <th>Prix</th>

    <th>Statut</th>

    <th>Delete</th>

</tr>

<?php foreach($forfaits as $f){ ?>

<tr>

    <td>
        <?= $f['id_forfait'] ?>
    </td>

    <td>
        <?= $f['nom_wifi'] ?>
    </td>

    <td>
        <?= $f['bande'] ?>
    </td>

    <td>
        <?= $f['nom_forfait'] ?>
    </td>

    <td>

    <?php

    $h = floor($f['duree'] / 60);

    $m = $f['duree'] % 60;

    echo $h."h ".$m." min";

    ?>

    </td>

    <td>
        <?= $f['prix'] ?> Ar
    </td>

    <td>

    <?php

    if($f['statut']=='actif'){

        echo "🟢 Actif";

    }else{

        echo "🔴 Inactif";
    }

    ?>

    </td>

    <td>

        <form method="POST"
              onsubmit="return confirm('Supprimer ?')">

            <input type="hidden"
                   name="delete_id"
                   value="<?= $f['id_forfait'] ?>">

            <button type="submit"
                    name="delete_forfait">

                🗑️

            </button>

        </form>

    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     📊 STATS
====================================================== -->

<?php

$total = $bd->query("
    SELECT COUNT(*)
    FROM forfait_wifi
")->fetchColumn();

?>

<h2>📊 Statistiques</h2>

<p>
📦 Total forfaits :
<?= $total ?>
</p>

<hr>

<!-- ======================================================
     💰 JS PRIX AUTO
====================================================== -->

<script>

const heure =
document.getElementById('heure');

const minute =
document.getElementById('minute');

const prix =
document.getElementById('prix');

function calculPrix(){

    let h =
    parseInt(heure.value) || 0;

    let m =
    parseInt(minute.value) || 0;

    if(h > 23){

        h = 23;
        heure.value = 23;
    }

    if(m > 59){

        m = 59;
        minute.value = 59;
    }

    let total =
    (h * 60) + m;

    let montant =
    Math.ceil((total / 60) * 2000);

    prix.value =
    montant + " Ar";
}

heure.addEventListener(
    'input',
    calculPrix
);

minute.addEventListener(
    'input',
    calculPrix
);

calculPrix();

</script>

</body>
</html>
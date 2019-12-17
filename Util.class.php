<?php
if (!isset($no_database)) {
    require_once('Database.class.php');
}

class Util {
    static function isLocalHost() {
        return !(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'ducksmanager' && strpos($_SERVER['HTTP_HOST'],'localhost')===false);
    }

    static function magazinesSupprimesInducks() {
        $requete_magazines='SELECT Pays, Magazine FROM numeros GROUP BY Pays, Magazine ORDER BY Pays, Magazine';
        $resultat_magazines=DM_Core::$d->requete($requete_magazines);
        $pays='';
        $magazines_inducks= [];
        foreach($resultat_magazines as $pays_magazine) {
            if ($pays!==$pays_magazine['Pays']) {
                $magazines_inducks=Inducks::get_liste_magazines($pays_magazine['Pays']);
            }
            if (!array_key_exists($pays_magazine['Magazine'], $magazines_inducks)) {
                echo $pays_magazine['Pays'] . '/' . $pays_magazine['Magazine'] . ' n\'existe plus<br />';
            }
            $pays=$pays_magazine['Pays'];
        }
    }

    static function lire_depuis_fichier($nom_fichier) {
        $inF = fopen($nom_fichier,"r");
        $str='';
        if ($inF === false) {
            echo 'Le fichier '.$nom_fichier.' n\'existe pas';
        }
        else {
            while (!feof($inF)) {
                $str.=fgets($inF, 4096);
            }
        }
        return $str;
    }

    static function ecrire_dans_fichier($nom_fichier,$str,$a_la_suite=false) {
        $inF = fopen($nom_fichier,$a_la_suite ? 'a+' : 'w');
        fwrite($inF,$str);
        fclose($inF);
    }

    static function exit_if_not_logged_in() {
        if (!isset($_SESSION['user'])) {
            header('Location: https://ducksmanager.net');
            exit(0);
        }
    }
}

if (isset($_GET['magazines_supprimes'])) {
    Util::magazinesSupprimesInducks();
}

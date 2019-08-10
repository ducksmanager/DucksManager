<?php
if (!isset($no_database)) {
    require_once('Database.class.php');
}

class Util {
    static $nom_fic;
    static function get_page($url) {
        $handle = @fopen($url, "r");

        if (isset($_GET['dbg'])) {
            echo $url;
        }
        if ($handle) {
            $buffer="";
            while (!feof($handle)) {
                $buffer.= fgets($handle, 4096);
            }
            fclose($handle);
            return $buffer;
        }

        return ERREUR_CONNEXION_INDUCKS;
    }

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

    static function get_random_string($length = 16) {
        $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
        $validCharNumber = strlen($validCharacters);
        $result = "";
        for ($i = 0; $i < $length; $i++) {
            $result.=$validCharacters[mt_rand(0, $validCharNumber - 1)];
        }

        return $result;
    }

    /**
     * @param $destination string
     * @param $sourceObject stdClass
     * @return stdClass
     */
    static function cast($destination, $sourceObject) {
        if (is_string($destination)) {
            $destination = new $destination();
        }
        $sourceReflection = new ReflectionObject($sourceObject);
        $destinationReflection = new ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination,$value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }

    /**
     * @return DateTime
     */
    static function get_derniere_visite_utilisateur() {
        $lastVisitXPath = '//result/lastVisits/row[position()=2]/lastActionTimestamp';

        $piwik = parse_ini_file('piwik.ini');

        $xml_obj = @simplexml_load_file("https://piwik.ducksmanager.net/?module=API&method=Live.getVisitorProfile&idSite=1&format=xml&segment=customVariableValue1=={$_SESSION['user']}&limitVisits=&token_auth={$piwik['token_auth']}");

        if (empty($xml_obj)) {
            return null;
        }

        $lastVisit = $xml_obj->xpath($lastVisitXPath);
        if ($lastVisit === false || count($lastVisit) === 0) {
            return null;
        }
        return new DateTime(date('Y-m-d H:i:s', (integer) $lastVisit[0]));
    }

}

if (isset($_GET['magazines_supprimes'])) {
    Util::magazinesSupprimesInducks();
}

<?php

include_once('auth.php');

function exporter($requete, $cheminCsv)
{
    $result = mysql_query($requete);
    if ($result) {
        $num_fields = mysql_num_fields($result);
        $headers = array();
        for ($i = 0; $i < $num_fields; $i++) {
            $headers[] = mysql_field_name($result, $i);
        }
        $fp = fopen($cheminCsv, 'w');
        if ($fp && $result) {
            fputcsv($fp, $headers);
            while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
                fputcsv($fp, array_values($row));
            }
        }
    }
}

if (isset($_GET['csv'])) {
    $csv = $_GET['csv'];
    switch($csv) {
        case 'numeros':
            $requete = "
                SELECT ID_Utilisateur, CONCAT(Pays,'/', Magazine) AS Publicationcode, Numero
                FROM numeros";
        break;
        case 'auteurs_pseudos':
            $requete = "
                SELECT ID_user, NomAuteurAbrege, Notation
                FROM auteurs_pseudos
                WHERE DateStat IS NULL AND NomAuteurAbrege <> ''";
        break;
    }

    if (isset($requete)) {
        $cheminCsv = "export/$csv.csv";
        exporter($requete, $cheminCsv);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename("$csv.csv").'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($cheminCsv));
        readfile($cheminCsv);
    }
}
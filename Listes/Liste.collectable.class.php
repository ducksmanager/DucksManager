<?php
/*
 * Developpement : Bruno Perel (admin[at]ducksmanager[dot]net)
 * (c)2003-2019 Cette classe est soumise à copyright
 */
include_once 'locales/lang.php';
include_once 'Database.class.php';
require_once 'Format_liste.php';
class collectable extends Format_liste {
    static $titre='CollecTable';
    static $max_centaines=0;
    static $max_diz_et_unites=1;
    function __construct() {
        $this->les_plus= [COLLECTABLE_PLUS_1,COLLECTABLE_PLUS_2,COLLECTABLE_PLUS_3];
        $this->les_moins= [COLLECTABLE_MOINS_1,COLLECTABLE_MOINS_2];
        $this->description=COLLECTABLE_DESCRIPTION;
        $this->ajouter_parametres([
            'nb_numeros_ligne'=>new Parametre_valeurs('Nombre de numéros par ligne', [25,50,100],50,50)]);
    }

    static function est_listable($numero) {
        return (preg_match('#^[\d]+$#', $numero) && $numero !=='0') || est_double($numero);
    }

    function afficher($liste) {
        ?>
            <style type="text/css">
                table {
                    color: black;
                    font: 11px/15px verdana, arial, sans-serif;
                }

                table.collectable, table.legendes {
                    width: 90%;
                }

                td.legende_numeros, td.legende_magazines, td.achats {
                    vertical-align: top;
                    border-left: 1px solid gray;
                    padding: 8px;
                }

                table.collectable {
                    border: solid 1px black;
                    border-collapse: collapse;
                }

                table.collectable tr {
                    height: 15px;
                }

                table.collectable tr td {
                    text-align: left;
                    border: solid 1px black;
                    vertical-align: top;
                    max-width: 25px;
                    word-wrap: break-word;
                }

                table.collectable tr td.libelle_ligne,
                table.collectable tr td.total_ligne {
                    text-align: center;
                    vertical-align: middle;
                    max-width: none;
                    white-space: nowrap;
                }
            </style>
        <?php
        $nb_lignes=100/$this->p('nb_numeros_ligne');
            ?><table class="collectable">
                <?php
                for ($i=1;$i<=$nb_lignes;$i++) {?>
                    <tr>
                        <td></td><?php
                        for ($j=$this->p('nb_numeros_ligne')*($i-1)+1;$j<=$this->p('nb_numeros_ligne')*$i;$j++) {?>
                            <td><?=$j?></td>
                        <?php }
                        if ($i===1) {?>
                        <td rowspan="<?=$nb_lignes?>">
                            <?=TOTAL?>
                        </td>
                        <?php } ?>
                    </tr><?php
                }
            global $centaines_utilisees;
            $centaines_utilisees= [];
            ksort($liste);

            foreach($liste as $pays=>$numeros_pays) {
                ksort($numeros_pays);
                foreach($numeros_pays as $magazine=>$numeros) {
                   $total_magazine = 0;
                   global $liste_numeros;
                   global $liste_numeros_doubles;
                   $liste_numeros = [];
                   $liste_numeros_doubles = [];
                   $liste_non_numeriques = [];
                   foreach($numeros as [,,$numero]) {
                        $total_magazine++;
                        if (est_double($numero)) {
                            preg_match(self::$regex_numero_double, $numero, $numero);
                            $premier_numero = $numero[1] . $numero[2];
                            $deuxieme_numero = $numero[1] . $numero[3];
                            ajouter_a_liste($premier_numero, true);
                            ajouter_a_liste($deuxieme_numero, true);
                        }
                        else if (!self::est_listable($numero)) {
                            $liste_non_numeriques[] = urldecode($numero);
                        }
                        else {
                            ajouter_a_liste($numero);
                        }
                    }
                    $non_numeriques=count($liste_non_numeriques) > 0;
                    ?>
                    <tr>
                        <td class="libelle_ligne" rowspan="<?=$nb_lignes+($non_numeriques?1:0)?>">
                            <img alt="<?=$pays?>" src="images/flags/<?=$pays?>.png" />
                            <br />
                            <?=$magazine?>
                            <br />
                        </td>
                    <?php

                    for($i=1;$i<=100;$i++) {
                        if ($i % $this->p('nb_numeros_ligne') === 1 && $i !== 1) {
                            ?><tr><?php
                        }
                        ?><td><?php
                        global $contenu;
                        $contenu='';
                        for ($j=0; $j<= self::$max_centaines; $j++) {
                            if (array_key_exists($j*100+$i,$liste_numeros)) {
                                for ($k=0;$k<$liste_numeros[$j*100+$i];$k++) {
                                    if (array_key_exists($j*100+$i, $liste_numeros_doubles)) {
                                        if (array_key_exists($j*100+$i+1, $liste_numeros_doubles)) {
                                            $contenu .= number_to_letter($j) . '&gt;';
                                        }
                                        else {
                                            $contenu .= '&lt;' . number_to_letter($j);
                                        }
                                    }
                                    else {
                                        $contenu.=number_to_letter($j);
                                    }
                                }
                            }
                        }
                        echo $contenu;
                        ?></td><?php
                        if ($i % $this->p('nb_numeros_ligne') === 0) {
                            if ($i === $this->p('nb_numeros_ligne')) {
                                ?><td class="total_ligne" rowspan="<?=$nb_lignes+($non_numeriques?1:0)?>"><?=$total_magazine?></td><?php
                            }
                            ?>
                            </tr><?php
                        }
                    }

                    if (count($liste_non_numeriques)>0) {
                        ?><tr>
                            <td colspan="<?=$this->p('nb_numeros_ligne')?>">
                                <?=AUTRES?> : <?=implode(', ',$liste_non_numeriques)?>
                            </td>
                        </tr><?php
                    }
                }
            }

            ?></table>
            <table class="legendes">
                <tr>
                    <td class="legende_numeros"><?php
            if (count($centaines_utilisees)>0) {
                        ?><table>
                            <tr>
                                <td align="center" colspan="2">
                                    <u><?=ucfirst(NUMEROS)?></u>
                                </td></tr><tr><?php
                for ($i=0;$i<= self::$max_centaines;$i++) {
                            ?><td>
                                <?=number_to_letter($i)?>:<?=($i*100+1)?>-&gt;<?=(($i+1)*100)?>
                             </td><?php
                        if ($i%2 === 1) {
                          ?></tr>
                            <tr><?php
                        }
                }
                          ?></tr>
                        </table><?php
            }
            ?>
                    </td><?php
            $nb_magazines=0;
            foreach($liste as $numeros_pays) {
                $nb_magazines+=count($numeros_pays);
            }

            if ($nb_magazines >= 1) {
                ?>
                    <td class="legende_magazines">
                        <table><?php
                $publication_codes= [];
                foreach($liste as $pays=>$numeros_pays) {
                    foreach(array_keys($numeros_pays) as $magazine) {
                        $publication_codes[]=$pays.'/'.$magazine;
                    }
                }
                $noms_magazines = Inducks::get_noms_complets_magazines($publication_codes);
                $i=0;
                            ?><tr>
                                <td align="center" colspan="4">
                                    <u><?=PUBLICATIONS?></u>
                                </td></tr><tr><?php
                foreach($liste as $pays=>$numeros_pays) {
                    ksort($numeros_pays);
                    foreach($numeros_pays as $magazine=>$numeros) {
                        if (!array_key_exists($pays.'/'.$magazine, $noms_magazines)) { // Magazine ayant disparu d'Inducks
                            continue;
                        }
                        ?><td>
                            <img alt="<?=$pays?>" src="images/flags/<?=$pays?>.png" />
                            &nbsp;<?=$magazine?>
                        </td>
                        <td>
                            <?=$noms_magazines[$pays.'/'.$magazine]?>
                        </td><?php
                        if($i%2 === 1) {
                            ?></tr><tr><?php
                        }
                        $i++;
                    }
                }?>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table><tr><td align="center" class="achats">
                        <!-- <u><?=ACHATS?></u> -->
                    </td></td></tr></table>

      <?php }?>
                </tr>
            </table>
            <?php
    }
}
function number_to_letter($number) {
    global $centaines_utilisees;
    $centaines_utilisees[$number]=true;
    if ($number<26) {
        return chr(97 + $number);
    }

    return chr(65 - 26 + $number);
}

function est_double($numero) {
    if (preg_match(collectable::$regex_numero_double, $numero, $numero) === 0) {
        return false;
    }
    return (int)($numero[1] . $numero[2]) +1 === (int)($numero[1] . $numero[3]);
}

function ajouter_a_liste($numero,$est_double=false) {
    global $liste_numeros;global $liste_numeros_doubles;
    if (false!== array_key_exists($numero,$liste_numeros)) {
        $liste_numeros[$numero]++;
    }
    else {
        $liste_numeros[$numero] = 1;
    }

    $centaine=get_nb_centaines($numero);
    $diz_et_unites=$numero-100*$centaine;
    if ($diz_et_unites === 0) {
        $diz_et_unites = 100;
        $centaine--;
    }
    if ($centaine > collectable::$max_centaines) {
        collectable::$max_centaines = $centaine;
    }
    if ($diz_et_unites > collectable::$max_diz_et_unites) {
        collectable::$max_diz_et_unites = $diz_et_unites;
    }

    if ($est_double) {
        if (array_key_exists($numero,$liste_numeros_doubles)) {
            $liste_numeros_doubles[$numero]++;
        }
        else {
            $liste_numeros_doubles[$numero] = 1;
        }
    }
}

function get_nb_centaines($numero) {
    return (int)($numero / 100);
}
?>

<?php
include_once('Texte.class.php');
include_once('IntervalleValidite.class.php');
include_once('DucksManager_Core.class.php');
class Edge {
    var $pays;
    var $magazine;
    var $numero;
    var $textes=array();
    var $largeur=20;
    var $hauteur=200;
    var $image;
    var $o;
    var $est_visible=true;
    var $magazine_est_inexistant=false;
    var $intervalles_validite=array();
    var $en_cours=array();
    static $grossissement=10;
    static $grossissement_affichage=1.5;
    static $largeur_numeros_precedents=0;
    static $d;
    
    function Edge($pays=null,$magazine=null,$numero=null) {
        $this->magazine_est_inexistant=false;
        if (is_null($pays))
            return;
        $this->pays=$pays;$this->magazine=$magazine;$this->numero=$numero;
        $this->numero=str_replace(' ','',str_replace('+','',$this->numero));
        list($this->magazine,$this->numero)=Inducks::get_vrais_magazine_numero($this->pays, $this->magazine, $this->numero);
        
        if (file_exists('edges/'.$this->pays.'/'.$this->magazine.'.edge.class.php')) {
            require_once('edges/'.$this->pays.'/'.$this->magazine.'.edge.class.php');
            $nom_classe=$this->pays.'_'.$this->magazine;
            $this->o=new $nom_classe($this->numero);
            if ($this->o->largeur==$this->largeur && $this->o->hauteur==$this->hauteur) {
                list($largeur_defaut,$hauteur_defaut)=$this->o->getLargeurHauteurDefaut();
                $this->o->largeur=$largeur_defaut;
                $this->o->hauteur=$hauteur_defaut;
                
            }
            $intervalle_validite=new IntervalleValidite($this->o->intervalles_validite);
            if (!$intervalle_validite->estValide($this->numero))
                $this->est_visible=false;
        }
        else {
            $this->o=clone $this;
            $this->est_visible=false;
            $this->largeur*=Edge::$grossissement;
            $this->hauteur*=Edge::$grossissement;
            $this->magazine_est_inexistant=true;
        }
    }

    function getLargeurHauteurDefaut() {
        return array($this->largeur,$this->hauteur);
    }
    
    static function getEtagereHTML($br=true) {
        $code= '<div class="etagere" style="width:'.Etagere::$largeur.';'
                                          .'background-image: url(\'edges/textures/'.Etagere::$texture2.'/'.Etagere::$sous_texture2.'.jpg\')">&nbsp;</div>';
        if ($br===true)
            $code.= '<br />';
        return $code;
    }
    function getImgHTML($regen=false) {
        $code='';
        $numero_clean=str_replace('+','',$this->numero);
        $requete_recherche_numero_reference='SELECT NumeroReference FROM tranches_doublons WHERE Pays LIKE \''.$this->pays.'\' AND Magazine LIKE \''.$this->magazine.'\' AND Numero LIKE \''.$numero_clean.'\'';
        $resultat_numero_reference=DM_Core::$d->requete_select($requete_recherche_numero_reference);
        if (count($resultat_numero_reference) ==0)
            $numero_clean_reference=$numero_clean;
        else
            $numero_clean_reference=$resultat_numero_reference[0]['NumeroReference'];
        if (Edge::$largeur_numeros_precedents + $this->o->largeur > Etagere::$largeur) {
            $code.=Edge::getEtagereHTML();
            Edge::$largeur_numeros_precedents=0;
        }
        if ($this->o->hauteur > Etagere::$hauteur_max_etage)
            Etagere::$hauteur_max_etage = $this->o->hauteur ;
        $code.= '<img class="tranche" ';
        $image='edges/'.$this->pays.'/gen/'.$this->magazine.'.'.$numero_clean_reference.'.png';
        $fichier_existe=file_exists($image);
        if ($fichier_existe && !$regen) {
            /*if (getEstVisible($this->pays, $this->magazine, $numero_clean)===true) {
                $image=imagecreatefrompng('edges/'.$this->pays.'/gen/'.$this->magazine.'.'.$numero_clean_reference.'.png');
                $gris_251=imagecolorallocate($image, 251,251,251);
                $gris_250=imagecolorallocate($image, 250,250,250);
                $gris_249=imagecolorallocate($image, 249,249,249);
                $e=new Edge($this->pays,$this->magazine,$this->numero);
                $e=$e->o;
                if (imagecolorat($image, $e->largeur/2, $e->largeur/2) == $gris_249
                 || imagecolorat($image, $e->largeur/2, $e->largeur/2) == $gris_250
                 || imagecolorat($image, $e->largeur/2, $e->largeur/2) == $gris_251) {
                     $fichier_existe=false;
                }
                else
                    $code.='name="edges/'.$this->pays.'/gen/'.$this->magazine.'.'.$numero_clean.'.png" ';
            }
            else*/
                $code.='name="'.$this->pays.'/'.$this->magazine.'.'.$numero_clean_reference.'" ';
            
        }
        else {
            $code.='name="Edge.class.php?pays='.$this->pays.'&amp;magazine='.$this->magazine.'&amp;numero='.$numero_clean_reference.'&amp;grossissement='.Edge::$grossissement.'" ';
        }
        $code.='width="'.$this->o->largeur.'" height="'.$this->o->hauteur.'" id="'.$this->pays.'/'.$this->magazine.'.'.$numero_clean.'"/>';
        
        Edge::$largeur_numeros_precedents+=$this->o->largeur;
        return $code;
    }

    function dessiner_tranche($regen=false) {
        $intervalle_validite=new IntervalleValidite($this->intervalles_validite);
        if ($intervalle_validite->estValide($this->numero)) {
            $this->image=$this->dessiner();
            $this->dessiner_contour();
        }
        else
            $this->image=$this->dessiner_defaut();
        foreach($this->textes as $texte) {
            imagettftext($this->image,$texte->taille,$texte->angle,$texte->pos_x,$texte->pos_y,$texte->couleur,$texte->police,$texte->texte);
        }
        imageantialias($this->image, true);
        if (!is_dir('edges/'.$this->pays)) {
            mkdir('edges/'.$this->pays);
            mkdir('edges/'.$this->pays.'/gen');
        }
        $reduction=Edge::$grossissement/Edge::$grossissement_affichage;
        $largeur_image_finale=intval($this->largeur/$reduction);
        $hauteur_image_finale=intval($this->hauteur/$reduction);
        $image2=imagecreatetruecolor($largeur_image_finale, $hauteur_image_finale);
        imagecopyresampled($image2, $this->image, 0, 0, 0, 0, $largeur_image_finale, $hauteur_image_finale, $this->largeur, $this->hauteur);
        
        $numero_clean=str_replace(' ','',str_replace('+','',$this->numero));
        imagepng($image2,'edges/'.$this->pays.'/gen/'.$this->magazine.'.'.$numero_clean.'.png');
        imagedestroy($image2);
        imagepng($this->image);
        imagedestroy($this->image);
    }

    function dessiner_defaut() {
        $this->image=imagecreatetruecolor($this->largeur,$this->hauteur);
        $blanc=imagecolorallocate($this->image,255,255,255);
        $noir = imagecolorallocate($this->image, 0, 0, 0);
        imagefilledrectangle($this->image, 0, 0, $this->largeur-2, $this->hauteur-2, $blanc);
        imagettftext($this->image,$this->largeur/3,90,$this->largeur*7/10,$this->hauteur-$this->largeur*4/5,
         $noir,'edges/Verdana.ttf','['.$this->pays.' / '.$this->magazine.' / '.$this->numero.']');
        $this->dessiner_contour();
        $gris_250=imagecolorallocate($this->image, 250,250,250);
        imageantialias($this->image, true);
        imagefilledrectangle($this->image, $this->largeur/4,$this->largeur/4, $this->largeur*3/4,$this->largeur*3/4,$gris_250);
        return $this->image;
	}

    function getColorsFromDB($default_color=array(255,255,255),$parametre_autre=null) {
        $requete_couleurs='SELECT CouleurR, CouleurG, CouleurB FROM bibliotheque_options WHERE Pays LIKE \''.$this->pays.'\' AND Magazine LIKE \''.$this->magazine.'\' AND Numero LIKE \''.$this->numero.'\'';
        if (!is_null($parametre_autre))
            $requete_couleurs.=' AND Autre LIKE \''.$parametre_autre.'\'';
        else
            $requete_couleurs.=' AND (Autre IS NULL || Autre LIKE \'\')';
        $resultat=DM_Core::$d->requete_select($requete_couleurs);
        if (count($resultat)==0)
            return $default_color;
        return array($resultat[0]['CouleurR'], $resultat[0]['CouleurG'], $resultat[0]['CouleurB']);
    }

    function getDataFromDB($default_text='') {
        $requete_couleurs='SELECT Autre FROM bibliotheque_options WHERE Pays LIKE \''.$this->pays.'\' AND Magazine LIKE \''.$this->magazine.'\' AND Numero LIKE \''.$this->numero.'\'';
        $resultat=DM_Core::$d->requete_select($requete_couleurs);
        if (count($resultat)==0)
            return $default_text;
        return $resultat[0]['Autre'];
    }

    function agrafer() {
        $noir=imagecolorallocate($this->image, 0, 0, 0);
        imagefilledrectangle($this->image, $this->largeur/2 -.25*Edge::$grossissement, $this->hauteur/5, $this->largeur/2 +.25*Edge::$grossissement, $this->hauteur/4, $noir);
        imagefilledrectangle($this->image, $this->largeur/2 -.25*Edge::$grossissement, $this->hauteur*4/5, $this->largeur/2 +.25*Edge::$grossissement, $this->hauteur*4/5 - ($this->hauteur/4 - $this->hauteur/5), $noir);
    }
    
    function agrafer_detail($y1,$y2,$taille) {
        $noir=imagecolorallocate($this->image, 0, 0, 0);
        imagefilledrectangle($this->image, $this->largeur/2 -.25*Edge::$grossissement, $y1, $this->largeur/2 +.25*Edge::$grossissement, $y1+$taille, $noir);
        imagefilledrectangle($this->image, $this->largeur/2 -.25*Edge::$grossissement, $y2, $this->largeur/2 +.25*Edge::$grossissement, $y2+$taille, $noir);
    }

    function placer_image($sous_image, $position='haut', $decalage=array(0,0), $compression_largeur=1, $compression_hauteur=1) {
        if (is_string($sous_image)) {
            $extension_image=strtolower(substr($sous_image, strrpos($sous_image, '.')+1,strlen($sous_image)-strrpos($sous_image, '.')-1));
            $fonction_creation_image='imagecreatefrom'.$extension_image.'_getimagesize';
            $chemin_reel=(strpos($sous_image, 'images_myfonts')!==false) ? $sous_image : $this->getChemin().'/'.$sous_image;
            list($sous_image,$width,$height)=call_user_func($fonction_creation_image,$chemin_reel);
        }
        else {
            $width=imagesx($sous_image);
            $height=imagesy($sous_image);
        }
        $hauteur_sous_image=$this->largeur*($height/$width);
        if ($position=='bas') {
            $decalage[1]=$this->hauteur-$hauteur_sous_image-$decalage[1];
        }
        imagecopyresampled ($this->image, $sous_image, $decalage[0], $decalage[1], 0, 0, $this->largeur*$compression_largeur, $hauteur_sous_image*$compression_hauteur, $width, $height);
        return $sous_image;
    }

    function dessiner_contour() {
        $noir=imagecolorallocate($this->image, 0, 0, 0);
        for ($i=0;$i<.15*Edge::$grossissement;$i++)
            imagerectangle($this->image, $i, $i, $this->largeur-1-$i, $this->hauteur-1-$i, $noir);
    }

    static function getPourcentageVisible($get_html=false, $regen=false, $user_unique=true) {
        include_once('Database.class.php');
        @session_start();
        if ($user_unique===true)
            $ids_users=array(DM_Core::$d->user_to_id($_SESSION['user']));
        else {
            $pourcentages_visible=array();
            $requete_users='SELECT ID, username FROM users';
            $resultat_users=DM_Core::$d->requete_select($requete_users);
            foreach($resultat_users as $user)
                $ids_users[$user['username']]=$user['ID'];
        }
        foreach($ids_users as $username=>$id_user) {
            $l=DM_Core::$d->toList($id_user);
            $texte_final='';
            $total_numeros=0;
            $total_numeros_visibles=0;
            DM_Core::$d->maintenance_ordre_magazines($id_user);
            $requete_ordre_magazines='SELECT Pays,Magazine,Ordre FROM bibliotheque_ordre_magazines WHERE ID_Utilisateur='.$id_user.' ORDER BY Ordre';
            $resultat_ordre_magazines=DM_Core::$d->requete_select($requete_ordre_magazines);
            foreach($resultat_ordre_magazines as $ordre) {
                $pays=$ordre['Pays'];
                $magazine=$ordre['Magazine'];
                $numeros=$l->collection[$pays][$magazine];
                if ($get_html === true)
                    sort($numeros);
                $total_numeros+=count($numeros);
                foreach($numeros as $numero) {
                    if ($get_html) {
                        list($texte,$est_visible)=getEstVisible($pays, $magazine, $numero[0],true, $regen);
                        $texte_final.=$texte;
                    }
                    else
                        $est_visible=getEstVisible($pays, $magazine, $numero[0]);
                    if ($est_visible===true)
                        $total_numeros_visibles++;
                }
            }
            $pourcentage_visible=$total_numeros==0 ? 0 : intval(100*$total_numeros_visibles/$total_numeros);
            if ($user_unique===true) {
                if ($get_html)
                    return array($texte_final, $pourcentage_visible);
                else
                    return $pourcentage_visible;
            }
            elseif ($total_numeros>0)
                $pourcentages_visible[' '.$username]=$pourcentage_visible;
        }
        return $pourcentages_visible;
    }

    function getChemin() {
        return 'edges/'.$this->pays.'/elements';
    }

}
DM_Core::$d->requete('SET NAMES UTF8');
if (isset($_POST['get_visible'])) {
    include_once ('locales/lang.php');
    list($nom_complet_pays,$nom_complet_magazine)=$nom_complet_magazine=DM_Core::$d->get_nom_complet_magazine($_POST['pays'], $_POST['magazine']);
    ?>
    <div class="titre_magazine"><?=$nom_complet_magazine?></div><br />
    <div class="numero_magazine">n&deg;<?=$_POST['numero']?></div><br />
    <?php
    if (!getEstVisible($_POST['pays'], strtoupper($_POST['magazine']), $_POST['numero'])) {
        ?>
        <?=TRANCHE_NON_DISPONIBLE1?><br /><?=TRANCHE_NON_DISPONIBLE2?><a class="lien_participer" target="_blank" href="?action=bibliotheque&onglet=participer"><?=ICI?></a><?=TRANCHE_NON_DISPONIBLE3?>
        <?php
    }
    ?>
        <div style="position:absolute;width:100%;text-align:center;border-top:1px solid black;bottom:10px"><?=DECOUVRIR_COUVERTURE?></div>
    <?php
}
elseif (isset($_GET['pays']) && isset($_GET['magazine']) && isset($_GET['numero'])) {
    if (isset($_GET['grossissement']))
        Edge::$grossissement=$_GET['grossissement'];
    if (!isset($_GET['debug']))
        header('Content-type: image/png');
    $e=new Edge($_GET['pays'],$_GET['magazine'],$_GET['numero']);
    $o=$e->o;
    $regen=isset($_GET['regen']);
    $o->dessiner_tranche($regen);
}
/*
 * Table bibliotheque_options
*/
elseif (isset($_POST['get_texture'])) {
    $id_user=DM_Core::$d->user_to_id($_SESSION['user']);
    $requete_texture='SELECT Bibliotheque_Texture'.$_POST['n'].' FROM users WHERE ID LIKE \''.$id_user.'\'';
    $resultat_texture=DM_Core::$d->requete_select($requete_texture);
	$rep = "edges/textures";
    $dir = opendir($rep);
    while ($f = readdir($dir)) {
        if( $f!=='.' && $f!=='..') {
            ?>
            <option 
            <?php
            if ($f==$resultat_texture[0]['Bibliotheque_Texture'.$_POST['n']])
                echo 'selected="selected" ';?>
            value="<?=$f?>"
            ><?=constant('TEXTURE__'.strtoupper($f))?></option>
            <?php
        }
    }
}

elseif (isset($_POST['get_sous_texture'])) {
    $id_user=DM_Core::$d->user_to_id($_SESSION['user']);
    $requete_texture='SELECT Bibliotheque_Sous_Texture'.$_POST['n'].' FROM users WHERE ID LIKE \''.$id_user.'\'';
    $resultat_texture=DM_Core::$d->requete_select($requete_texture);

    $rep = 'edges/textures/'.$_POST['texture'].'/miniatures';
    $dir = opendir($rep);
    while ($f = readdir($dir)) {
        if( $f!=='.' && $f!=='..') {
            $nom_sous_texture=substr($f,0, strrpos($f, '.'));
            ?>
            <option <?php
            if ($nom_sous_texture==$resultat_texture[0]['Bibliotheque_Sous_Texture'.$_POST['n']])
                echo 'selected="selected" ';?>
            style="background:url('edges/textures/<?=$_POST['texture']?>/miniatures/<?=$f?>') no-repeat scroll center right transparent">
                <?=$nom_sous_texture?>
            </option><?php
        }
    }
}
elseif (isset($_POST['num_gen'])) {
    include_once('Util.class.php');
    $grossissement=1;
    $grossissement_images=1.5;
    $largeur=$_POST['largeur']*$grossissement;
    $hauteur=$_POST['hauteur']*$grossissement;
    $hauteur_etagere=15;
    $hauteur_restante_etagere=15;
    $dimensions_etagere=array('largeur'=>$largeur,'hauteur'=>$hauteur_etagere);
    $html=Util::lire_depuis_fichier('edges/_tmp/'.$_POST['num_gen'].'.html');
    @unlink('edges/_tmp/'.$_POST['num_gen'].'.html');
    $regex_etagere='#<div class="etagere" style="width:[^;]+;background-image: url\(\'([^\']+)\'\)">&nbsp;</div>#is';
    $regex_numero='#name="([^"]+)"#is';
    preg_match($regex_etagere, $html, $code_etagere);
    list($code_etagere,$chemin_image_etagere)=$code_etagere;
    
    $image_etagere=imagecreatefromjpeg($chemin_image_etagere);
    $dimensions_image_etagere=array('largeur'=>imagesx($image_etagere),'hauteur'=>imagesy($image_etagere));
    imagecopyresampled($image_etagere, $image_etagere, 0, 0, 0, 0, $dimensions_etagere['largeur'], $dimensions_etagere['hauteur'], $dimensions_image_etagere['largeur'], $dimensions_etagere['hauteur']);
    $image_etagere2 = imagecreatetruecolor($dimensions_etagere['largeur'], $dimensions_etagere['hauteur']);
    imagecopy($image_etagere2, $image_etagere, 0, 0, 0, 0, $dimensions_etagere['largeur'], $dimensions_etagere['hauteur']);
    $image_etagere=$image_etagere2;
    /*foreach(array_keys($dimensions_etagere) as $dimension) {
        if ($dimensions_etagere[$dimension] > $dimensions_image_etagere[$dimension])
            // Dupliquer l'image
    }*/
    
    $im=imagecreatetruecolor($largeur, $hauteur);
    imagecopyresampled($im, $image_etagere, 0, 0, 0, 0, $dimensions_etagere['largeur'], $dimensions_etagere['hauteur'], $dimensions_etagere['largeur'], $dimensions_etagere['hauteur']);
    
    $contenus_etageres=explode($code_etagere, $html);
    $decalage_y=0;
    foreach($contenus_etageres as $contenu_etagere) {
        if (empty($contenu_etagere))
            continue;
        $hauteur_max=0;
        $images_numeros=array();
        preg_match_all($regex_numero, $contenu_etagere, $numeros);
        if (count($numeros[1])==0)
            continue;
        foreach($numeros[1] as $numero) {
            $grossissement2=1;
            list($pays,$magazine_numero)=explode('/',str_replace('<br>','',$numero));
            list($magazine,$numero)=explode('.',$magazine_numero);
            $e=new Edge($pays,$magazine,$numero);
            if (!getEstVisible($pays, $magazine, $numero) && !$e->magazine_est_inexistant) {
                $grossissement2=$grossissement_images;
            }
            $image_numero=@imagecreatefrompng('edges/'.$pays.'/gen/'.$magazine_numero.'.png');
            $image_numero2=imagecreatetruecolor(imagesx($image_numero)*$grossissement*$grossissement2, imagesy($image_numero)*$grossissement*$grossissement2);
            imagecopyresized($image_numero2, $image_numero, 0, 0, 0, 0, $e->o->largeur*$grossissement*$grossissement2, $e->o->hauteur*$grossissement*$grossissement2, $e->o->largeur, $e->o->hauteur);
            $images_numeros[]=$image_numero2;
        }
        $hauteurs=array_map('get_hauteur', $images_numeros);
        $hauteur_max=max($hauteurs);
        $decalage_x=0;
        foreach($images_numeros as $image_numero) {
            $decalage_numero_y=$decalage_y+$hauteur_max+$dimensions_etagere['hauteur']+$hauteur_restante_etagere-imagesy($image_numero);
            imagecopyresampled($im, $image_numero, $decalage_x, $decalage_numero_y, 0, 0, imagesx($image_numero), imagesy($image_numero), imagesx($image_numero), imagesy($image_numero));
            $decalage_x+=imagesx($image_numero);
        }
        
        $decalage_y+=$hauteur_max+$dimensions_etagere['hauteur']+$hauteur_restante_etagere;
        imagecopyresampled($im, $image_etagere, 0, $decalage_y, 0, 0, $dimensions_etagere['largeur'], $dimensions_etagere['hauteur'], $dimensions_etagere['largeur'], $dimensions_etagere['hauteur']);
    }
    //imagecopyresampled($im, $image_etagere, 0, $hauteur-$dimensions_etagere['hauteur'], 0, 0, $dimensions_etagere['largeur'], $dimensions_etagere['hauteur'], $dimensions_etagere['largeur'], $dimensions_etagere['hauteur']);
    
    imagepng($im,'edges/_tmp/'.$_POST['num_gen'].'.png');
}
elseif (isset($_POST['generer_image'])) {
    error_reporting(E_ALL);
    $nom_fichier='edges/_tmp/'.$_SESSION['user'].'-'.md5($_SESSION['user']).'.jpg';
    $images=array('texture1','sous_texture1','texture2','sous_texture2');
    $variables=array('largeur','texture1','sous_texture1','texture2','sous_texture2');
    foreach($variables as $variable)
        ${$variable}=$_POST[$variable];
    
    $largeur=intval($largeur)-20;

    $image_texture1=imagecreatefromjpeg('edges/textures/'.$texture1.'/'.$sous_texture1.'.jpg');
    $image_texture2=imagecreatefromjpeg('edges/textures/'.$texture2.'/'.$sous_texture2.'.jpg');
    $pos=json_decode(str_replace('\"','"',$_POST['pos']));
    foreach($pos as $type_element=>$pos_elements) {
        foreach($pos_elements as $i=>$pos_element) {
            $pos->$type_element->$i=explode('-',$pos->$type_element->$i);
        }
    }
    $max_y=0;
    $pos_sup_gauche=array();
    foreach($pos->etageres->etageres as $i=>$pos_etagere) {
        $pos_etagere_courante=explode(',',$pos_etagere);
        if ($i==0)
            $pos_sup_gauche=$pos_etagere_courante;
        if ($pos_etagere_courante[1] > $max_y)
            $max_y=$pos_etagere_courante[1];
    }
    $min_y=$pos_sup_gauche[1];
    $hauteur=$max_y-$min_y+16;
    $im=imagecreatetruecolor($largeur, $hauteur);
    for ($i=0;$i<$largeur;$i+=imagesx($image_texture1))
        for ($j=0;$j<$hauteur;$j+=imagesy($image_texture1))
            imagecopy ($im, $image_texture1, $i, $j, 0, 0, imagesx($image_texture1), imagesy($image_texture1));
    
    imagedestroy($image_texture1);
    foreach($pos->etageres->etageres as $i=>$pos_etagere) {
        $pos_etagere_courante=explode(',',$pos_etagere);
        imagecopyresampled($im, $image_texture2, 0, $pos_etagere_courante[1]-$pos_sup_gauche[1], 0, 0, $largeur, 16, imagesx($image_texture2), 16);
    }
    imagedestroy($image_texture2);
    
    foreach($pos->tranches as $src_tranche=>$pos) {
        $image_tranche=imagecreatefrompng(preg_replace('#\?.*#is', '', $src_tranche));
        foreach($pos as $pos_tranche) {
            $pos_courante=explode(',',$pos_tranche);
            imagecopyresampled($im, $image_tranche, $pos_courante[0]-$pos_sup_gauche[0], $pos_courante[1]-$pos_sup_gauche[1], 0, 0, $pos_courante[2], $pos_courante[3], imagesx($image_tranche), imagesy($image_tranche));   
        }
        imagedestroy($image_tranche);
    }
    imagejpeg($im,$nom_fichier);
    echo '<a style="float:left;border-bottom:1px dashed white" target="_blank" href="'.$nom_fichier.'">'.BIBLIOTHEQUE_SAUVEGARDER_IMAGE.'</a>';
    
}
elseif (isset($_GET['regen'])) {
    ?>
            <html><head><style type="text/css">img {margin-left:-4px;}</style></head><body>
    <?php
    $pays=$_GET['pays'];
    $magazine=$_GET['magazine'];
    $grossissement=isset($_GET['grossissement'])?$_GET['grossissement']:Edge::$grossissement;
    if (isset($_GET['debut'])) {
        $numeros=array('debut'=>$_GET['debut'], 'fin'=>$_GET['fin']);
    }
        $nom_classe=$pays.'_'.$magazine;
    if (file_exists('edges/'.$pays.'/'.$magazine.'.edge.class.php')) {
        include_once('edges/'.$pays.'/'.$magazine.'.edge.class.php');
        $o=new $nom_classe(0);
    }
    else {
        $o=new Edge($pays,$magazine,0);
    }
    $iv=new IntervalleValidite($o->intervalles_validite);
    if (isset($_GET['debut'])) {
        $liste_numeros=array();
        for ($i=$_GET['debut'];$i<=$_GET['fin'];$i++)
            //if ($iv->estValide($i))
                $liste_numeros[]=$i;
    }
    else
        $liste_numeros=$iv->getListeNumeros();
    foreach($liste_numeros as $numero) {?>
        <img src="Edge.class.php?grossissement=<?=$grossissement?>&regen=true&pays=<?=$pays?>&magazine=<?=$magazine?>&numero=<?=$numero?>" />
    <?php }
    ?></body></html><?php
}
elseif (isset($_GET['dispo_tranches'])) {
    $data=Edge::getPourcentageVisible(false, false, false);
    asort($data);
    $usernames=array_keys($data);
    sort($data);
    $somme=0;
    foreach($data as $pct)
        $somme+=$pct;
    $moyenne=$somme/count($data);
    $data_moyenne=array();
    for($i=0;$i<count($data);$i++)
        $data_moyenne[]=$moyenne;

    include 'OpenFlashChart/php-ofc-library/open-flash-chart.php';

    $chart = new open_flash_chart();
    $chart->set_title( new title( 'Disponibilite des tranches' ) );

    //
    // Make our area chart:
    //
    $area = new area();
    // set the circle line width:
    $area->set_width( 2 );
    $area->set_default_dot_style( new hollow_dot() );
    $area->set_colour( '#838A96' );
    $area->set_fill_colour( '#E01B49' );
    $area->set_fill_alpha( 0.4 );
    $area->set_values( $data );
    $t=new tooltip( "Utilisateur #x_label<br>#val#" );

    // add the area object to the chart:
    $chart->add_element( $area );
    
    $line_dot = new line();
    $line_dot->set_values($data_moyenne);
    $line_dot->set_tooltip( $t );
    $chart->add_element( $line_dot );

    $y_axis = new y_axis();
    $y_axis->set_range( 0, 100, 10 );
    $y_axis->labels = null;
    $y_axis->set_offset( false );

    $chart->add_y_axis( $y_axis );

    $x_labels = new x_axis_labels();
    $x_labels->set_vertical();
    $x_labels->set_colour( '#A2ACBA' );
    $x_labels->set_labels($usernames);
    
    $x = new x_axis();
    $x->set_colour( '#A2ACBA' );
    $x->set_grid_colour( '#D7E4A3' );
    $x->set_offset( false );
    $x->set_labels( $x_labels );
    
    $chart->set_x_axis( $x );

    ?>
        <html>
            <head>
            <link rel="stylesheet" type="text/css" href="style.css">
            <!--[if IE]>
                    <style type="text/css" media="all">@import "fix-ie.css";</style>
            <![endif]-->
            <script type="text/javascript" src="js/json/json2.js"></script>
            <script type="text/javascript" src="js/swfobject.js"></script>
            <script type="text/javascript">
            swfobject.embedSWF("open-flash-chart.swf", "my_chart", "<?=(25*count($usernames))?>", "380", "9.0.0");
            </script>

            <script type="text/javascript">

            function open_flash_chart_data()
            {
                return JSON.stringify(data);
            }

            function findSWF(movieName) {
              if (navigator.appName.indexOf("Microsoft")!= -1) {
                return window[movieName];
              } else {
                return document[movieName];
              }
            }

            var data = <?php echo $chart->toPrettyString(); ?>;

            </script>
            </head>
            <body>
                <div id="my_chart"></div>
            </body>
        </html>
        <?php
}

function getEstVisible($pays,$magazine,$numero, $get_html=false, $regen=false) {
    $e=new Edge($pays, $magazine, $numero);
    if ($get_html)
        return array($e->getImgHTML($regen),$e->est_visible);
    else
        return $e->est_visible;
}

function imagecreatefrompng_getimagesize($chemin) {
    $image=imagecreatefrompng($chemin);
    return array($image,imagesx($image),imagesy($image));
}

function imagecreatefromgif_getimagesize($chemin) {
    $image=imagecreatefromgif($chemin);
    return array($image,imagesx($image),imagesy($image));
}

function imagepalettetotruecolor(&$img) {
    if (!imageistruecolor($img))
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $img1 = imagecreatetruecolor($w,$h);
        imagecopy($img1,$img,0,0,0,0,$w,$h);
        $img = $img1;
    }
}

function rgb2hex($r,$g,$b) {
    $hex = "";
    $rgb=array($r,$g,$b);
    for ($i = 0; $i < 3; $i++) {
        if (($rgb[$i] > 255) || ($rgb[$i] < 0)) {
            echo "Error : input must be between 0 and 255";
            return 0;
        }
        $tmp = dechex($rgb[$i]);
        if (strlen($tmp) < 2)
            $hex .= "0" . $tmp;
        else
            $hex .= $tmp;
    }
    return $hex;
}


function remplacerCouleur(&$im,$r_old,$g_old,$b_old,$r,$g,$b) {
    if ($r_old===$r && $g_old===$g && $b_old===$b)
        return;
    $width = imagesx($im);
    $height = imagesy($im);
    $cloneH = 0;
    $oldhex = rgb2hex($r_old,$g_old,$b_old);
    $hex = rgb2hex($r,$g,$b);
    $color = imagecolorallocate($im, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 6)));
    for ($cloneH = 0; $cloneH < $height; $cloneH++) {
        for ($x = 0; $x < $width; $x++) {
            if (colormatch($im, $x, $cloneH, $oldhex))
               imagesetpixel($im, $x, $cloneH, $color);
       }
   }
}

function colormatch($image, $x, $y, $hex) {
    $rgb = imagecolorat($image, $x, $y);
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;

    $r2 = hexdec(substr($hex, 0, 2));
    $g2 = hexdec(substr($hex, 2, 2));
    $b2 = hexdec(substr($hex, 4, 6));
    return $r == $r2 && $b == $b2 && $g == $g2;
}

function get_hauteur($image) {
    return imagesy($image);
}
?>
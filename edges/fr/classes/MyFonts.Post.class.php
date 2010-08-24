<?php
@include_once('Post.class.php');
class MyFonts extends Post {
    var $p;
    var $chemin_image;
    static $regex_source_image='#src="([^"]+)"#is';

    function MyFonts($font,$color,$color_bg, $width, $text,$precision=18) {
        $data = array(
            'seed'=>'43',
            'dock'=>'false',
            'size'=>$precision,
            'w'=>$width,
            'src'=>'custom',
            'text'=>urlencode(utf8_encode($text)),
            'fg'=>$color,
            'bg'=>$color_bg,
            'goodies'=>'ot.liga',
            urlencode('i[0]')=>urlencode($font.',,720,144')
        );

        // send a request to example.com (referer = jonasjohn.de)
        $this->p=new Post(
            "http://new.myfonts.com/ajax-server/testdrive.xml",
            "http://www.jonasjohn.de/",
            $data,
            'GET'
        );

        $code_image=$this->p->content;
        preg_match(self::$regex_source_image, $code_image, $chemin);
        $this->chemin_image=$chemin[1];
    }
}

?>

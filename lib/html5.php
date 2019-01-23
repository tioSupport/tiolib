<?php

namespace tiolib;

/*************************************************************************
* Copyright (c)2007-2014   - ALL RIGHTS RESERVED
*
 
*************************************************************************/

class html5 {

    private $doctype = '<!DOCTYPE html>';
    private $html_open = '<html>';
    private $html_close = '</html>';
    private $charset;
    private $autorefresh;
    private $author;
    private $copyright;
    private $keywords;
    private $description;
    private $title;
    private $pretitle;
    private $posttitle;
    private $favicon;
    private $footerhtml;
    private $headerhtml;
    private $metatags = array();
    private $csstags = array();
    private $jstags = array();
    private $rsstags = array();
    private $onloadJs = array();
    private $freeheaders = array();
    private $code_compression = false;

    public function __construct($site_title = 'Untitled HTML5 document', $site_charset = 'utf-8'){
    $this->title = $site_title;
    $this->charset = $site_charset;
  }

    private function compress_code($h){
        $h = str_replace("\n\n",'',$h);
        $h = str_replace("\r\n",'',$h);
        $h = str_replace("\n",'',$h);
        $h = str_replace("   ",' ',$h);
        $h = str_replace("  ",' ',$h);
        $h = str_replace("> <",'><',$h);
        return $h;
    }

    public function html_header() {

        $h = $this->doctype;
        $h .= "\n".$this->html_open;
        $h .= "\n".'<head>';
        $h .= "\n".'<title>'.$this->pretitle.$this->title.$this->posttitle.'</title>';
        $h .= "\n".'<meta http-equiv="Content-Type" content="text/html; charset='.$this->charset.'" />';

        if ($this->keywords)
            $this->add_metatag('keywords',$this->keywords);

        if ($this->description)
            $this->add_metatag('description',$this->description);

        if ($this->author)
            $this->add_metatag('author',$this->author);

        if ($this->copyright)
            $this->add_metatag('copyright',$this->copyright);

        if ($this->favicon)
            $h .= "\n".'<link rel="shortcut icon" href="'.$this->favicon.'" />';

        if ($this->autorefresh)
            $h .= "\n".'<meta http-equiv="refresh" content="'.$this->autorefresh.'" />';

        foreach ($this->metatags as $t)
            $h .= "\n".'<meta name="'.$t[0].'" content="'.$t[1].'" />';

        foreach ($this->csstags as $t)
            $h .= "\n".'<link rel="stylesheet" type="text/css" href="'.$t.'" />';

        foreach ($this->jstags as $t)
            $h .= "\n".'<script type="text/javascript" src="'.$t.'"></script>';

        foreach ($this->rsstags as $t)
            $h .= "\n".'<link rel="alternate" type="application/rss+xml" title="'.$t[0].'" href="'.$t[1].'" />';

        foreach ($this->freeheaders as $t)
            $h .= "\n".$t;

        if(!empty($this->onloadJs)){
            $h .= "\n"."<script type=\"text/javascript\">\n$(function() {";
            foreach ($this->onloadJs as $t){
                $h .=   $t;
            }
            $h .= "});\n</script>";
        }

        $h .= "\n".'</head>';
        $h .= "\n".'<body>'."\n";
        $h .= "\n".$this->headerhtml;

        return $this->code_compression ? $this->compress_code($h) : $h;

    }

    public function html_footer() {

        $h = "\n".$this->footerhtml;

        /*if (!$GLOBALS['config']['production'] && $GLOBALS['sql']){
            $h .= $GLOBALS['sql']->debug();
        }*/

        $h .= "\n".'</body>';
        $h .= "\n".$this->html_close;

        return $this->code_compression ? $this->compress_code($h) : $h;

    }

    public function set_charset($value) {
        $this->charset = $value;
    }

    public function set_refresh($value) {
        $this->autorefresh = $value;
    }

    public function set_title($value) {
        $this->title = $value;
    }

    public function set_pretitle($value) {
        $this->pretitle = $value;
    }

    public function set_posttitle($value) {
        $this->posttitle = $value;
    }

    public function set_favicon($value) {
        $this->favicon = $value;
    }

    public function set_keywords($value) {
        if (!is_array($value)){
            $this->keywords .= $value;
        } else {
            foreach ($value as $keyw){
                $this->keywords .= ','.$keyw;
            }
            $this->keywords = substr($this->keywords,1);
        }
    }

    public function add_keywords($value) {
        if (!is_array($value)){
            $this->keywords .= ','.$value;
        } else {
            foreach ($value as $k){
                $this->keywords .= ','.$k;
            }
        }
    }

    public function set_description($value) {
        $this->description = $value;
    }

    public function set_author($value) {
        $this->author = $value;
    }

    public function set_copyright($value) {
        $this->copyright = $value;
    }

    public function add_metatag($meta_name, $meta_content) {
        $tag_exists = false;
        foreach ($this->metatags as $t){
            if (strtolower($t[0]) == strtolower($meta_name)){
                $tag_exists = true;
            }
        }
        if (!$tag_exists){
            array_push($this->metatags, array($meta_name, $meta_content));
        }
    }

    public function add_css($css_url) {
        array_push($this->csstags, $css_url);
    }

    public function add_js($js_url) {
        array_push($this->jstags, $js_url);
    }

    public function add_onloadJs($js_script) {
        array_push($this->onloadJs, $js_script);
    }


    public function add_rss($rss_title, $rss_url) {
        array_push($this->rsstags, array($rss_title, $rss_url));
    }

    public function set_code_compression($value) {
        $this->code_compression = $value;
    }

    public function add_freeheader($value) {
        array_push($this->freeheaders, $value);
    }

    public function add_headerhtml($value) {
        $this->headerhtml .= $value;
    }

    public function add_footerhtml($value) {
        $this->footerhtml .= $value;
    }

    public function output($c) {
        echo
            $this->html_header().
            ($this->code_compression ? $this->compress_code($c) : $c).
            $this->html_footer();
    }

}

?>
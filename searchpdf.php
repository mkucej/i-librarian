<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if(!empty($_GET['file'])) {
    $file_name = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
    $file = dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$file_name;
    if (!file_exists($file)) die('{"Error":"PDF does not exist!"}');
} else {
    die('{"Error":"No PDF provided!"}');
}

if(!empty($_GET['search_term'])) {
    $search_term = addcslashes($_GET['search_term'],"\044\050..\053\056\057\074\076\077\133\134\136\173\174");
    $search_term = str_replace('\<\?\>', '.', $search_term);
    $search_term = str_replace('\<\*\>', '.*', $search_term);
} else {
    die('{"Error":"No search term provided!"}');
}

$temp_file = $temp_dir.DIRECTORY_SEPARATOR.$file_name.'.txt';

if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file)) system(select_pdftotext().'-layout -enc UTF-8 "'.$file.'" "'.$temp_file.'"', $ret);

$string = file_get_contents($temp_file);

if (empty($string)) die('{"Error":"PDF to text conversion failed!"}');

$pages = array ();
$pages = explode("\f", $string);

$output_pages = array ();

while (list($page_num,$page_str) = each ($pages)) {
   if(preg_match("/$search_term/ui", $page_str) > 0) $output_pages[]=$page_num;
}

$final_pages = array ();
foreach ($output_pages as $output_page) {
    $output_page = $output_page + 1;
    $temp_xml = $temp_dir.DIRECTORY_SEPARATOR.$file_name;
    if (!file_exists($temp_xml.$output_page.'.xml') || filemtime($temp_xml.$output_page.'.xml') < filemtime($file)) {
        system(select_pdftohtml().' -q -noframes -enc UTF-8 -nomerge -c -i -xml -f '. $output_page .' -l '. $output_page .' "'.$file.'" "'.$temp_xml.$output_page.'"');
    }
    if (file_exists($temp_xml.$output_page.'.xml')) {
        $string = file_get_contents($temp_xml.$output_page.'.xml');
//        $string = preg_replace ('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);
        $string = preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
        $string = preg_replace('/\s{2,}/ui', ' ', $string);
//        $string = strtolower($string);
        $string = str_ireplace('<!doctype pdf2xml system "pdf2xml.dtd">', '<!DOCTYPE pdf2xml SYSTEM "pdf2xml.dtd">', $string);
        $xml = @simplexml_load_string($string);
        if(!$xml) die('{"Error":"Invalid XML encoding!"}');
        foreach($xml->page->attributes() as $a => $b) {
            if ($a == 'height') $page_height = $b;
            if ($a == 'width') $page_width = $b;
        }
        $i = 0;
        foreach($xml->page->text as $row) {
            $row = strip_tags($row->asXML());
            if(preg_match("/$search_term/ui", $row) > 0) {
                foreach($xml->page->text[$i]->attributes() as $a => $b) {
                    if ($a == 'top') $row_top = 100*round($b/$page_height, 3);
                    if ($a == 'left') $row_left = 100*round($b/$page_width, 3)-0.5;
                    if ($a == 'height') $row_height = 100*round($b/$page_height, 3);
                    if ($a == 'width') $row_width = 100*round($b/$page_width, 3);
                }
                $final_pages[($output_page)][] = array('top' => $row_top, 'left' => $row_left, 'height' => $row_height, 'width' => $row_width, 'text' => $row);
            }
            $i = $i + 1;
        }
    } else {
        die('{"Error":"PDF to XML conversion failed!"}');
    }
}
print json_encode ($final_pages, JSON_FORCE_OBJECT);
?>
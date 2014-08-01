<?php
// THIS FILE CREATES A TEXT LAYER OVER A PAGE IN THE PDF VIEWER
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!empty($_GET['file'])) {
    $file_name = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
    $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $file_name;
    if (!file_exists($file))
        die('Error! PDF does not exist!');
} else {
    die('Error! No PDF provided!');
}

$page = '';
if (!empty($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    die('Error! No page number provided!');
}

$temp_xml = $temp_dir . DIRECTORY_SEPARATOR . $file_name;
if (!file_exists($temp_xml . $page . '.xml') || filemtime($temp_xml . $page . '.xml') < filemtime($file)) {
    system(select_pdftohtml() . ' -q -noframes -enc UTF-8 -nomerge -c -i -xml -f ' . $page . ' -l ' . $page . ' "' . $file . '" "' . $temp_xml . $page . '"');
}
if (file_exists($temp_xml . $page . '.xml')) {
    $string = file_get_contents($temp_xml . $page . '.xml');
    $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    $string = preg_replace('/\s{2,}/ui', ' ', $string);
    $string = str_ireplace('<!doctype pdf2xml system "pdf2xml.dtd">', '<!DOCTYPE pdf2xml SYSTEM "pdf2xml.dtd">', $string);

    $xml = @simplexml_load_string($string);
    if (!$xml)
        die('{"Error":"Invalid XML encoding!"}');
    foreach ($xml->page->attributes() as $a => $b) {
        if ($a == 'height')
            $page_height = $b;
        if ($a == 'width')
            $page_width = $b;
    }
    $i = 0;
    $output = '';
    foreach ($xml->page->text as $row) {
        $row = strip_tags($row->asXML());
        foreach ($xml->page->text[$i]->attributes() as $a => $b) {
            if ($a == 'top')
                $row_top = 100 * round($b / $page_height, 3);
            if ($a == 'left')
                $row_left = 100 * round($b / $page_width, 3);
            if ($a == 'height')
                $row_height = 100 * round($b / $page_height, 3);
            if ($a == 'width')
                $row_width = 100 * round($b / $page_width, 3);
        }
        $output .= '<div class="pdf-text-div" data-text="'.str_replace('"', '&quot;', $row).'" style="top:'.$row_top.'%;left:'.$row_left.'%;width:'.$row_width.'%;height:'.$row_height.'%"></div>';
        $i = $i + 1;
    }
} else {
    die('Error! PDF to XML conversion failed!');
}

print $output;
?>
<?php

include_once 'data.php';
include_once 'functions.php';
include_once 'pdfclass.php';
session_write_close();

// Sanitize PDF filename.
$file = '';
if (!empty($_REQUEST['file'])) {
    $file = preg_replace('/[^a-zA-z0-9\_\.pdf]/', '', $_REQUEST['file']);
} else {
    sendError("No PDF provided.");
}

$pdfHandler = new PDFViewer($file);

if (!empty($_GET['extractimage']) && !empty($_GET['image']) && !empty($_GET['x']) && !empty($_GET['y']) && !empty($_GET['width']) && !empty($_GET['height'])) {

    // Extract image from a pdf page image.
    $pdfHandler->extractImage($_GET['image'], $_GET['x'], $_GET['y'], $_GET['width'], $_GET['height']);
    
} elseif (!empty($_GET['renderpdf']) && !empty($_GET['page'])) {

    // Create page image.
    $pdfHandler->createPageImage($_GET['page']);
    
} elseif (isset($_GET['renderthumbs']) && !empty($_GET['from'])) {

    // Create thumbs.
    $pdfHandler->createPageThumbs($_GET['from']);
    
} elseif (isset($_GET['renderbookmarks'])) {

    // Extract bookmarks.
    echo $pdfHandler->extractBookmarks();
    
} elseif (isset($_GET['rendertext'])) {

    // Extract text into database.
    $pdfHandler->extractXMLText();
    
} elseif (isset($_GET['gettextlayer']) && !empty($_GET['from'])) {

    // Get text from the database.
    echo $pdfHandler->getTextLayer($_GET['from']);
    
} elseif (isset($_GET['searchtextlayer'])) {

    if (empty($_GET['search_term'])) {
        sendError('No search term provided');
    }

    // Search text in the database.
    echo $pdfHandler->searchTextLayer($_GET['search_term']);
    
} elseif (isset($_GET['deleteannotation']) && !empty($_GET['type'])) {

    $dbids = array();

    if (!empty($_GET['dbids'])) {
        $dbids = $_GET['dbids'];
    }

    // Delete annotation.
    echo $pdfHandler->deletePDFAnnotation($_GET['type'], $dbids);
    
} elseif (isset($_GET['editannotation']) && !empty($_GET['dbid'])) {

    // Edit PDF note text.
    echo $pdfHandler->editPDFNote($_GET['dbid'], $_GET['text']);
    
} elseif (isset($_GET['savepdfnote']) && !empty($_GET['page']) && !empty($_GET['top']) && !empty($_GET['left'])) {

    // Save new PDF note.
    echo $pdfHandler->savePDFNote($_GET['page'], $_GET['top'], $_GET['left']);
    
} elseif (isset($_POST['savepdfmarkers']) && !empty($_POST['page']) && !empty($_POST['markers'])) {

    // Save PDF markers.
    echo $pdfHandler->savePDFMarkers($_POST['page'], $_POST['markers']);
    
} elseif (isset($_GET['getpdfmarkers'])) {

    $users = null;

    if (isset($_GET['users']) && $_GET['users'] === 'other') {
        $users = 'other';
    }

    // Get PDF markers.
    echo $pdfHandler->getPDFMarkers($users);
    
} elseif (isset($_GET['getpdfnotes'])) {

    $users = null;
    if (isset($_GET['users']) && $_GET['users'] === 'other') {
        $users = 'other';
    }

    // Get PDF markers.
    echo $pdfHandler->getPDFNotes($users);
    
} elseif (isset($_GET['downloadpdf'])) {

    $mode = null;
    $attachments = array();

    if (isset($_GET['mode']) && $_GET['mode'] === 'download') {
        $mode = 'download';
    }

    if (isset($_GET['attachments'])) {
        $attachments = $_GET['attachments'];
    }

    // Download PDF.
    $pdfHandler->downloadPDF($mode, $attachments);

} elseif (isset($_GET['getlinks'])) {

    // Get links.
    echo $pdfHandler->getLinks();
}
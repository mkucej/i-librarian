<?php

include_once 'data.php';
include_once 'functions.php';

if (is_file("library/" . $_GET['file']) && isset($_SESSION['auth'])) {

    if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
        print '<iframe id="pdf-div" src="'
                . htmlspecialchars('downloadpdf.php?file=' . $_GET['file'] . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0')
                . 'frameborder="0" class="noprint" style="width:100%;height:100%"></iframe>';

    if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
        include 'viewpdf.php';

} elseif (isset($_SESSION['auth'])) {

    print '<div style="width:100%;height:50%;text-align:center;padding-top:270px;color:#b6b8bc;font-size:36px">No PDF</div>';
}
?>

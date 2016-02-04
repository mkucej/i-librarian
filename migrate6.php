<?php

ignore_user_abort();

include_once 'data.php';
include_once 'functions.php';

// Install every non existing table and folder, to be sure.
include 'install.php';

// Delete PDF cache files.
$pdf_caches = glob(IL_PDF_CACHE_PATH . DIRECTORY_SEPARATOR . '*.sq3', GLOB_NOSORT);

if (is_array($pdf_caches)) {
    foreach ($pdf_caches as $pdf_cache) {
        @unlink($pdf_cache);
    }
}
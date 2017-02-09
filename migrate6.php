<?php

ignore_user_abort(true);

echo <<<EOT
    <script type="text/javascript">
        var div = parent.document.getElementById('first-loader').childNodes[1];
        div.innerHTML = div.innerHTML + '<p style="font-size: 26px;">Please wait, upgrading&hellip;</p>';
    </script>
EOT;

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
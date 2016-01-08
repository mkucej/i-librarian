<?php
//list of styles
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$dbHandle = database_connect(__DIR__, 'styles');

// select style titles

$result = $dbHandle->query("SELECT title FROM styles WHERE title != ''");

$i = 1;
$output = '';

while ($style = $result->fetch(PDO::FETCH_NUM)) {
    
    $output .= '<tr><td class="details">'
            . $i . '. </td><td class="details">'
            . htmlspecialchars(ucwords($style[0])) . '</td></tr>';
    $i++;
    
}

$dbHandle = null;
?>
<table style="width:100%">
        <tr>
            <td class="details alternating_row" colspan="2"><b>List of citation styles</b></td>
        </tr>
        <?php
        echo $output;
        ?>
</table>
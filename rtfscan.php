<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (isset($_FILES['rtf']) && is_uploaded_file($_FILES['rtf']['tmp_name'])) {

    $response = array();
    $errors = array();
    
    $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . basename(str_replace('\\', '/', urldecode($_FILES['rtf']['name'])));
    $move = move_uploaded_file($_FILES['rtf']['tmp_name'], $temp_file);
    if (!$move)
        $errors[] = 'Error! File upload failed.';

    $rtf_string = file_get_contents($temp_file);
    if (empty($rtf_string))
        $errors[] = 'Error! File is empty.';
    preg_match_all('/(\-\S+\-ID)(\d+)/', $rtf_string, $ids);

    $cites_ordered = $ids[2];
    if (count($cites_ordered) === 0)
        $errors[] = 'Error! No citations found.';

    $unique_ids = array_unique($cites_ordered);
    $id_query = join(',', $unique_ids);
    $orderby = ' ORDER BY CASE id ';
    while (list($key, $cite) = each($unique_ids)) {
        $orderby .= ' WHEN ' . $cite . ' THEN ' . $key;
    }
    $orderby .= ' END';

    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT * FROM library WHERE id IN (" . $id_query . ")" . $orderby);
    $dbHandle = null;

    $citations = array();
    while ($items = $result->fetch(PDO::FETCH_ASSOC)) {

        $authors = '';
        $editors = '';
        $id = '';
        $title = '';
        $secondary_title = '';
        $tertiary_title = '';
        $pages = '';
        $volume = '';
        $issue = '';
        $doi = '';
        $journal = '';
        $date_parts = array();
        $publisher = '';
        $place_published = '';

        extract($items);

        $i = 0;
        $new_authors = array();
        $array = array();
        $array = explode(';', $authors);
        $array = array_filter($array);
        if (!empty($array)) {
            foreach ($array as $author) {
                $array2 = explode(',', $author);
                $last = trim($array2[0]);
                $last = substr($array2[0], 3, -1);
                $first = trim($array2[1]);
                $first = substr($array2[1], 3, -1);
                $new_authors[$i]['family'] = $last;
                $new_authors[$i]['given'] = $first;
                $i++;
            }
        }

        $i = 0;
        $new_editors = array();
        $array = array();
        $array = explode(';', $editors);
        $array = array_filter($array);
        if (!empty($array)) {
            foreach ($array as $editor) {
                $array2 = explode(',', $editor);
                $last = trim($array2[0]);
                $last = substr($array2[0], 3, -1);
                $first = trim($array2[1]);
                $first = substr($array2[1], 3, -1);
                $new_editors[$i]['family'] = $last;
                $new_editors[$i]['given'] = $first;
                $i++;
            }
        }

        $date_parts['date-parts'][] = explode("-", $year);

        $type = convert_type($reference_type, 'ilib', 'csl');

        // CSL book type, shift secondary title to tertiary title
        if ($reference_type == 'book') {
            $tertiary_title = $secondary_title;
            $secondary_title = '';
        }

        $json['ID' . $id] = array(
            "id" => 'ID' . $id,
            "type" => $type,
            "title" => $title,
            "container-title" => $secondary_title,
            "collection-title" => $tertiary_title,
            "page" => $pages,
            "volume" => $volume,
            "issue" => $issue,
            "DOI" => $doi,
            "journalAbbreviation" => $journal,
            "author" => $new_authors,
            "editor" => $new_editors,
            "issued" => $date_parts,
            "publisher" => $publisher,
            "publisher-place" => $place_published
        );
    }
    $response['references'] = $json;

    foreach ($cites_ordered as $key => $id) {
        if (isset($json['ID' . $id])) {
            $citations[] = array(
                "citationItems" => array(array('id' => 'ID' . $id)),
                "properties" => array('noteIndex' => $key + 1)
            );
        } else {
            $errors[] = 'Error! Reference ' . $id . ' not found.';
        }
    }
    $response['citations'] = $citations;

    // fetch citation style
    if (!empty($_POST['citation-style']) || !empty($_POST['last-style'])) {

        if (!empty($_POST['last-style']))
            $_POST['citation-style'] = $_POST['last-style'];

        try {
            $dbHandle = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'styles.sq3');
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
        $title_q = $dbHandle->quote(strtolower($_POST['citation-style']));
        $result = $dbHandle->query('SELECT style FROM styles WHERE title=' . $title_q);
        $style = $result->fetchColumn();
        if (empty($style))
            $errors[] = 'Error! This citation style does not exist.';
        $style = gzuncompress($style);
        $style = str_replace(array("\r\n", "\r", "\n"), "", $style);
        $style = str_replace("'", "\'", $style);
        $response['style'] = $style;
    }

    if (count($errors) > 0)
        $response['errors'] = $errors;

    // encode items in JSON
    $content = json_encode($response, JSON_HEX_APOS);
    die($content);
}

// fomat RTF file
if (!empty($_POST['bibliography']) && count($_POST['cites']) > 0 && !empty($_POST['rtfname'])) {

    $response = array('OK');
    $errors = array();
    $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . basename(str_replace('\\', '/', urldecode($_POST['rtfname'])));
    $output_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'formatted-' . basename(str_replace('\\', '/', urldecode($_POST['rtfname'])));
    $rtf_string = file_get_contents($temp_file);
    if (empty($rtf_string))
        $errors[] = 'Error! Could not read RTF file.';

    //insert citations
    $i = 0;
    preg_match_all('/\\\{\S+\-\S+\-ID\d+\\\}/', $rtf_string, $ids);
    foreach ($ids[0] as $target) {
        $rtf_string = str_replace($target, $_POST['cites'][$i], $rtf_string);
        $i++;
    }

    //insert bibliography
    $position = strrpos($rtf_string, '}');
    $rtf_string = substr($rtf_string, 0, $position - 1) . PHP_EOL . $_POST['bibliography'] . PHP_EOL . '}';

    $put = file_put_contents($output_file, $rtf_string);
    if (!$put)
        $errors[] = 'Error! Could not write RTF file.';
    unlink($temp_file);

    if (count($errors) > 0)
        $response['errors'] = $errors;
    $content = json_encode($response, JSON_HEX_APOS);
    die($content);
}
?>
<div class="item-sticker ui-widget-content ui-corner-all" style="margin: auto;width:50%;margin-top:4em">
    <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="text-align:center;font-size:13px;border:0">RTF citation scan</div>
    <form id="rtfscanform" enctype="multipart/form-data" action="rtfscan.php" method="POST">
        <table cellspacing="0" class="alternating_row ui-corner-bottom" style="width:100%;border-spacing:6px;margin:auto">
            <tr>
                <td style="width:7em">
                    RTF file:
                </td>
                <td style="vertical-align: middle">
                    <input type="file" accept="application/rtf" name="rtf">
                </td>
            </tr>
            <tr>
                <td>
                    Citation style:
                </td>
                <td style="vertical-align: middle">
                    <input type="text" name="citation-style" id="citation-style" placeholder=" e.g. Cell" style="width:95%" autocomplete="false">
                </td>
            </tr>
            <tr style="display:none">
                <td>
                    Last style:
                </td>
                <td id="last-style-td" class="select_span" style="vertical-align: middle">
                    <input type="checkbox" name="last-style" style="display:none" value="">
                    <i class="fa fa-square-o"></i>
                    <span></span>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td style="vertical-align: middle">
                    <input type="submit" value="Format Citations">
                </td>
            </tr>
        </table>
    </form>
</div>

<div id="rtfscan-results" style="width:50%;margin:auto;padding:0;margin-top:3em">

</div>
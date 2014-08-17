<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$nozip = false;
if (extension_loaded('zip'))
    $nozip = true;

if (!empty($_GET['export_files']) && isset($_GET['export'])) {

    if (!isset($_GET['column']))
        $_GET['column'][] = 'Title';

    if ($_GET['export_files'] == 'session') {
        $export_files = read_export_files(0);
        $_GET['export_files'] = implode(" ", $export_files);
    }

    $column_translation = array(
        "Unique ID" => "id",
        "Authors" => "authors",
        "Title" => "title",
        "Journal" => "journal",
        "Year" => "year",
        "Volume" => "volume",
        "Issue" => "issue",
        "Pages" => "pages",
        "Abstract" => "abstract",
        "Secondary Title" => "secondary_title",
        "Tertiary Title" => "tertiary_title",
        "Affiliation" => "affiliation",
        "Editor" => "editor",
        "Publisher" => "publisher",
        "Place Published" => "place_published",
        "Keywords" => "keywords",
        "Accession ID" => "uid",
        "DOI" => "doi",
        "URL" => "url",
        "Publication Type" => "reference_type");

    $column_translation = array_flip($column_translation);

    $export_files = preg_replace('/[^0-9\s]/i', '', $_GET['export_files']);
    $export_files = explode(" ", $export_files);
    $export_files = join("','", $export_files);
    $export_files = "WHERE id IN ('$export_files')";

    if ($_GET['format'] == 'zip') {
        $zip = new ZipArchive;
        $zip->open($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip', ZIPARCHIVE::OVERWRITE);
    }

    $orderby = 'id DESC';
    if ($_GET['format'] == 'citations')
        $orderby = 'authors COLLATE NOCASE ASC';

    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT * FROM library $export_files ORDER BY $orderby");
    $dbHandle = null;

    $items = $result->fetchAll(PDO::FETCH_ASSOC);

    $paper = '';
    $i = 1;

    while (list($key, $item) = each($items)) {

        $add_item = array();

        if ($item['volume'] == 0)
            $item['volume'] = '';

        while (list($key, $value) = each($_GET['column'])) {

            $column_name = array_search($value, $column_translation);

            $add_item[$column_name] = $item[$column_name];
        }

        reset($_GET['column']);

        if ($_GET['encoding'] == 'ASCII') {

            while (list($key, $value) = each($add_item)) {

                if (!empty($value))
                    $add_item[$key] = utf8_deaccent($value);
            }

            reset($add_item);
        }

        if (isset($add_item['id'])) {

            $id_author = substr($item['authors'], 3);
            $id_author = substr($id_author, 0, strpos($id_author, '"'));
            if (empty($id_author))
                $id_author = 'unknown';

            $id_year_array = explode('-', $item['year']);
            $id_year = '0000';
            if (!empty($id_year_array[0]))
                $id_year = $id_year_array[0];

            $add_item['id'] = utf8_deaccent($id_author) . '-' . $id_year . '-ID' . $item['id'];

            if ($_GET['format'] == 'BibTex' && !empty($item['bibtex']))
                $add_item['id'] = $item['bibtex'];

            $add_item['id'] = str_replace(' ', '', $add_item['id']);
        }

        if ($_GET['format'] == 'citations') {
            
            if(empty($_GET['citation-style'])) die('Citation style required.');
            
            $authors = '';
            $id = '';
            $title = '';
            $secondary_title = '';
            $pages = '';
            $volume = '';
            $issue = '';
            $doi = '';
            $journal = '';
            $date_parts = array();
                    
            if (!empty($add_item['authors'])) $authors = $add_item['authors'];
            if (!empty($add_item['id'])) $id = $add_item['id'];
            if (!empty($add_item['title'])) $title = $add_item['title'];
            if (!empty($add_item['secondary_title'])) $secondary_title = $add_item['secondary_title'];
            if (!empty($add_item['pages'])) $pages = $add_item['pages'];
            if (!empty($add_item['volume'])) $volume = $add_item['volume'];
            if (!empty($add_item['issue'])) $issue = $add_item['issue'];
            if (!empty($add_item['doi'])) $doi = $add_item['doi'];
            if (!empty($add_item['journal'])) $journal = $add_item['journal'];
            if (!empty($add_item['year'])) $date_parts['date-parts'][] = explode("-", $add_item['year']);

            $i = 0;
            $new_authors = array();
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

            $json[$add_item['id']] = array(
                "id" => $id,
                "type" => "article-journal",
                "title" => $title,
                "container-title" => $secondary_title,
                "page" => $pages,
                "volume" => $volume,
                "issue" => $issue,
                "DOI" => $doi,
                "journalAbbreviation" => $journal,
                "author" => $new_authors,
                "issued" => $date_parts
            );
        }

        if ($_GET['format'] == 'zip') {

            $new_authors = array();
            $array = explode(';', $item['authors']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_authors[] = $last . ', ' . $first;
                }
                $authors = join('; ', $new_authors);
            }

            $paper .= '<p style="text-align: justify;border:1px solid #959698;margin:10px;padding:10px;background-color:#fff;border-radius:4px">';
            if ($i < 513 && is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '<a href="library/' . $item['file'] . '">';
            $paper .= '<b style="font-size:1.2em">' . $add_item['title'] . '</b>';
            if ($i < 513 && is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '</a>';
            if (!empty($item['authors']))
                $paper .= '<br>' . $authors;
            if (!empty($item['journal']))
                $paper .= '<br>' . $item['journal'];
            if (empty($item['journal']) && !empty($item['secondary_title']))
                $paper .= '<br>' . $item['secondary_title'];
            if (!empty($item['year']))
                $paper .= ' (' . $item['year'] . ')';
            if (!empty($item['volume']))
                $paper .= ' <i>' . $item['volume'] . '</i>';
            if (!empty($item['issue']))
                $paper .= ' (' . $item['issue'] . ')';
            if (!empty($item['pages']))
                $paper .= ': ' . $item['pages'];
            $paper .= '<br>';
            if ($i < 513 && is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '<a href="library/' . $item['file'] . '">';
            $paper .= $item['file'];
            if ($i < 513 && is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '</a>';
            if (!empty($item['abstract']))
                $paper .= '<br>' . $item['abstract'];
            $paper .= '</p>' . PHP_EOL;

            if ($i < 513 && is_readable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf'])) {
                $zip->addFile(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file'], 'library' . DIRECTORY_SEPARATOR . $item['file']);
                $i = $i + 1;
            }
        }

        if ($_GET['format'] == 'csv') {

            $new_authors = array();
            $array = explode(';', $add_item['authors']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_authors[] = $last . ', ' . $first;
                }
                $add_item['authors'] = join('; ', $new_authors);
            }

            $new_authors = array();
            $array = explode(';', $add_item['editor']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_authors[] = $last . ', ' . $first;
                }
                $add_item['editor'] = join('; ', $new_authors);
            }

            while (list($key, $value) = each($add_item)) {

                $columns[] = '"' . str_replace("\"", "\"\"", $value) . '"';
            }

            reset($add_item);

            $line = join(",", $columns);
            $paper .= $line . PHP_EOL;
            $columns = null;
        }

        if ($_GET['format'] == 'EndNote') {

            $endnote_translation = array(
                "%F" => "id",
                "%A" => "authors",
                "%+" => "affiliation",
                "%T" => "title",
                "%J" => "journal",
                "%D" => "year",
                "%V" => "volume",
                "%N" => "issue",
                "%P" => "pages",
                "%X" => "abstract",
                "%B" => "secondary_title",
                "%S" => "tertiary_title",
                "%E" => "editor",
                "%I" => "publisher",
                "%C" => "place_published",
                "%K" => "keywords",
                "%M" => "uid",
                "%M" => "doi",
                "%U" => "url");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(PHP_EOL . "%A ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['editor'])) {

                $new_authors = array();
                $array = explode(';', $add_item['editor']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(PHP_EOL . "%E ", $new_authors);
                $add_item['editor'] = $authors;
            }

            if (isset($add_item['url'])) {

                $add_item['url'] = explode("|", $add_item['url']);
                $urls = join(PHP_EOL . "%U ", $add_item['url']);
                $add_item['url'] = $urls;
            }

            if (isset($add_item['year'])) {

                if (!is_numeric($add_item['year'])) {
                    $add_item['year'] = substr($add_item['year'], 0, 4);
                }
            }

            while (list($key, $value) = each($add_item)) {

                $endnote_name = array_search($key, $endnote_translation);
                if ($endnote_name && !empty($value))
                    $columns[] = "$endnote_name $value";
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'endnote');
            $line = join(PHP_EOL, $columns);
            $paper .= '%0 ' . $type . PHP_EOL . $line . PHP_EOL . PHP_EOL;
            $columns = null;
        }

        if ($_GET['format'] == 'BibTex') {

            $bibtex_translation = array(
                "author = " => "authors",
                "title = " => "title",
                "journal = " => "journal",
                "year = " => "year",
                "volume = " => "volume",
                "number = " => "issue",
                "pages = " => "pages",
                "abstract = " => "abstract",
                "journal = " => "secondary_title",
                "series = " => "tertiary_title",
                "editor = " => "editor",
                "publisher = " => "publisher",
                "address = " => "place_published",
                "doi = " => "doi");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(" and ", $new_authors);
                $add_item['authors'] = $authors;
            }

            // bibtex does not have a journal abbreviation tag, but if user wants it, put abbreviation in journal tag
            if (isset($add_item['journal']) && !isset($add_item['secondary_title'])) {

                $add_item['secondary_title'] = $add_item['journal'];
            }

            if (isset($add_item['editor'])) {

                $new_authors = array();
                $array = explode(';', $add_item['editor']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(" and ", $new_authors);
                $add_item['editor'] = $authors;
            }

            if (isset($add_item['year'])) {

                if (!is_numeric($add_item['year'])) {
                    $add_item['year'] = substr($add_item['year'], 0, 4);
                }
            }

            if (isset($add_item['pages'])) {

                $add_item['pages'] = str_replace('-', '--', $add_item['pages']);
            }

            while (list($key, $value) = each($add_item)) {

                $value = str_replace('&', '\&', $value);
                $value = str_replace('{', '\{', $value);
                $value = str_replace('}', '\}', $value);
                $bibtex_name = array_search($key, $bibtex_translation);
                if ($bibtex_name && !empty($value))
                    $columns[] = $bibtex_name . '{' . $value . '}';
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'bibtex');
            $line = join(',' . PHP_EOL, $columns);
            $paper .= '@' . $type . '{' . $add_item['id'] . ',';
            $paper .= PHP_EOL . $line;
            if ($hosted == false && is_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file'])) {
                $paper .= ',' . PHP_EOL . 'file = {FULLTEXT:';
                $paper .= dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file'] . ':PDF}' . PHP_EOL;
            }
            $paper .= '}' . PHP_EOL . PHP_EOL;
            $columns = null;

            if ($item['reference_type'] == 'conference' || $item['reference_type'] == 'chapter') {
                $paper = str_replace('journal = {', 'booktitle = {', $paper);
            } elseif ($item['reference_type'] == 'book') {
                $paper = str_replace('journal = {', 'seriestitle = {', $paper);
            } elseif ($item['reference_type'] == 'thesis') {
                $paper = str_replace('journal = {', 'school = {', $paper);
            } elseif ($item['reference_type'] == 'manual') {
                $paper = str_replace('journal = {', 'section = {', $paper);
            } elseif ($item['reference_type'] == 'patent') {
                $paper = str_replace('journal = {', 'source = {', $paper);
            }
        }

        if ($_GET['format'] == 'RIS') {

            $ris_translation = array(
                "ID  - " => "id",
                "AU  - " => "authors",
                "AD  - " => "affiliation",
                "TI  - " => "title",
                "J2  - " => "journal",
                "DA  - " => "year",
                "VL  - " => "volume",
                "IS  - " => "issue",
                "SP  - " => "pages",
                "AB  - " => "abstract",
                "T2  - " => "secondary_title",
                "T3  - " => "tertiary_title",
                "ED  - " => "editor",
                "PB  - " => "publisher",
                "CY  - " => "place_published",
                "KW  - " => "keywords",
                "M2  - " => "uid",
                "DO  - " => "doi",
                "UR  - " => "url");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(PHP_EOL . "AU  - ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['editor'])) {

                $new_authors = array();
                $array = explode(';', $add_item['editor']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(PHP_EOL . "ED  - ", $new_authors);
                $add_item['editor'] = $authors;
            }

            if (isset($add_item['keywords'])) {

                $add_item['keywords'] = explode("/", $add_item['keywords']);
                $add_item['keywords'] = preg_replace("/(\s?)(.+)/ui", "\\2", $add_item['keywords']);
                $keywords = join(PHP_EOL . "KW  - ", $add_item['keywords']);
                $add_item['keywords'] = $keywords;
            }

            if (isset($add_item['url'])) {

                $add_item['url'] = explode("|", $add_item['url']);
                $urls = join(PHP_EOL . "UR  - ", $add_item['url']);
                $add_item['url'] = $urls;
            }

            if (isset($add_item['year'])) {

                if (is_numeric($add_item['year'])) {
                    $add_item['year'] = $add_item['year'] . "///";
                } else {
                    $add_item['year'] = str_replace('-', '/', $add_item['year']) . '/';
                }
            }

            while (list($key, $value) = each($add_item)) {

                $ris_name = array_search($key, $ris_translation);
                if ($ris_name && !empty($value))
                    $columns[] = $ris_name . $value;
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'ris');
            $line = join(PHP_EOL, $columns);
            $paper .= 'TY  - ' . $type . PHP_EOL;
            $paper .= $line . PHP_EOL;
            if ($hosted == false && is_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file'])) {
                $paper .= 'L1  - file://';
                if (substr(strtoupper(PHP_OS), 0, 3) == 'WIN')
                    $paper .= '/';
                $paper .= dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $item['file'] . PHP_EOL;
            }
            $paper .= 'ER  - ' . PHP_EOL . PHP_EOL;
            $columns = null;
        }
    }

    if ($_GET['format'] == 'citations') {

        // encode items in JSON
        $content = json_encode($json, JSON_HEX_APOS);
        $content = str_replace('"', '\"', $content);

        // fetch citation style
        try {
            $dbHandle = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'styles.sq3');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage();
            die();
        }
        $title_q = $dbHandle->quote(strtolower($_GET['citation-style']));
        $result = $dbHandle->query('SELECT style FROM styles WHERE title=' . $title_q);
        $style = $result->fetchColumn();
        if(empty($style)) die('This citation style does not exist.');
        $style = gzuncompress($style);
        $style = str_replace(array("\r\n", "\r", "\n"), "", $style);
        $style = str_replace("'", "\'", $style);

        // print citations to a browser window
        ?>
        <!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <title>I, Librarian</title>
                <style type="text/css">
                    body {
                        padding:0.75in 1in;
                    }
                    table {
                        margin:0;
                        border-spacing:0;
                    }
                    td {
                        font-family: serif;
                        font-size: 12pt;
                        padding:0;
                        padding-bottom:1em;
                        vertical-align: top;
                        line-height: 1.8em
                    }
                    .td-csl-left {
                        width: 5em
                    }
                </style>
                <script src="js/jquery.js"></script>
                <script src="js/csl/citeproc.min.js"></script>
            </head>
            <body>
                <table>
                    <tbody>
                    </tbody>
                </table>
                <script type="text/javascript">
                    // list of citations in json format
                    var citations = JSON.parse('<?php echo $content ?>');
                    // extract all citation keys
                    var itemIDs = [];
                    for (var key in citations) {
                        itemIDs.push(key);
                    }
                    // user-selected style
                    var style = '<?php echo $style ?>';
                    // initialize citeproc object
                    citeprocSys = {
                        retrieveLocale: function(lang) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', 'js/csl/locales/locales-' + lang + '.xml', false);
                            xhr.send(null);
                            return xhr.responseText;
                        },
                        retrieveItem: function(id) {
                            return citations[id];
                        }
                    };
                    // render citations
                    var citeproc = new CSL.Engine(citeprocSys, style);
                    citeproc.updateItems(itemIDs);
                    var bibResult = citeproc.makeBibliography();
                    // load them into a container, convert to table
                    $.each(bibResult[1], function(key,val){
                        // two columns vs one column layout
                        if($(val).children("div").length===2) {
                            $('tbody').append('<tr><td class="td-csl-left" valign="top" width="80">'
                                    + $(val).children("div").eq(0).html() + '</td><td valign="top">'
                                    + $(val).children("div").eq(1).html() + '</td></tr>');
                        } else if ($(val).children("div").length===0) {
                            $('tbody').append('<tr><td valign="top">' + $(val).html() + '</td></tr>');
                        }
                    });
                    // final formatting
                    $('td').css('line-height', 1.2 * bibResult[0]['linespacing'] + 'em')
                        .css('padding-bottom', bibResult[0]['entryspacing'] + 'em');
                    $('.td-csl-left').css('width', bibResult[0]['maxoffset'] + 'em')
                        .attr('width', 16 * bibResult[0]['maxoffset']);
                </script>
            </body>
        </html>
        <?php
        die();
    }

    if ($_GET['format'] == 'zip') {

        $content_type = 'application/zip';
        $filename = 'library.zip';

        $html = '<!DOCTYPE html><html>
                <head>
                 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                 <title>I, Librarian</title>
                </head><body style="padding:5px 10px;background-color:#E5E6E8">' . $paper . '</body></html>';
        $zip->addFromString('index.html', $html);
        $zip->close();
    }

    if ($_GET['format'] == 'csv') {

        $content_type = 'text/csv';
        $filename = 'library.csv';

        while (list($key, $value) = each($add_item)) {

            $column_names[] = "\"$key\"";
        }

        $header = join(",", $column_names);
        $header .= PHP_EOL;

        $content = $header . $paper;
    }

    if ($_GET['format'] == 'EndNote') {

        $content_type = 'text/plain';
        $filename = 'library.txt';

        $content = $paper;
    }

    if ($_GET['format'] == 'RIS') {

        $content_type = 'application/x-research-info-systems';
        $filename = 'library.ris';

        $content = $paper;
    }

    if ($_GET['format'] == 'BibTex') {

        $content_type = 'text/plain';
        $filename = 'library.bib';

        $content = $paper;
    }

    if ($_GET['output'] == 'attachment' || $_GET['format'] == 'zip') {
        header("Content-type: $content_type");
        header("Content-Disposition: attachment; filename=$filename");
    }
    header("Pragma: no-cache");
    header("Expires: 0");

    if ($_GET['output'] == 'inline' && $_GET['format'] != 'zip')
        print '<!DOCTYPE html><html><head><title>I, Librarian - Export</title></head><body><pre>';

    if ($_GET['format'] == 'zip') {
        $handle = fopen($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip', 'rb');
        while (!feof($handle)) {
            $content = fread($handle, 1024 * 1024);
            print $content;
        }
        fclose($handle);
        unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip');
    } else {
        print $content;
    }

    if ($_GET['output'] == 'inline' && $_GET['format'] != 'zip')
        print '</pre></body></html>';
} else {

    $get_post_export_files = 'session';
    if (isset($_GET['export_files']))
        $get_post_export_files = $_GET['export_files'];

    if (ini_get('safe_mode'))
        print 'Warning! Your php.ini configuration does not alow to run scipts for a long time.
				This may cause the export of a larger number (>10,000) of items to fail. Please unset safe_mode directive in your php.ini.';
    ?>
    <form id="exportform" action="export.php" method="GET">
        <table style="margin: auto;margin:10px auto">
            <tr>
                <td style="width:25em">
                    <input type="hidden" name="export_files" value="<?php print $get_post_export_files ?>">
                    <input type="hidden" name="export" value="1">
                    <b>Include:</b><br><br>
                    <span id="selectall" class="ui-state-highlight ui-corner-all">&nbsp;Select All&nbsp;</span> 
                    <span id="unselectall" class="ui-state-highlight ui-corner-all">&nbsp;Unselect All&nbsp;</span>
                    <table style="margin-top:6px">
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Unique ID" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Unique ID
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Authors" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Authors
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Title" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Title
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Journal" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Journal Abbreviation
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Year" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Year
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Volume" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Volume
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Pages" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Pages
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Issue" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Issue
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Abstract" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Abstract
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Secondary Title" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Secondary Title
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Tertiary Title" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Tertiary Title
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Keywords" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Keywords
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Affiliation" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Affiliation
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Accession ID" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Accession ID
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="DOI" style="display:none">
                                <i class="fa fa-square-o"></i>
                                DOI
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="URL" style="display:none">
                                <i class="fa fa-square-o"></i>
                                URL
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Editor" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Editor
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Publisher" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Publisher
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Place Published" style="display:none">
                                <i class="fa fa-square-o"></i>
                                Place Published
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:25em">
                    <b>Export format:</b><br><br>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="EndNote" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                EndNote
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="RIS" style="display:none" checked>
                                <i class="fa fa-circle"></i>
                                RIS
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="BibTex" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                BibTeX
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="csv" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                CSV (Office)
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span <?php print $nozip ? '' : ' ui-state-disabled'  ?>">
                                <input type="radio" name="format" value="zip" <?php print $nozip ? '' : 'disabled'  ?> style="display:none">
                                <i class="fa fa-circle-o"></i>
                                Zipped HTML
                                <table style="margin-left: 30px">
                                    <tr>
                                        <td class="select_span <?php print $nozip ? '' : ' ui-state-disabled'  ?>">
                                            <input type="checkbox" name="include_pdf" value="1" <?php print $nozip ? '' : 'disabled'  ?> style="display:none">
                                            <i class="fa fa-square-o"></i>
                                            include PDFs (max. 500)
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="citations" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                Citation style:
                                <table style="margin-left: 1em">
                                    <tr>
                                        <td class="select_span">
                                            <input type="text" name="citation-style" id="citation-style" placeholder=" e.g. Cell" style="width:24em">
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <b>Character encoding:</b><br><br>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="encoding" value="utf-8" checked style="display:none">
                                <i class="fa fa-circle"></i>
                                UTF-8 (accented letters)
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="encoding" value="ASCII" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                ASCII (no accents)
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <b>Output options:</b><br><br>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="output" value="inline" checked style="display:none">
                                <i class="fa fa-circle"></i>
                                display in browser
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="output" value="attachment" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                download file
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </form>
    <?php
}
?>

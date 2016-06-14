<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$nozip = false;
if (extension_loaded('zip'))
    $nozip = true;

(!empty($_SESSION['custom1'])) ? $custom1 = $_SESSION['custom1'] : $custom1 = 'Custom 1';
(!empty($_SESSION['custom2'])) ? $custom2 = $_SESSION['custom2'] : $custom2 = 'Custom 2';
(!empty($_SESSION['custom3'])) ? $custom3 = $_SESSION['custom3'] : $custom3 = 'Custom 3';
(!empty($_SESSION['custom4'])) ? $custom4 = $_SESSION['custom4'] : $custom4 = 'Custom 4';

if (!empty($_GET['export_files']) && isset($_GET['export'])) {

    if (!isset($_GET['column']))
        $_GET['column'][] = 'Title';

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
        "Publication Type" => "reference_type",
        "Custom 1" => "custom1",
        "Custom 2" => "custom2",
        "Custom 3" => "custom3",
        "Custom 4" => "custom4");

    $column_translation = array_flip($column_translation);

    if ($_GET['format'] == 'zip') {
        $zip = new ZipArchive;
        $zip->open(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip', ZIPARCHIVE::OVERWRITE);
    }

    $orderby = 'id DESC';

    database_connect(IL_DATABASE_PATH, 'library');

    if ($_GET['export_files'] == 'session') {

        $quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3');
        $dbHandle->exec("ATTACH DATABASE $quoted_path as history");

        $result = $dbHandle->query("SELECT * FROM library WHERE id IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY $orderby LIMIT 100000) ORDER BY $orderby");
    } else {

        $file_quote = $dbHandle->quote($_GET['export_files']);
        $result = $dbHandle->query("SELECT * FROM library WHERE id=" . $file_quote);
    }

    $paper = '';
    $i = 1;

    while ($item = $result->fetch(PDO::FETCH_ASSOC)) {

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

            if (!empty($item['bibtex'])) {

                $add_item['id'] = $item['bibtex'];
            } else {

                $id_author = substr($item['authors'], 3);
                $id_author = substr($id_author, 0, strpos($id_author, '"'));
                if (empty($id_author))
                    $id_author = 'unknown';

                $id_year_array = explode('-', $item['year']);
                $id_year = '0000';
                if (!empty($id_year_array[0]))
                    $id_year = $id_year_array[0];

                $add_item['id'] = utf8_deaccent($id_author) . '-' . $id_year . '-ID' . $item['id'];

                $add_item['id'] = str_replace(' ', '', $add_item['id']);
            }
        }

        if ($_GET['format'] == 'citations') {

            if (!empty($_GET['last-style']))
                $_GET['citation-style'] = $_GET['last-style'];

            if (empty($_GET['citation-style']))
                die('Citation style required.');

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
            $publisher_place = '';

            if (!empty($add_item['authors']))
                $authors = $add_item['authors'];
            if (!empty($add_item['editor']))
                $editors = $add_item['editor'];
            if (!empty($add_item['id']))
                $id = $add_item['id'];
            if (!empty($add_item['title']))
                $title = $add_item['title'];
            if (!empty($add_item['secondary_title']))
                $secondary_title = $add_item['secondary_title'];
            if (!empty($add_item['tertiary_title']))
                $tertiary_title = $add_item['tertiary_title'];
            if (!empty($add_item['pages']))
                $pages = $add_item['pages'];
            if (!empty($add_item['volume']))
                $volume = $add_item['volume'];
            if (!empty($add_item['issue']))
                $issue = $add_item['issue'];
            if (!empty($add_item['doi']))
                $doi = $add_item['doi'];
            if (!empty($add_item['journal']))
                $journal = $add_item['journal'];
            if (!empty($add_item['year']))
                $date_parts['date-parts'][] = explode("-", $add_item['year']);
            if (!empty($add_item['publisher']))
                $publisher = $add_item['publisher'];
            if (!empty($add_item['place_published']))
                $publisher_place = $add_item['place_published'];

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
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
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
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
                    $new_editors[$i]['family'] = $last;
                    $new_editors[$i]['given'] = $first;
                    $i++;
                }
            }

            $type = convert_type($item['reference_type'], 'ilib', 'csl');

            // CSL book type, shift secondary title to tertiary title
            if ($item['reference_type'] == 'book') {
                $tertiary_title = $secondary_title;
                $secondary_title = '';
            }

            $json[$add_item['id']] = array(
                "id" => $id,
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
                "publisher-place" => $publisher_place
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
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
                    $new_authors[] = $last . ', ' . $first;
                }
                $authors = join('; ', $new_authors);
            }

            $paper .= '<p style="text-align: justify;border:1px solid #959698;margin:10px;padding:10px;background-color:#fff;border-radius:4px">';
            if ($i < 513 && is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '<a href="library/' . $item['file'] . '">';
            $paper .= '<b style="font-size:1.2em">' . $add_item['title'] . '</b>';
            if ($i < 513 && is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
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
            if ($i < 513 && is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '<a href="library/' . $item['file'] . '">';
            $paper .= $item['file'];
            if ($i < 513 && is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf']))
                $paper .= '</a>';
            if (!empty($item['abstract']))
                $paper .= '<br>' . $item['abstract'];
            $paper .= '</p>' . PHP_EOL;

            if ($i < 513 && is_readable(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file']) && isset($_GET['include_pdf'])) {
                $zip->addFile(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file'], 'library' . DIRECTORY_SEPARATOR . $item['file']);
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
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
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
                    $first = '';
                    if (isset($array2[1])) {
                        $first = trim($array2[1]);
                        $first = substr($array2[1], 3, -1);
                    }
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
                "%U" => "url",
                "%1" => "custom1",
                "%2" => "custom2",
                "%3" => "custom3",
                "%4" => "custom4");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
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
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
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
                "author    = " => "authors",
                "title     = " => "title",
                "journal   = " => "journal",
                "year      = " => "year",
                "volume    = " => "volume",
                "number    = " => "issue",
                "pages     = " => "pages",
                "abstract  = " => "abstract",
                "journal   = " => "secondary_title",
                "series    = " => "tertiary_title",
                "editor    = " => "editor",
                "publisher = " => "publisher",
                "address   = " => "place_published",
                "doi       = " => "doi",
                "url       = " => "url",
                str_pad(str_replace(' ', '-', strtolower($custom1)), 9, ' ') . " = " => "custom1",
                str_pad(str_replace(' ', '-', strtolower($custom2)), 9, ' ') . " = " => "custom2",
                str_pad(str_replace(' ', '-', strtolower($custom3)), 9, ' ') . " = " => "custom3",
                str_pad(str_replace(' ', '-', strtolower($custom4)), 9, ' ') . " = " => "custom4");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
                        $new_authors[] = $last . ', ' . $first;
                    }
                }
                $authors = join(" and ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['url'])) {

                $urls = explode("|", $add_item['url']);
                $add_item['url'] = $urls[0];
            }

            // bibtex does not have a journal abbreviation tag, but if user wants it, put abbreviation in journal tag
            if ($item['reference_type'] == 'article' && !empty($add_item['journal']) && empty($add_item['secondary_title'])) {

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
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
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

            if ($item['reference_type'] == 'conference' || $item['reference_type'] == 'chapter') {
                unset($bibtex_translation['journal = ']);
                $bibtex_translation['booktitle = '] = 'secondary_title';
            } elseif ($item['reference_type'] == 'book') {
                unset($bibtex_translation['journal = ']);
                $bibtex_translation['series    = '] = 'secondary_title';
            } elseif ($item['reference_type'] == 'thesis') {
                unset($bibtex_translation['journal = ']);
                $bibtex_translation['school    = '] = 'secondary_title';
            } elseif ($item['reference_type'] == 'manual') {
                unset($bibtex_translation['journal = ']);
                $bibtex_translation['section   = '] = 'secondary_title';
            } elseif ($item['reference_type'] == 'patent') {
                unset($bibtex_translation['journal = ']);
                $bibtex_translation['source    = '] = 'secondary_title';
            }

            while (list($key, $value) = each($add_item)) {

                $value = wordwrap($value, 75, "\n            ");

                // Escape certain special chars.
                $value = str_replace('&', '\&', $value);
                $value = str_replace('%', '\%', $value);
                $value = str_replace('$', '\$', $value);

                $bibtex_name = array_search($key, $bibtex_translation);
                if ($bibtex_name && !empty($value)) {

                    // Protect capitalization.
                    $protected_fields = array(
                        'title     = ',
                        'booktitle = ',
                        'series    = ',
                        'journal   = '
                    );

                    if (in_array($bibtex_name, $protected_fields)) {
                        $value = preg_replace('/(\p{Lu}{2,})/u', '{$1}', $value);
                    }

                    $columns[] = $bibtex_name . '{' . $value . '}';
                }
            }

            // UIDs.
            if (!empty($add_item['uid'])) {

                $uids = explode('|', $add_item['uid']);

                foreach ($uids as $uid) {

                    $uid2 = explode(':', $uid);
                    $key = str_pad(str_replace(' ', '-', strtolower($uid2[0])), 9, ' ') . ' = ';
                    $columns[] = $key . '{' . $uid2[1] . '}';
                }
            }

            reset($add_item);

            if (!empty($item['bibtex_type'])) {
                $type = $item['bibtex_type'];
            } else {
                $type = convert_type($item['reference_type'], 'ilib', 'bibtex');
            }
            $line = join(',' . PHP_EOL, $columns);
            $paper .= '@' . $type . '{' . $add_item['id'] . ',';
            $paper .= PHP_EOL . $line;
            if ($hosted == false && is_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file'])) {
                $paper .= ',' . PHP_EOL . 'file      = {FULLTEXT:';
                $paper .= IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file'] . ':PDF}' . PHP_EOL;
            }
            $paper .= '}' . PHP_EOL . PHP_EOL;
            $columns = null;
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
                "UR  - " => "url",
                "C1  - " => "custom1",
                "C2  - " => "custom2",
                "C3  - " => "custom3",
                "C4  - " => "custom4");

            if (isset($add_item['authors'])) {

                $new_authors = array();
                $array = explode(';', $add_item['authors']);
                $array = array_filter($array);
                if (!empty($array)) {
                    foreach ($array as $author) {
                        $array2 = explode(',', $author);
                        $last = trim($array2[0]);
                        $last = substr($array2[0], 3, -1);
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
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
                        $first = '';
                        if (isset($array2[1])) {
                            $first = trim($array2[1]);
                            $first = substr($array2[1], 3, -1);
                        }
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
            if ($hosted == false && is_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file'])) {
                $paper .= 'L1  - file://';
                if (substr(strtoupper(PHP_OS), 0, 3) == 'WIN')
                    $paper .= '/';
                $paper .= IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($item['file']) . DIRECTORY_SEPARATOR . $item['file'] . PHP_EOL;
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
        $dbHandle = database_connect(__DIR__, 'styles');
        $title_q = $dbHandle->quote(strtolower($_GET['citation-style']));
        $result = $dbHandle->query('SELECT style FROM styles WHERE title=' . $title_q);
        $style = $result->fetchColumn();
        if (empty($style))
            die('This citation style does not exist.');
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
                        retrieveLocale: function (lang) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', 'js/csl/locales/locales-' + lang + '.xml', false);
                            xhr.send(null);
                            return xhr.responseText;
                        },
                        retrieveItem: function (id) {
                            return citations[id];
                        }
                    };
                    // render citations
                    var citeproc = new CSL.Engine(citeprocSys, style);
                    citeproc.updateItems(itemIDs);
                    var bibResult = citeproc.makeBibliography();
                    // load them into a container, convert to table
                    $.each(bibResult[1], function (key, val) {
                        // two columns vs one column layout
                        if ($(val).children("div").length === 2) {
                            $('tbody').append('<tr><td class="td-csl-left" valign="top" width="80">'
                                    + $(val).children("div").eq(0).html() + '</td><td valign="top">'
                                    + $(val).children("div").eq(1).html() + '</td></tr>');
                        } else if ($(val).children("div").length === 0) {
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
        $handle = fopen(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip', 'rb');
        while (!feof($handle)) {
            $content = fread($handle, 1024 * 1024);
            print $content;
        }
        fclose($handle);
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'test.zip');
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
                    <div id="export-radio">
                        <input type="radio" id="selectall" name="radio">
                        <label for="selectall">Select All</label>
                        <input type="radio" id="unselectall" name="radio">
                        <label for="unselectall">Unselect All</label>
                    </div>
                    <table style="margin-top:6px">
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Unique ID" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Citation Key
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
                                <input type="checkbox" name="column[]" value="Secondary Title" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Secondary Title
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Tertiary Title" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Tertiary Title
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
                                <input type="checkbox" name="column[]" value="Issue" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Issue
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="DOI" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                DOI
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Editor" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Editor
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Publisher" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Publisher
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Place Published" style="display:none" checked>
                                <i class="fa fa-check-square"></i>
                                Place Published
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
                                <input type="checkbox" name="column[]" value="URL" style="display:none">
                                <i class="fa fa-square-o"></i>
                                URL
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Custom 1" style="display:none">
                                <i class="fa fa-square-o"></i>
                                <?php echo $custom1 ?>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Custom 2" style="display:none">
                                <i class="fa fa-square-o"></i>
                                <?php echo $custom2 ?>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Custom 3" style="display:none">
                                <i class="fa fa-square-o"></i>
                                <?php echo $custom3 ?>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="column[]" value="Custom 4" style="display:none">
                                <i class="fa fa-square-o"></i>
                                <?php echo $custom4 ?>
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
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span <?php print $nozip ? '' : ' ui-state-disabled'  ?>" style="padding-left:1em">
                                <input type="checkbox" name="include_pdf" value="1" <?php print $nozip ? '' : 'disabled'  ?> style="display:none">
                                <i class="fa fa-square-o"></i>
                                include PDFs (max. 500)
                            </td>
                        </tr>
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="format" value="citations" style="display:none">
                                <i class="fa fa-circle-o"></i>
                                Citation style:
                                <table style="margin-left: 1em;margin-top:0.5em">
                                    <tr>
                                        <td class="select_span">
                                            <input type="text" name="citation-style" id="citation-style" placeholder=" e.g. Cell" style="width:24em">
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr id="last-style-tr" style="display:none">
                            <td class="select_span" style="padding-left:1em;padding-top:0.5em">
                                <input type="checkbox" name="last-style" style="display:none" value="">
                                <i class="fa fa-square-o"></i>
                                <span></span>
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

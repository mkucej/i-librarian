<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (isset($_SESSION['auth'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes',
        'annotations'
    ));
    cache_start($db_change);
}

database_connect($database_path, 'library');

$shelf_files = array();
$shelf_files = read_shelf($dbHandle);

$desktop_projects = array();
$desktop_projects = read_desktop($dbHandle);

if (isset($_GET['select'])) {

    if ($_GET['select'] != 'library' &&
            $_GET['select'] != 'shelf' &&
            $_GET['select'] != 'project' &&
            $_GET['select'] != 'clipboard') {

        $_GET['select'] = 'library';
    }

    $select = $_GET['select'];
} else {
    $select = 'library';
}

if (isset($_GET['file'])) {

    $query = $dbHandle->quote($_GET['file']);

    if (!isset($paper)) {
        $result = $dbHandle->query("SELECT * FROM library WHERE id=$query LIMIT 1");
        $paper = $result->fetch(PDO::FETCH_ASSOC);
    }

    if (!empty($paper['id'])) {

        $paper['journal'] = htmlspecialchars($paper['journal']);
        $paper['title'] = lib_htmlspecialchars($paper['title']);
        $paper['abstract'] = lib_htmlspecialchars($paper['abstract']);
        $paper['year'] = htmlspecialchars($paper['year']);

        #######new date#########
        $date = '';
        if (!empty($paper['year'])) {
            $date_array = array();
            $date_array = explode('-', $paper['year']);
            if (count($date_array) == 1) {
                $date = $paper['year'];
            } else {
                if (empty($date_array[0]))
                    $date_array[0] = '1969';
                if (empty($date_array[1]))
                    $date_array[1] = '01';
                if (empty($date_array[2]))
                    $date_array[2] = '01';
                $date = date('Y M j', mktime(0, 0, 0, $date_array[1], $date_array[2], $date_array[0]));
            }
        }

        print '<div id="file-panel2"><div id="file-item-' . $paper['id'] . '" class="items alternating_row" data-file="' . $paper['file'] . '">';

        print '<div class="titles file-title">' . $paper['title'] . '</div>';

        $authors_string = '';
        if (!empty($paper['authors'])) {
            $array = explode(';', $paper['authors']);
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
                $authors_string = join('; ', $new_authors);
            }

            print PHP_EOL . '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors_string) . '</div>';
        }

        if (!empty($paper['affiliation']))
            print PHP_EOL . '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($paper['affiliation']) . '</div>';

        print '<i>';

        if (!empty($paper['journal'])) {
            print $paper['journal'];
        } elseif (!empty($paper['secondary_title'])) {
            print $paper['secondary_title'];
        }
        if (!empty($date))
            print " ($date)";
        if (!empty($paper['volume']))
            print " <b>" . htmlspecialchars($paper['volume']) . "</b>";
        if (!empty($paper['issue']))
            print " (" . htmlspecialchars($paper['issue']) . ")";
        if (!empty($paper['pages']))
            print ": " . htmlspecialchars($paper['pages']) . ".";

        if (!empty($paper['tertiary_title'])) {
            print ', ' . $paper['tertiary_title'];
        }

        print "</i><br>";

        if (isset($_SESSION['auth'])) {

            print '<span><i class="star ' . (($paper['rating'] >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i>';
            print '&nbsp;<i class="star ' . (($paper['rating'] == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . ' fa fa-star"></i></span>&nbsp;';
        }

        $result = $dbHandle->query("SELECT categoryID,category
			FROM categories
			WHERE categoryID IN (SELECT categoryID
				FROM filescategories
				WHERE fileID=$query)
			ORDER BY category COLLATE NOCASE ASC");

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {
            $category_array[] = htmlspecialchars($categories['category']);
        }

        if (empty($category_array[0]))
            $category_array[0] = '!unassigned';

        $category_string = join(", ", $category_array);
        $category_array = null;

        if (is_file("library/$paper[file]") && isset($_SESSION['auth'])) {

            if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                print '&nbsp;<b>&middot;</b> <a title="Open PDF in new window. Right-click to download it." href="' . htmlspecialchars('downloadpdf.php?file=' . urlencode($paper['file']) . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . '" target="_blank" class="pdf_link">
				<span class="ui-state-highlight" style="padding:0px 2px 0px 2px;margin-right:2px">&nbsp;PDF&nbsp;</span></a>';

            if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal'))
                print '&nbsp;<b>&middot;</b> <a title="Open PDF in new window. Right-click to download it." href="' . htmlspecialchars('viewpdf.php?file=' . urlencode($paper['file']) . '&title=' . urlencode($paper['title'])) . '" target="_blank" class="pdf_link">
				<span class="ui-state-highlight ui-corner-all" style="padding:0px 2px 0px 2px;margin-right:2px">&nbsp;PDF&nbsp;</span></a>';
        }

        if (!empty($paper['doi'])) {

            print '&nbsp;<b>&middot;</b> <a href="http://dx.doi.org/' . urlencode($paper['doi']) . '" target="_blank">Publisher Website</a>';
        }

        if (!empty($paper['url'])) {

            $pmid_url = '';
            $pmcid_url = '';
            $nasaads_url = '';
            $arxiv_url = '';
            $jstor_url = '';
            $pmid_related_url = '';
            $pmid_citedby_pmc = '';
            $nasa_related_url = '';
            $nasa_citedby_pmc = '';
            $other_urls = array();

            $urls = explode("|", $paper['url']);

            while (list($key, $url) = each($urls)) {

                if (preg_match('/pubmed\.org/', $url)) {

                    $pmid_url = $url;
                } elseif (preg_match('/pubmedcentral\.nih\.gov/', $url) || preg_match('/\/pmc\//', $url)) {

                    $pmcid_url = $url;
                } elseif (preg_match('/adsabs\.harvard\.edu/', $url)) {

                    $nasaads_url = $url;
                } elseif (preg_match('/arxiv\.org/', $url)) {

                    $arxiv_url = $url;
                } elseif (preg_match('/jstor\.org/', $url)) {

                    $jstor_url = $url;
                } else {

                    $other_urls[] = $url;
                }
            }
        }

        if (!empty($paper['uid'])) {

            $uids = explode("|", $paper['uid']);

            while (list($key, $uid) = each($uids)) {
                if (preg_match('/PMID:/', $uid))
                    $pmid = preg_replace('/PMID:/', '', $uid);
                if (preg_match('/NASAADS:/', $uid))
                    $nasaid = preg_replace('/NASAADS:/', '', $uid);
                if (preg_match('/IEEE:/', $uid))
                    $ieeeid = preg_replace('/IEEE:/', '', $uid);
            }
            $uids = null;
        }

        if (!empty($pmid)) {
            $pmid_related_url = 'http://www.ncbi.nlm.nih.gov/sites/entrez?db=pubmed&cmd=link&linkname=pubmed_pubmed&uid=' . $pmid;
            $pmid_citedby_pmc = 'http://www.ncbi.nlm.nih.gov/pubmed?db=pubmed&cmd=link&linkname=pubmed_pubmed_citedin&uid=' . $pmid;
        }

        if (!empty($nasaid)) {
            $nasa_related_url = 'http://adsabs.harvard.edu/cgi-bin/nph-abs_connect?return_req=no_params&text=' . urlencode($paper['abstract']) . '&title=' . urlencode($paper['title']);
            $nasa_citedby_pmc = 'http://adsabs.harvard.edu/cgi-bin/nph-data_query?bibcode=' . $nasaid . '&link_type=CITATIONS';
        }

        if (!empty($ieeeid)) {
            $ieee_url = 'http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $ieeeid;
        }

        if (!empty($pmid_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($pmid_url) . "\" target=\"_blank\">PubMed</a>";
        }

        if (!empty($pmid_related_url)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($pmid_related_url) . '" target="_blank">
			Related Articles</a>';
        }

        if (!empty($pmid_citedby_pmc)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($pmid_citedby_pmc) . '" target="_blank">
			Cited by</a>';
        }

        if (!empty($pmcid_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($pmcid_url) . "\" target=\"_blank\">PubMed Central</a>";
        }

        if (!empty($nasaads_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($nasaads_url) . "\" target=\"_blank\">NASA ADS</a>";
        }

        if (!empty($nasa_related_url)) {
            print ' <b>&middot;</b> <a href="' . htmlspecialchars($nasa_related_url) . '" target="_blank" title="Related Articles">Related Articles</a>';
        }

        if (!empty($nasa_citedby_pmc)) {
            print ' <b>&middot;</b> <a href="' . htmlspecialchars($nasa_citedby_pmc) . '" target="_blank" title="Cited by">Cited by</a>';
        }

        if (!empty($arxiv_url)) {
            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($arxiv_url) . "\" target=\"_blank\">arXiv</a>";
        }

        if (!empty($jstor_url)) {
            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($jstor_url) . "\" target=\"_blank\">
			JSTOR</a>";
        }

        if (!empty($ieee_url)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($ieee_url) . '" target="_blank">IEEE</a>';
        }

        if (!empty($other_urls)) {

            foreach ($other_urls as $another_url) {
                $url_host = htmlspecialchars(parse_url($another_url, PHP_URL_HOST));
                print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($another_url) . "\" target=\"_blank\" class=\"anotherurl\" title=\"$url_host\">Link</a>";
            }
        }

        print '<div style="clear:both"></div>';

        if (isset($_SESSION['auth'])) {

            print ' <div class="noprint">';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files)) {
                print '<span class="update_shelf clicked"><i class="update_shelf fa fa-check-square ui-state-error-text"></i>&nbsp;Shelf&nbsp;</span>';
            } else {
                print '<span class="update_shelf"><i class="update_shelf fa fa-square-o"></i>&nbsp;Shelf&nbsp;</span>';
            }

            if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard'])) {
                print ' &nbsp;<span class="update_clipboard clicked"><i class="update_clipboard fa fa-check-square ui-state-error-text"></i>&nbsp;Clipboard&nbsp;</span>';
            } else {
                print ' &nbsp;<span class="update_clipboard"><i class="update_clipboard fa fa-square-o"></i>&nbsp;Clipboard&nbsp;</span>';
            }

            foreach ($desktop_projects as $desktop_project) {

                $project_rowid = $dbHandle->query("SELECT ROWID FROM projectsfiles WHERE projectID=" . intval($desktop_project['projectID']) . " AND fileID=" . intval($paper['id']) . " LIMIT 1");
                $project_rowid = $project_rowid->fetchColumn();

                if (!empty($project_rowid))
                    print ' &nbsp;<span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project clicked" style="white-space:pre;"><i class="update_project fa fa-check-square ui-state-error-text"></i>&nbsp;'
                            . htmlspecialchars($desktop_project['project']) . '&nbsp;</span>';

                if (empty($project_rowid))
                    print ' &nbsp;<span data-projid="' . $desktop_project['projectID']
                            . '" class="update_project" style="white-space:pre;"><i class="update_project fa fa-square-o"></i>&nbsp;'
                            . htmlspecialchars($desktop_project['project']) . '&nbsp;</span>';

                $project_rowid = null;
            }

            print ' </div>';
        }

        print '<div style="clear:both"></div>';

        print " </div>";

        $notes = null;

        if (isset($_SESSION['auth'])) {

            $user_query = $dbHandle->quote($_SESSION['user_id']);
            $result = $dbHandle->query("SELECT notesID,notes FROM notes WHERE fileID=$query AND userID=$user_query LIMIT 1");
            $fetched = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;

            $notesid = $fetched['notesID'];
            $notes = $fetched['notes'];
        }

        $url_filename = null;
        $url_filenames = array();
        if (isset($_SESSION['auth'])) {
            $integer = sprintf("%05d", intval($paper['file']));
            $files_to_display = glob('library/supplement/' . $integer . '*');
            if (is_array($files_to_display)) {
                foreach ($files_to_display as $supplementary_file) {
                    $url_filenames[] = '<li><a href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">' . substr(basename($supplementary_file), 5) . '</a>';
                }
                $url_filename = join('<br>', $url_filenames);
            }
        }

        $annotation = '';
        if (isset($_SESSION['auth'])) {
            $result = $dbHandle->query("SELECT annotation FROM annotations WHERE userID=" . intval($_SESSION['user_id']) . " AND filename='" . $paper['file'] . "' ORDER BY id ASC");
            while ($annotations = $result->fetch(PDO::FETCH_ASSOC)) {
                $annotation .= '<div>' . htmlspecialchars($annotations['annotation']) . '</div><br>';
            }
            $result = null;
        }

        print '<div class="file-grid" style="border-bottom:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">Abstract</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row abstract" style="padding:4px 7px;overflow:auto;height:260px;column-count:auto;-moz-column-count:auto;-webkit-column-count:auto">'
                . $paper['abstract']
                . '</div>
            </div>
            <div class="file-grid" style="border-bottom:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0;">Notes</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;height:260px;overflow:auto">' . $notes . '</div>
            </div>
            <div class="file-grid" style="width:33.33%;border-bottom:0;border-right:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">Graphical Abstract</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="height:268px;overflow:auto">';

        if (isset($_SESSION['auth']) && is_file(graphical_abstract($paper['file'])))
            echo '<img src="' . htmlspecialchars('attachment.php?mode=inline&attachment=' . basename(graphical_abstract($paper['file']))) . '">';

        print '</div>
            </div>
            <div class="file-grid" style="border-bottom:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0;position:relative">
                    Supplementary Files
                </div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px"><ul style="padding-left:16px;width:90%">' . $url_filename . '</ul></div>
            </div>
            <div class="file-grid" style="border-bottom:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">PDF Notes</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px">' . $annotation . '</div>
            </div>
            <div class="file-grid" style="border-right:0;width:33.33%;border-bottom:0">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">IDs</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px">';

        print "<u>I, Librarian ID:</u> <a href=\"stable.php?id=$paper[id]\" target=\"_blank\">" . $paper['id'] . " (stable link)</a>";

        $uid_array = explode("|", $paper['uid']);

        foreach ($uid_array as $uid) {
            $uids[] = $uid;
        }

        print "<br><u>External IDs:</u> " . htmlspecialchars(implode(", ", $uids));

        print '<br><u>DOI:</u> <span id="file-doi">' . htmlspecialchars($paper['doi']) . '</span>';

        if (!empty($paper['bibtex'])) {
            print '<br><u>BibTex:</u> <input type="text" class="bibtex" value="' . htmlspecialchars($paper['bibtex']) . '" style="outline:none;width:200px" readonly>';
        } else {
            $bibtex_author = substr($paper['authors'], 3);
            $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, '"'));
            if (empty($bibtex_author))
                $bibtex_author = 'unknown';

            $bibtex_year = '0000';
            $bibtex_year_array = explode('-', $paper['year']);
            if (!empty($bibtex_year_array[0]))
                $bibtex_year = $bibtex_year_array[0];

            $bibtex_key = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];
            print '<br><u>BibTex:</u> <input type="text" class="bibtex" value="' . $bibtex_key . '" style="outline:none;width:200px" readonly>';
        }

        $editor_string = '';
        if (!empty($paper['editor'])) {
            $array = explode(';', $paper['editor']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $editor) {
                    $array2 = explode(',', $editor);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_editor[] = $last . ', ' . $first;
                }
                $editor_string = join('; ', $new_editor);
            }
        }

        print '</div>
            </div>
            <div class="file-grid">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">Categories</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px">' . $category_string . '</div>
            </div>
            <div class="file-grid">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">Keywords</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px">' . htmlspecialchars($paper['keywords']) . '</div>
            </div>
            <div class="file-grid" style="border-right:0;width:33.33%">
                <div class="ui-widget-header ui-dialog-titlebar items" style="border:0;border-radius:0">Miscellaneous</div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row" style="padding:4px 7px;overflow:auto;height:150px">
                <u>Publication type:</u> ' . htmlspecialchars($paper['reference_type'])
                . '<br><u>Editor:</u> ' . htmlspecialchars($editor_string)
                . '<br><u>Publisher:</u> ' . htmlspecialchars($paper['publisher'])
                . '<br><u>Place published:</u> ' . htmlspecialchars($paper['place_published'])
                . '<br><u>Custom 1:</u> ' . htmlspecialchars($paper['custom1'])
                . '<br><u>Custom 2:</u> ' . htmlspecialchars($paper['custom2'])
                . '<br><u>Custom 3:</u> ' . htmlspecialchars($paper['custom3'])
                . '<br><u>Custom 4:</u> ' . htmlspecialchars($paper['custom4'])
                . '</div>
            </div>';

        print '<div style="clear:both"></div><div style="padding:12px"><b>Added:</b> ' . date('F jS, Y', strtotime($paper['addition_date'])) . ' by ' . htmlspecialchars(get_username($dbHandle, $database_path, $paper['added_by']));

        if (!empty($paper['modified_date']))
            print " <b>&middot; Modified:</b> " . date("F jS, Y, g:i A", strtotime($paper['modified_date'])) . " by " . htmlspecialchars(get_username($dbHandle, $database_path, $paper['modified_by']));

        print '</div>';
        print '<script type="text/javascript">$("title").text("' . $paper['title'] . '")</script>';
    }
}

if (isset($_SESSION['auth']))
    cache_store();
?>

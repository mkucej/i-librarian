<?php
include_once 'data.php';

if (isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    database_connect($usersdatabase_path, 'users');
    $user_query = $dbHandle->quote($_SESSION['user_id']);
    $result = $dbHandle->query("SELECT setting_name FROM settings WHERE userID=$user_query AND setting_name LIKE 'settings_remove_%'");
    $settings = $result->fetchAll(PDO::FETCH_ASSOC);
    $result = null;
    $dbHandle = null;

    foreach ($settings as $setting) {
        ${$setting['setting_name']} = 1;
    }
    ?>
    <div class="leftindex" id="addarticle-left" style="float:left;width:240px;height:100%;overflow:scroll">
        <table cellspacing=0 style="margin:8px 0 6px 0;width:93%">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="uploadlink">
                    Add Single Item
                </td>
            </tr>
        </table>
        <table cellspacing=0 style="margin:6px 0;width:93%">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="importlink">
                    Add Multiple Items
                </td>
            </tr>
        </table>
        <table cellspacing=0 style="margin:6px 0;width:93%">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="<?php print ($hosted == false) ? 'batchimportlink' : 'importany'  ?>">
                    Add Multiple PDFs
                </td>
            </tr>
        </table>
        <?php
        if ($hosted == false) {
            ?>
            <div style="padding-left: 10px;width:190px">
                <div class="select-import" id="importlocalhost">from localhost</div>
                <div class="select-import" id="importany">from any computer</div>
            </div>
            <?php
        }

        database_connect($database_path, 'library');
        $user_query = $dbHandle->quote($_SESSION['user_id']);
        $result = $dbHandle->query("SELECT DISTINCT searchname FROM searches WHERE userID=$user_query ORDER BY searchname ASC");
        $searchnames = $result->fetchAll(PDO::FETCH_COLUMN);
        $result = null;

        if (!isset($_SESSION['remove_pubmed'])) {
            //HOW MANY FLAGGED?
            $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='pubmed'");
            if ($result)
                $flagged_count = $result->fetchColumn();
            $result = null;
            ?>
            <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 23 million records">
                <tr>
                    <td class="leftleftbutton">&nbsp;</td>
                    <td class="leftbutton ui-widget-header ui-corner-right" id="pubmedlink">
                        PubMed
                    </td>
                </tr>
            </table>
            <div id="pubmed-container" style="padding-left: 10px;width:190px">
                <div class="ui-state-highlight empty-flagged pubmed"><i class="fa fa-trash-o"></i></div>
                <span class="pubmed flagged-items">Flagged Items</span><br>
                &nbsp;&nbsp;<span id="pubmed-flagged-count"><?php print isset($flagged_count) ? $flagged_count : '0'  ?></span>/100
                <div style="clear:both"></div>
                <?php
                while (list($key, $searchname) = each($searchnames)) {

                    if (substr($searchname, 0, 7) == "pubmed#") {

                        $searchname_query = $dbHandle->quote($searchname);
                        $result = $dbHandle->query("SELECT searchvalue FROM searches WHERE userID=$user_query AND searchfield='last_search' AND searchname=$searchname_query LIMIT 1");
                        $last_search_stamp = $result->fetchColumn();
                        $result = null;
                        $last_search = round((time() - $last_search_stamp) / 86400, 1);
                        if ($last_search < 1) {
                            $last_search = round((time() - $last_search_stamp) / 3600);
                            $last_search .= ' hour' . (($last_search != 1) ? 's' : '') . ' ago';
                        } elseif ($last_search > 365) {
                            $last_search = '>1 year ago';
                        } else {
                            $last_search = round($last_search);
                            $last_search .= ' day' . (($last_search != 1) ? 's' : '') . ' ago';
                        }
                        if ($last_search_stamp < 2)
                            $last_search = 'Never';
                        print '<div class="pubmed">';
                        print '<div class="ui-state-highlight del-saved-search pubmed"><i class="fa fa-trash-o"></i></div>';
                        print '<span class="saved-search pubmed" id="saved-search-pubmed-' . htmlspecialchars(rawurlencode(substr($searchname, 7))) . '">';
                        print htmlspecialchars(substr($searchname, 7));
                        print '</span><br>&nbsp;&nbsp;<span>' . $last_search;
                        print '</span></div><div style="clear:both"></div>';
                    }
                }
                reset($searchnames);
                print '</div>';
            }
            if (!isset($_SESSION['remove_pmc'])) {
                //HOW MANY FLAGGED?
                $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='pmc'");
                if ($result)
                    $flagged_count = $result->fetchColumn();
                $result = null;
                ?>
                <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 3 million records">
                    <tr>
                        <td class="leftleftbutton">&nbsp;</td>
                        <td class="leftbutton ui-widget-header ui-corner-right" id="pmclink">
                            PubMed Central
                        </td>
                    </tr>
                </table>
                <div id="pmc-container" style="padding-left: 10px;width:190px">
                    <div class="ui-state-highlight empty-flagged pmc"><i class="fa fa-trash-o"></i></div>
                    <span class="pmc flagged-items">Flagged Items</span><br>
                    &nbsp;&nbsp;<span id="pmc-flagged-count"><?php print isset($flagged_count) ? $flagged_count : '0'  ?></span>/100
                    <div style="clear:both"></div>
                    <?php
                    while (list($key, $searchname) = each($searchnames)) {

                        if (substr($searchname, 0, 4) == "pmc#") {

                            $searchname_query = $dbHandle->quote($searchname);
                            $result = $dbHandle->query("SELECT searchvalue FROM searches WHERE userID=$user_query AND searchfield='pmc_last_search' AND searchname=$searchname_query LIMIT 1");
                            $last_search_stamp = $result->fetchColumn();
                            $result = null;
                            $last_search = round((time() - $last_search_stamp) / 86400, 1);
                            if ($last_search < 1) {
                                $last_search = round((time() - $last_search_stamp) / 3600);
                                $last_search .= ' hour' . (($last_search != 1) ? 's' : '') . ' ago';
                            } elseif ($last_search > 365) {
                                $last_search = '>1 year ago';
                            } else {
                                $last_search = round($last_search);
                                $last_search .= ' day' . (($last_search != 1) ? 's' : '') . ' ago';
                            }
                            if ($last_search_stamp < 2)
                                $last_search = 'Never';
                            print '<div class="pmc">';
                            print '<div class="ui-state-highlight del-saved-search pmc"><i class="fa fa-trash-o"></i></div>';
                            print '<span class="saved-search pmc" id="saved-search-pmc-' . htmlspecialchars(rawurlencode(substr($searchname, 4))) . '">';
                            print htmlspecialchars(substr($searchname, 4));
                            print '</span><br>&nbsp;&nbsp;<span>' . $last_search;
                            print '</span></div><div style="clear:both"></div>';
                        }
                    }
                    reset($searchnames);
                    print '</div>';
                }
                if (!isset($_SESSION['remove_nasaads'])) {
                    //HOW MANY FLAGGED?
                    $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='nasaads'");
                    if ($result)
                        $flagged_count = $result->fetchColumn();
                    $result = null;
                    ?>
                    <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 10 million records">
                        <tr>
                            <td class="leftleftbutton">&nbsp;</td>
                            <td class="leftbutton ui-widget-header ui-corner-right" id="nasalink">
                                NASA ADS
                            </td>
                        </tr>
                    </table>
                    <div id="nasaads-container" style="padding-left: 10px;width:190px">
                        <div class="ui-state-highlight empty-flagged nasaads"><i class="fa fa-trash-o"></i></div>
                        <span class="nasaads flagged-items">Flagged Items</span><br>
                        &nbsp;&nbsp;<span id="nasaads-flagged-count"><?php print isset($flagged_count) ? $flagged_count : '0'  ?></span>/100
                        <div style="clear:both"></div>
                        <?php
                        while (list($key, $searchname) = each($searchnames)) {

                            if (substr($searchname, 0, 8) == "nasaads#") {

                                $searchname_query = $dbHandle->quote($searchname);
                                $result = $dbHandle->query("SELECT searchvalue FROM searches WHERE userID=$user_query AND searchfield='nasa_last_search' AND searchname=$searchname_query LIMIT 1");
                                $last_search_stamp = $result->fetchColumn();
                                $result = null;
                                $last_search = round((time() - $last_search_stamp) / 86400, 1);
                                if ($last_search < 1) {
                                    $last_search = round((time() - $last_search_stamp) / 3600);
                                    $last_search .= ' hour' . (($last_search != 1) ? 's' : '') . ' ago';
                                } elseif ($last_search > 365) {
                                    $last_search = '>1 year ago';
                                } else {
                                    $last_search = round($last_search);
                                    $last_search .= ' day' . (($last_search != 1) ? 's' : '') . ' ago';
                                }
                                if ($last_search_stamp < 2)
                                    $last_search = 'Never';
                                print '<div class="nasaads">';
                                print '<div class="ui-state-highlight del-saved-search nasaads"><i class="fa fa-trash-o"></i></div>';
                                print '<span class="saved-search nasaads" id="saved-search-nasaads-' . htmlspecialchars(rawurlencode(substr($searchname, 8))) . '">';
                                print htmlspecialchars(substr($searchname, 8));
                                print '</span><br>&nbsp;&nbsp;<span>' . $last_search;
                                print '</span></div><div style="clear:both"></div>';
                            }
                        }
                        reset($searchnames);
                        print '</div>';
                    }
                    if (!isset($_SESSION['remove_arxiv'])) {
                        //HOW MANY FLAGGED?
                        $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='arxiv'");
                        if ($result)
                            $flagged_count = $result->fetchColumn();
                        $result = null;
                        ?>
                        <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 800 thousands records">
                            <tr>
                                <td class="leftleftbutton">&nbsp;</td>
                                <td class="leftbutton ui-widget-header ui-corner-right" id="arxivlink">
                                    arXiv
                                </td>
                            </tr>
                        </table>
                        <div id="arxiv-container" style="padding-left: 10px;width:190px">
                            <div class="ui-state-highlight empty-flagged arxiv"><i class="fa fa-trash-o"></i></div>
                            <span class="arxiv flagged-items">Flagged Items</span><br>
                            &nbsp;&nbsp;<span id="arxiv-flagged-count"><?php print isset($flagged_count) ? $flagged_count : '0'  ?></span>/100
                            <div style="clear:both"></div>
                            <?php
                            while (list($key, $searchname) = each($searchnames)) {

                                if (substr($searchname, 0, 6) == "arxiv#") {

                                    $searchname_query = $dbHandle->quote($searchname);
                                    $result = $dbHandle->query("SELECT searchvalue FROM searches WHERE userID=$user_query AND searchfield='arxiv_last_search' AND searchname=$searchname_query LIMIT 1");
                                    $last_search_stamp = $result->fetchColumn();
                                    $result = null;
                                    $last_search = round((time() - $last_search_stamp) / 86400, 1);
                                    if ($last_search < 1) {
                                        $last_search = round((time() - $last_search_stamp) / 3600);
                                        $last_search .= ' hour' . (($last_search != 1) ? 's' : '') . ' ago';
                                    } elseif ($last_search > 365) {
                                        $last_search = '>1 year ago';
                                    } else {
                                        $last_search = round($last_search);
                                        $last_search .= ' day' . (($last_search != 1) ? 's' : '') . ' ago';
                                    }
                                    if ($last_search_stamp < 2)
                                        $last_search = 'Never';
                                    print '<div class="arxiv">';
                                    print '<div class="ui-state-highlight del-saved-search arxiv"><i class="fa fa-trash-o"></i></div>';
                                    print '<span class="saved-search arxiv" id="saved-search-arxiv-' . htmlspecialchars(rawurlencode(substr($searchname, 6))) . '">';
                                    print htmlspecialchars(substr($searchname, 6));
                                    print '</span><br>&nbsp;&nbsp;<span>' . $last_search;
                                    print '</span></div><div style="clear:both"></div>';
                                }
                            }
                            reset($searchnames);
                            print '</div>';
                        }
                        if (!isset($_SESSION['remove_ieee'])) {
                            ?>
                            <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 3 million records">
                                <tr>
                                    <td class="leftleftbutton">&nbsp;</td>
                                    <td class="leftbutton ui-widget-header ui-corner-right" id="ieeelink">
                                        IEEE Xplore
                                    </td>
                                </tr>
                            </table>
                            <div id="ieee-container" style="padding-left: 10px;width:190px">
                                <?php
                                while (list($key, $searchname) = each($searchnames)) {

                                    if (substr($searchname, 0, 5) == "ieee#") {

                                        print '<div class="ieee">';
                                        print '<div class="ui-state-highlight del-saved-search ieee"><i class="fa fa-trash-o"></i></div>';
                                        print '<span class="saved-search ieee" id="saved-search-ieee-' . htmlspecialchars(rawurlencode(substr($searchname, 5))) . '">';
                                        print htmlspecialchars(substr($searchname, 5));
                                        print '</span></div><div style="clear:both"></div>';
                                    }
                                }
                                reset($searchnames);
                                print '</div>';
                            }
//                            if (!isset($_SESSION['remove_citeseer'])) {
                                ?>
<!--                                <table cellspacing=0 style="margin:6px 0;width:93%">
                                    <tr>
                                        <td class="leftleftbutton">&nbsp;</td>
                                        <td class="leftbutton ui-widget-header ui-corner-right" id="citeseerlink">
                                            CiteSeerX
                                        </td>
                                    </tr>
                                </table>
                                <div id="citeseer-container" style="padding-left: 10px;width:190px">-->
                                    <?php
//                                    while (list($key, $searchname) = each($searchnames)) {
//
//                                        if (substr($searchname, 0, 9) == "citeseer#") {
//
//                                            print '<div class="citeseer">';
//                                            print '<div class="ui-state-highlight del-saved-search citeseer"><i class="fa fa-trash-o"></i></div>';
//                                            print '<span class="saved-search citeseer" id="saved-search-citeseer-' . htmlspecialchars(rawurlencode(substr($searchname, 9))) . '">';
//                                            print htmlspecialchars(substr($searchname, 5));
//                                            print '</span></div><div style="clear:both"></div>';
//                                        }
//                                    }
//                                    reset($searchnames);
//                                    print '</div>';
//                                }
                                if (!isset($_SESSION['remove_springer'])) {
                                    ?>
                                    <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 8 million records">
                                        <tr>
                                            <td class="leftleftbutton">&nbsp;</td>
                                            <td class="leftbutton ui-widget-header ui-corner-right" id="springerlink">
                                                Springer
                                            </td>
                                        </tr>
                                    </table>
                                    <div id="springer-container" style="padding-left: 10px;width:190px">
                                        <?php
                                        while (list($key, $searchname) = each($searchnames)) {

                                            if (substr($searchname, 0, 9) == "springer#") {

                                                print '<div class="springer">';
                                                print '<div class="ui-state-highlight del-saved-search springer"><i class="fa fa-trash-o"></i></div>';
                                                print '<span class="saved-search springer" id="saved-search-springer-' . htmlspecialchars(rawurlencode(substr($searchname, 9))) . '">';
                                                print htmlspecialchars(substr($searchname, 9));
                                                print '</span></div><div style="clear:both"></div>';
                                            }
                                        }
                                        reset($searchnames);
                                        print '</div>';
                                    }
                                    if (!isset($_SESSION['remove_highwire'])) {
                                        ?>
                                        <table cellspacing=0 style="margin:6px 0;width:93%" title="Search over 7 million records">
                                            <tr>
                                                <td class="leftleftbutton">&nbsp;</td>
                                                <td class="leftbutton ui-widget-header ui-corner-right" id="highwirelink">
                                                    HighWire Press
                                                </td>
                                            </tr>
                                        </table>
                                        <div id="highwire-container" style="padding-left: 10px;width:190px">
                                            <?php
                                            while (list($key, $searchname) = each($searchnames)) {

                                                if (substr($searchname, 0, 9) == "highwire#") {

                                                    print '<div class="highwire">';
                                                    print '<div class="ui-state-highlight del-saved-search highwire"><i class="fa fa-trash-o"></i></div>';
                                                    print '<span class="saved-search highwire" id="saved-search-highwire-' . htmlspecialchars(rawurlencode(substr($searchname, 9))) . '">';
                                                    print htmlspecialchars(substr($searchname, 9));
                                                    print '</span></div><div style="clear:both"></div>';
                                                }
                                            }
                                            reset($searchnames);
                                            print '</div>';
                                        }
                                        
                                        $searchnames = null;
                                        $dbHandle = null;
                                        ?>
                                        <br>
                                    </div>
                                    <div style="height:100%;overflow:auto" id="addarticle-right"></div>
                                    <?php
                                } else {
                                    print 'Super User or User permissions required.';
                                }
                                ?>
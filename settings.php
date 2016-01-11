<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    if (isset($_GET['form']) && $_GET['form'] == 'submitted') {

        // Security checks.
        // User-specific settings first.
        $new_settings['limit'] = '';
        if ($_GET['limit'] != 10 && $_GET['limit'] > 0 && $_GET['limit'] < 101) {
            $new_settings['limit'] = intval($_GET['limit']);
        }

        $new_settings['display'] = '';
        if ($_GET['display'] != 'summary' && ctype_alpha($_GET['display'])) {
            $new_settings['display'] = $_GET['display'];
        }

        $new_settings['orderby'] = '';
        if ($_GET['orderby'] != 'id' && ctype_alpha($_GET['orderby'])) {
            $new_settings['orderby'] = $_GET['orderby'];
        }

        $new_settings['remove_pubmed'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("pubmed", $_GET['database_links']))) {
            $new_settings['remove_pubmed'] = 1;
        }

        $new_settings['remove_pmc'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("pmc", $_GET['database_links']))) {
            $new_settings['remove_pmc'] = 1;
        }

        $new_settings['remove_nasaads'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("nasaads", $_GET['database_links']))) {
            $new_settings['remove_nasaads'] = 1;
        }

        $new_settings['remove_arxiv'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("arxiv", $_GET['database_links']))) {
            $new_settings['remove_arxiv'] = 1;
        }

        $new_settings['remove_highwire'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("highwire", $_GET['database_links']))) {
            $new_settings['remove_highwire'] = 1;
        }

        $new_settings['remove_ieee'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("ieee", $_GET['database_links']))) {
            $new_settings['remove_ieee'] = 1;
        }

        $new_settings['remove_springer'] = '';
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("springer", $_GET['database_links']))) {
            $new_settings['remove_springer'] = 1;
        }

        $new_settings['pdfviewer'] = '';
        if ($_GET['pdfviewer'] === 'internal' || $_GET['pdfviewer'] === 'external') {
            $new_settings['pdfviewer'] = $_GET['pdfviewer'];
        }

        // Global settings, super user permissions required.
        if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

            $new_settings['global_watermarks'] = '';
            if (!empty($_GET['watermarks'])) {
                $new_settings['global_watermarks'] = $_GET['watermarks'];
            }
            
            $new_settings['global_signin_mode'] = '';
            if ($_GET['signin_mode'] == 'textinput') {
                $new_settings['global_signin_mode'] = $_GET['signin_mode'];
            }

            $new_settings['global_disallow_signup'] = '';
            if (!isset($_GET['disallow_signup'])) {
                $new_settings['global_disallow_signup'] = 1;
            }

            $new_settings['global_default_permissions'] = '';
            if (in_array($_GET['default_permissions'], array('A', 'U', 'G'))) {
                $new_settings['global_default_permissions'] = $_GET['default_permissions'];
            }

            $new_settings['global_custom1'] = '';
            if (!empty($_GET['custom1'])) {
                $new_settings['global_custom1'] = filter_var($_GET['custom1'], FILTER_SANITIZE_STRING);
            }

            $new_settings['global_custom2'] = '';
            if (!empty($_GET['custom2'])) {
                $new_settings['global_custom2'] = filter_var($_GET['custom2'], FILTER_SANITIZE_STRING);
            }
            
            $new_settings['global_custom3'] = '';
            if (!empty($_GET['custom3'])) {
                $new_settings['global_custom3'] = filter_var($_GET['custom3'], FILTER_SANITIZE_STRING);
            }
            
            $new_settings['global_custom4'] = '';
            if (!empty($_GET['custom4'])) {
                $new_settings['global_custom4'] = filter_var($_GET['custom4'], FILTER_SANITIZE_STRING);
            }
            
            $new_settings['global_connection'] = '';
            if (isset($_GET['connection']) && $_GET['connection'] !== 'direct') {
                $new_settings['global_connection'] = $_GET['connection'];
            }
            
            $new_settings['global_wpad_url'] = '';
            if (isset($_GET['wpad_url'])) {
                $new_settings['global_wpad_url'] = $_GET['wpad_url'];
            }

            $new_settings['global_proxy_name'] = '';
            if (isset($_GET['proxy_name']) && !empty($_GET['proxy_name'])) {
                $new_settings['global_proxy_name'] = $_GET['proxy_name'];
            }

            $new_settings['global_proxy_port'] = '';
            if (isset($_GET['proxy_port']) && !empty($_GET['proxy_port'])) {
                $new_settings['global_proxy_port'] = $_GET['proxy_port'];
            }

            $new_settings['global_proxy_username'] = '';
            if (isset($_GET['proxy_username']) && isset($new_settings['global_proxy_name'])) {
                $new_settings['global_proxy_username'] = $_GET['proxy_username'];
            }

            $new_settings['global_proxy_password'] = '';
            if (isset($_GET['proxy_password']) && isset($new_settings['global_proxy_name'])) {
                $new_settings['global_proxy_password'] = $_GET['proxy_password'];
            }
            
            $new_settings['global_zone'] = '';
            if (isset($_GET['zone'])) {
                $new_settings['global_zone'] = $_GET['zone'];
            }
        }

        $dbHandle = database_connect(IL_USER_DATABASE_PATH, 'users');

        save_settings($dbHandle, $new_settings);

        $dbHandle = null;
    }

    // Clear setting values.
    $limit = '';
    unset($_SESSION['limit']);
    $display = '';
    unset($_SESSION['display']);
    $orderby = '';
    unset($_SESSION['orderby']);
    $remove_pubmed = '';
    unset($_SESSION['remove_pubmed']);
    $remove_pmc = '';
    unset($_SESSION['remove_pmc']);
    $remove_nasaads = '';
    unset($_SESSION['remove_nasaads']);
    $remove_arxiv = '';
    unset($_SESSION['remove_arxiv']);
    $remove_ieee = '';
    unset($_SESSION['remove_ieee']);
    $remove_highwire = '';
    unset($_SESSION['remove_highwire']);
    $remove_springer = '';
    unset($_SESSION['remove_springer']);
    $pdfviewer = '';
    unset($_SESSION['pdfviewer']);
    $watermarks = '';
    unset($_SESSION['watermarks']);
    $signin_mode = '';
    unset($_SESSION['signin_mode']);
    $disallow_signup = '';
    unset($_SESSION['disallow_signup']);
    $default_permissions = '';
    unset($_SESSION['default_permissions']);
    $custom1 = '';
    unset($_SESSION['custom1']);
    $custom2 = '';
    unset($_SESSION['custom2']);
    $custom3 = '';
    unset($_SESSION['custom3']);
    $custom4 = '';
    unset($_SESSION['custom4']);
    $connection = '';
    unset($_SESSION['connection']);
    $wpad_url = '';
    unset($_SESSION['wpad_url']);
    $proxy_name = '';
    unset($_SESSION['proxy_name']);
    $proxy_port = '';
    unset($_SESSION['proxy_port']);
    $proxy_username = '';
    unset($_SESSION['proxy_username']);
    $proxy_password = '';
    unset($_SESSION['proxy_password']);
    $zone = '';
    unset($_SESSION['zone']);

    database_connect(IL_USER_DATABASE_PATH, 'users');

    $user_id_q = $dbHandle->quote($_SESSION['user_id']);

    // Load settings.
    $result = $dbHandle->query("SELECT setting_name as n, setting_value as v FROM settings"
            . " WHERE userID= '' OR userID=$user_id_q");

    while ($setting = $result->fetch(PDO::FETCH_ASSOC)) {

        ${$setting['n']} = $_SESSION[$setting['n']] = htmlspecialchars($setting['v']);
    }

    $dbHandle = null;
    ?>

    <form enctype="multipart/form-data" action="settings.php" method="GET" id="form-settings">
        <table cellspacing="0" style="width: 100%">
            <tr>
                <td class="details alternating_row" colspan=2>User-specific Settings:</td>
            </tr>
            <tr>
                <td class="details" style="white-space: nowrap;width: 30%">Items per page:</td>
                <td class="details">
                    <input type="radio" name="limit" value="5" <?php print htmlspecialchars($limit == '5' ? ' checked' : ''); ?>>5
                    <input type="radio" name="limit" value="10" <?php print htmlspecialchars($limit == '' ? ' checked' : ''); ?>>10
                    <input type="radio" name="limit" value="15" <?php print htmlspecialchars($limit == '15' ? ' checked' : ''); ?>>15
                    <input type="radio" name="limit" value="20" <?php print htmlspecialchars($limit == '20' ? ' checked' : ''); ?>>20
                    <input type="radio" name="limit" value="50" <?php print htmlspecialchars($limit == '50' ? ' checked' : ''); ?>>50
                    <input type="radio" name="limit" value="100" <?php print htmlspecialchars($limit == '100' ? ' checked' : ''); ?>>100
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Display type:</td>
                <td class="details">
                    <input type="radio" name="display" value="brief"<?php print htmlspecialchars($display == 'brief' ? ' checked' : ''); ?>>Title
                    <input type="radio" name="display" value="summary"<?php print htmlspecialchars($display == '' ? ' checked' : ''); ?>>Summary
                    <input type="radio" name="display" value="abstract"<?php print htmlspecialchars($display == 'abstract' ? ' checked' : ''); ?>>Abstract
                    <input type="radio" name="display" value="icons"<?php
                    print ($display == 'icons') ? ' checked' : '';
                    print !extension_loaded('gd') ? ' disabled' : '';

                    ?>>Icons
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Sorting:</td>
                <td class="details">
                    <input type="radio" name="orderby" value="id"<?php print htmlspecialchars($orderby == '' ? ' checked' : ''); ?>>Addition Date
                    <input type="radio" name="orderby" value="year"<?php print htmlspecialchars($orderby == 'year' ? ' checked' : ''); ?>>Publication Year
                    <input type="radio" name="orderby" value="journal"<?php print htmlspecialchars($orderby == 'journal' ? ' checked' : ''); ?>>Journal Name
                    <input type="radio" name="orderby" value="rating"<?php print htmlspecialchars($orderby == 'rating' ? ' checked' : ''); ?>>Rating
                    <input type="radio" name="orderby" value="title"<?php print htmlspecialchars($orderby == 'title' ? ' checked' : ''); ?>>Title
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Connect to these databases:</td>
                <td class="details">
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="pubmed" <?php print !empty($remove_pubmed) ? '' : 'checked'  ?>>PubMed<br>
                        <input type="checkbox" name="database_links[]" value="pmc" <?php print !empty($remove_pmc) ? '' : 'checked'  ?>>PubMed Central&nbsp;

                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="nasaads" <?php print !empty($remove_nasaads) ? '' : 'checked'  ?>>NASA ADS&nbsp;<br>
                        <input type="checkbox" name="database_links[]" value="arxiv" <?php print !empty($remove_arxiv) ? '' : 'checked'  ?>>arXiv<br>
                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="ieee" <?php print !empty($remove_ieee) ? '' : 'checked'  ?>>IEEE Xplore<br>
                        <input type="checkbox" name="database_links[]" value="highwire" <?php print !empty($remove_highwire) ? '' : 'checked'  ?>>HighWire Press
                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="springer" <?php print !empty($remove_springer) ? '' : 'checked'  ?>>Springer<br>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="details" style="white-space: nowrap">PDF viewer:</td>
                <td class="details">
                    <input type="radio" name="pdfviewer" value="internal" <?php print $pdfviewer == 'internal' ? 'checked' : ''  ?>>internal I, Librarian viewer
                    <input type="radio" name="pdfviewer" value="external" <?php print $pdfviewer == 'external' ? 'checked' : ''  ?>>web browser PDF plug-in
                </td>
            </tr>
            <?php
            if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

                ?>
                <tr>
                    <td class="details alternating_row" colspan=2>Global Settings:</td>
                </tr>
                <tr>
                    <td class="details" STYLE="white-space: nowrap">Force PDF watermarks:</td>
                    <td class="details">
                        <input type="radio" name="watermarks" value="nocopy" <?php print $watermarks == 'nocopy' ? 'checked' : ''  ?>>DO NOT COPY
                        <input type="radio" name="watermarks" value="confidential" <?php print $watermarks == 'confidential' ? 'checked' : ''  ?>>CONFIDENTIAL
                        <input type="radio" name="watermarks" value="" <?php print $watermarks == '' ? 'checked' : ''  ?>>no watermark
                    </td>
                </tr>
                <tr>
                    <td class="details" STYLE="white-space: nowrap">Sign in mode:</td>
                    <td class="details">
                        <input type="radio" name="signin_mode" value="dropdown" <?php print $signin_mode == '' ? 'checked' : ''  ?>>select box (more convenient)
                        <input type="radio" name="signin_mode" value="textinput" <?php print $signin_mode == 'textinput' ? 'checked' : ''  ?>>text input (more secure)
                    </td>
                </tr>
                <tr>
                    <td class="details" STYLE="white-space: nowrap">User registration:</td>
                    <td class="details">
                        <input type="checkbox" name="disallow_signup" value="1" <?php print !empty($disallow_signup) ? '' : 'checked'  ?>>Allow new users to sign up themselves
                    </td>
                </tr>
                <tr>
                    <td class="details default_permissions" STYLE="white-space: nowrap">Default permissions:
                    </td>
                    <td class="details default_permissions">
                        <input type="radio" name="default_permissions" value="A" <?php print $default_permissions == 'A' ? 'checked' : ''  ?>>
                        Super User - can add, delete and edit records, manages other users<br>
                        <input type="radio" name="default_permissions" value="U" <?php print $default_permissions == 'U' ? 'checked' : ''  ?>>
                        User - can add and edit records, cannot delete records<br>
                        <input type="radio" name="default_permissions" value="G" <?php print $default_permissions == 'G' ? 'checked' : ''  ?>>
                        Guest - cannot add, delete, or edit records
                    </td>
                </tr>
                <tr>
                    <td class="details" STYLE="white-space: nowrap">Rename custom fields:</td>
                    <td class="details">
                        Custom 1: <input type="text" name="custom1" value="<?php print !empty($custom1) ? $custom1 : ''  ?>"><br>
                        Custom 2: <input type="text" name="custom2" value="<?php print !empty($custom2) ? $custom2 : ''  ?>"><br>
                        Custom 3: <input type="text" name="custom3" value="<?php print !empty($custom3) ? $custom3 : ''  ?>"><br>
                        Custom 4: <input type="text" name="custom4" value="<?php print !empty($custom4) ? $custom4 : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="details" STYLE="white-space: nowrap">Time zone:</td>
                    <td class="details">
                        <select name="zone">
                            <option value="UTC">UTC</option>
                            <?php
                            $php_zones = timezone_identifiers_list();
                            array_pop($php_zones);
                            foreach ($php_zones as $php_zone) {
                                echo "<option" . (isset($zone) && $zone == $php_zone ? ' selected' : '') . ">$php_zone</option>" . PHP_EOL;
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php
            }
            if ($hosted == false) {
                if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

                    ?>
                    <tr>
                        <td class="details alternating_row" STYLE="width: 100%" COLSPAN=2>&nbsp;Proxy settings:</td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap" colspan=2><input type="radio" name="connection" value="direct"<?php print $connection == '' ? ' checked' : ''  ?>>direct connection to the Internet</td>
                    </tr>
                    <?php
                    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {

                        ?>
                        <tr>
                            <td class="details" style="white-space: nowrap" colspan=2><input type="radio" name="connection" value="autodetect"<?php print $connection == 'autodetect' ? ' checked' : ''  ?>>auto-detect proxy settings</td>
                        </tr>
                        <?php
                    }

                    ?>
                    <tr>
                        <td class="details" style="white-space: nowrap"><input type="radio" name="connection" value="url"<?php print $connection == 'url' ? ' checked' : ''  ?>>automatic proxy configuration URL:</td>
                        <td class="details">
                            <input type="text" style="width:90%" name="wpad_url" value="<?php print (isset($wpad_url)) ? $wpad_url : ''  ?>" placeholder="http://wpad.example.com/wpad.dat or proxy.pac">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap" colspan=2><input name="connection" value="proxy" type="radio"<?php print $connection == 'proxy' ? ' checked' : ''  ?>>manual HTTP proxy configuration:</td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy host:</td>
                        <td class="details">
                            <input type="text" name="proxy_name" value="<?php print !empty($proxy_name) ? $proxy_name : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy port:</td>
                        <td class="details">
                            <input type="text" name="proxy_port" size=4 value="<?php print !empty($proxy_port) ? $proxy_port : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy user name:</td>
                        <td class="details">
                            <input type="text" name="proxy_username" value="<?php print !empty($proxy_username) ? $proxy_username : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy password:</td>
                        <td class="details">
                            <input type="password" name="proxy_password" value="<?php print !empty($proxy_password) ? $proxy_password : ''  ?>">
                        </td>
                    </tr>
                    <?php
                } else {

                    ?>
                    <input type="hidden" name="signin_mode" value="<?php print !empty($signin_mode) ? $signin_mode : ''  ?>">
                    <input type="hidden" name="connection" value="<?php print !empty($connection) ? $connection : ''  ?>">
                    <input type="hidden" name="proxy_name" value="<?php print !empty($proxy_name) ? $proxy_name : ''  ?>">
                    <input type="hidden" name="proxy_port" value="<?php print !empty($proxy_port) ? $proxy_port : ''  ?>">
                    <input type="hidden" name="proxy_username" value="<?php print !empty($proxy_username) ? $proxy_username : ''  ?>">
                    <input type="hidden" name="proxy_password" value="<?php print !empty($proxy_password) ? $proxy_password : ''  ?>">
                    <?php
                }
            }

            ?>

        </table>
        <br>
        <input type="hidden" name="form" value="submitted">
        &nbsp;<input type="submit" value="Save">
    </form>
    <br><br>
    <?php
}
?>
<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    if (isset($_GET['form']) && $_GET['form'] == 'submitted') {

// SAVE USER SETTINGS

        if ($_GET['limit'] != 10)
            $new_setting['limit'] = intval($_GET['limit']);
        if ($_GET['display'] != 'summary' && ctype_alpha($_GET['display']))
            $new_setting['display'] = $_GET['display'];
        if ($_GET['orderby'] != 'id' && ctype_alpha($_GET['orderby']))
            $new_setting['orderby'] = $_GET['orderby'];

        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("pubmed", $_GET['database_links']))) {
            $new_setting['remove_pubmed'] = 1;
            $_SESSION['remove_pubmed'] = 1;
        } else {
            unset($_SESSION['remove_pubmed']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("pmc", $_GET['database_links']))) {
            $new_setting['remove_pmc'] = 1;
            $_SESSION['remove_pmc'] = 1;
        } else {
            unset($_SESSION['remove_pmc']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("nasaads", $_GET['database_links']))) {
            $new_setting['remove_nasaads'] = 1;
            $_SESSION['remove_nasaads'] = 1;
        } else {
            unset($_SESSION['remove_nasaads']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("arxiv", $_GET['database_links']))) {
            $new_setting['remove_arxiv'] = 1;
            $_SESSION['remove_arxiv'] = 1;
        } else {
            unset($_SESSION['remove_arxiv']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("highwire", $_GET['database_links']))) {
            $new_setting['remove_highwire'] = 1;
            $_SESSION['remove_highwire'] = 1;
        } else {
            unset($_SESSION['remove_highwire']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("ieee", $_GET['database_links']))) {
            $new_setting['remove_ieee'] = 1;
            $_SESSION['remove_ieee'] = 1;
        } else {
            unset($_SESSION['remove_ieee']);
        }
        if (!isset($_GET['database_links']) || (isset($_GET['database_links']) && !in_array("springer", $_GET['database_links']))) {
            $new_setting['remove_springer'] = 1;
            $_SESSION['remove_springer'] = 1;
        } else {
            unset($_SESSION['remove_springer']);
        }

        if (isset($_GET['pdfviewer']))
            $new_setting['pdfviewer'] = $_GET['pdfviewer'];

// SAVE GLOBAL SETTINGS, ADMIN LEVEL REQUIRED

        if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

            if (isset($_GET['connection']))
                $new_setting['global_connection'] = $_GET['connection'];
            if (isset($_GET['wpad_url']))
                $new_setting['global_wpad_url'] = $_GET['wpad_url'];
            if (isset($_GET['proxy_name']) && !empty($_GET['proxy_name']))
                $new_setting['global_proxy_name'] = $_GET['proxy_name'];
            if (isset($_GET['proxy_port']) && !empty($_GET['proxy_port']))
                $new_setting['global_proxy_port'] = $_GET['proxy_port'];
            if (isset($_GET['proxy_username']) && isset($new_setting['global_proxy_name']))
                $new_setting['global_proxy_username'] = $_GET['proxy_username'];
            if (isset($_GET['proxy_password']) && isset($new_setting['global_proxy_name']))
                $new_setting['global_proxy_password'] = $_GET['proxy_password'];

            if (!isset($_GET['disallow_signup']))
                $new_setting['global_disallow_signup'] = 1;

            if (isset($_GET['default_permissions'])) {
                $new_setting['global_default_permissions'] = $_GET['default_permissions'];
            } else {
                $new_setting['global_default_permissions'] = 'U';
            }

            if (!empty($_GET['watermarks']))
                $new_setting['global_watermarks'] = $_GET['watermarks'];

            if ($_GET['signin_mode'] == 'textinput')
                $new_setting['global_signin_mode'] = $_GET['signin_mode'];
        }

        $_SESSION['limit'] = intval($_GET['limit']);
        $_SESSION['display'] = $_GET['display'];
        $_SESSION['orderby'] = $_GET['orderby'];
        $_SESSION['pdfviewer'] = $_GET['pdfviewer'];

        if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {
            if ($_GET['connection'] == 'direct') {
                $_SESSION['proxy_name'] = null;
                $_SESSION['proxy_port'] = null;
                $_SESSION['proxy_username'] = null;
                $_SESSION['proxy_password'] = null;
            } else {
                $_SESSION['proxy_name'] = $_GET['proxy_name'];
                $_SESSION['proxy_port'] = $_GET['proxy_port'];
                $_SESSION['proxy_username'] = $_GET['proxy_username'];
                $_SESSION['proxy_password'] = $_GET['proxy_password'];
            }
        }

        database_connect($usersdatabase_path, 'users');

        $dbHandle->beginTransaction();
        $user_query = $dbHandle->quote($_SESSION['user_id']);
        $dbHandle->exec("DELETE FROM settings WHERE userID=$user_query AND setting_name LIKE 'settings_%'");

        if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {
            $dbHandle->exec("DELETE FROM settings WHERE setting_name LIKE 'settings_global_%'");
        }

        if (!empty($new_setting)) {

            while (list($key, $value) = each($new_setting)) {

                if (strpos($key, 'global_') === 0) {
                    $key = $dbHandle->quote($key);
                    $value = $dbHandle->quote($value);
                    $dbHandle->exec("INSERT INTO settings (userID, setting_name, setting_value) VALUES ('', 'settings_' || $key, $value)");
                } else {
                    $key = $dbHandle->quote($key);
                    $value = $dbHandle->quote($value);
                    $dbHandle->exec("INSERT INTO settings (userID, setting_name, setting_value) VALUES ($user_query, 'settings_' || $key, $value)");
                }
            }
        }

        $dbHandle->commit();
        $dbHandle = null;
    }

//DEFAULTS
    $limit = 10;
    $display = 'summary';
    $orderby = 'id';
    $signin_mode = 'dropdown';
    $connection = '';
    $pdfviewer = 'internal';
    $default_permissions = 'U';
    $watermarks = '';
    $_SESSION['watermarks'] = '';

    database_connect($usersdatabase_path, 'users');
    $user_query = $dbHandle->quote($_SESSION['user_id']);
    $result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=$user_query");
    $result2 = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE setting_name LIKE 'settings_global_%'");

    while ($custom_settings = $result->fetch(PDO::FETCH_ASSOC)) {

        ${$custom_settings['setting_name']} = $custom_settings['setting_value'];
    }

    while ($custom_settings = $result2->fetch(PDO::FETCH_ASSOC)) {

        ${$custom_settings['setting_name']} = $custom_settings['setting_value'];
    }

    $dbHandle = null;

    if (isset($settings_limit))
        $limit = $settings_limit;
    if (isset($settings_display))
        $display = $settings_display;
    if (isset($settings_orderby))
        $orderby = $settings_orderby;
    if (isset($settings_global_signin_mode))
        $signin_mode = $settings_global_signin_mode;
    if (isset($settings_remove_pubmed))
        $remove_pubmed = $settings_remove_pubmed;
    if (isset($settings_remove_pmc))
        $remove_pmc = $settings_remove_pmc;
    if (isset($settings_remove_nasaads))
        $remove_nasaads = $settings_remove_nasaads;
    if (isset($settings_remove_arxiv))
        $remove_arxiv = $settings_remove_arxiv;
    if (isset($settings_remove_jstor))
        $remove_jstor = $settings_remove_jstor;
    if (isset($settings_remove_highwire))
        $remove_highwire = $settings_remove_highwire;
    if (isset($settings_remove_ieee))
        $remove_ieee = $settings_remove_ieee;
    if (isset($settings_remove_springer))
        $remove_springer = $settings_remove_springer;
    if (isset($settings_pdfviewer))
        $pdfviewer = $settings_pdfviewer;

    if (isset($settings_global_disallow_signup))
        $disallow_signup = $settings_global_disallow_signup;

    if (isset($settings_global_default_permissions))
        $default_permissions = $settings_global_default_permissions;

    if (isset($settings_global_connection)) {
        $connection = $settings_global_connection;
        if ($connection == 'autodetect' || $connection == 'url') {
            $_SESSION['connection'] = $connection;
        } else {
            if (isset($_SESSION['connection']))
                unset($_SESSION['connection']);
        }
    }

    if (isset($settings_global_wpad_url)) {
        $wpad_url = $settings_global_wpad_url;
        if ($connection == 'url')
            $_SESSION['wpad_url'] = $settings_global_wpad_url;
    }

    if (isset($settings_global_proxy_name)) {
        $proxy_name = $settings_global_proxy_name;
        if ($connection == 'proxy')
            $_SESSION['proxy_name'] = $settings_global_proxy_name;
    }

    if (isset($settings_global_proxy_port)) {
        $proxy_port = $settings_global_proxy_port;
        if ($connection == 'proxy')
            $_SESSION['proxy_port'] = $settings_global_proxy_port;
    }

    if (isset($settings_global_proxy_username)) {
        $proxy_username = $settings_global_proxy_username;
        if ($connection == 'proxy')
            $_SESSION['proxy_username'] = $settings_global_proxy_username;
    }

    if (isset($settings_global_proxy_password)) {
        $proxy_password = $settings_global_proxy_password;
        if ($connection == 'proxy')
            $_SESSION['proxy_password'] = $settings_global_proxy_password;
    }

    if (isset($settings_global_watermarks)) {
        $watermarks = $settings_global_watermarks;
        $_SESSION['watermarks'] = $settings_global_watermarks;
    }
    ?>

    <form enctype="multipart/form-data" action="settings.php" method="GET" id="form-settings">
        <table cellspacing="0" style="width: 100%">
            <tr>
                <td class="details alternating_row" colspan=2>User-specific Settings:</td>
            </tr>
            <tr>
                <td class="details" style="white-space: nowrap;width: 30%">Items per page:</td>
                <td class="details">
                    <input type="radio" name="limit" value="5" <?php print htmlspecialchars((isset($limit) && $limit == '5') ? ' checked' : ''); ?>>5
                    <input type="radio" name="limit" value="10" <?php print htmlspecialchars((isset($limit) && $limit == '10') ? ' checked' : ''); ?>>10
                    <input type="radio" name="limit" value="15" <?php print htmlspecialchars((isset($limit) && $limit == '15') ? ' checked' : ''); ?>>15
                    <input type="radio" name="limit" value="20" <?php print htmlspecialchars((isset($limit) && $limit == '20') ? ' checked' : ''); ?>>20
                    <input type="radio" name="limit" value="50" <?php print htmlspecialchars((isset($limit) && $limit == '50') ? ' checked' : ''); ?>>50
                    <input type="radio" name="limit" value="100" <?php print htmlspecialchars((isset($limit) && $limit == '100') ? ' checked' : ''); ?>>100
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Display type:</td>
                <td class="details">
                    <input type="radio" name="display" value="brief"<?php print htmlspecialchars((isset($display) && $display == 'brief') ? ' checked' : ''); ?>>Title
                    <input type="radio" name="display" value="summary"<?php print htmlspecialchars((isset($display) && $display == 'summary') ? ' checked' : ''); ?>>Summary
                    <input type="radio" name="display" value="abstract"<?php print htmlspecialchars((isset($display) && $display == 'abstract') ? ' checked' : ''); ?>>Abstract
                    <input type="radio" name="display" value="icons"<?php
                    print (isset($display) && $display == 'icons') ? ' checked' : '';
                    print !extension_loaded('gd') ? ' disabled' : '';
                    ?>>Icons
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Sorting:</td>
                <td class="details">
                    <input type="radio" name="orderby" value="id"<?php print htmlspecialchars((isset($orderby) && $orderby == 'id') ? ' checked' : ''); ?>>Addition Date
                    <input type="radio" name="orderby" value="year"<?php print htmlspecialchars((isset($orderby) && $orderby == 'year') ? ' checked' : ''); ?>>Publication Year
                    <input type="radio" name="orderby" value="journal"<?php print htmlspecialchars((isset($orderby) && $orderby == 'journal') ? ' checked' : ''); ?>>Journal Name
                    <input type="radio" name="orderby" value="rating"<?php print htmlspecialchars((isset($orderby) && $orderby == 'rating') ? ' checked' : ''); ?>>Rating
                    <input type="radio" name="orderby" value="title"<?php print htmlspecialchars((isset($orderby) && $orderby == 'title') ? ' checked' : ''); ?>>Title
                </td>
            </tr>
            <tr>
                <td class="details" STYLE="white-space: nowrap">Connect to these databases:</td>
                <td class="details">
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="pubmed" <?php print (isset($remove_pubmed)) ? '' : 'checked'  ?>>PubMed<br>
                        <input type="checkbox" name="database_links[]" value="pmc" <?php print (isset($remove_pmc)) ? '' : 'checked'  ?>>PubMed Central&nbsp;

                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="nasaads" <?php print (isset($remove_nasaads)) ? '' : 'checked'  ?>>NASA ADS&nbsp;<br>
                        <input type="checkbox" name="database_links[]" value="arxiv" <?php print (isset($remove_arxiv)) ? '' : 'checked'  ?>>arXiv<br>
                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="ieee" <?php print (isset($remove_ieee)) ? '' : 'checked'  ?>>IEEE Xplore<br>
                        <input type="checkbox" name="database_links[]" value="highwire" <?php print (isset($remove_highwire)) ? '' : 'checked'  ?>>HighWire Press
                    </div>
                    <div style="float:left">
                        <input type="checkbox" name="database_links[]" value="springer" <?php print (isset($remove_springer)) ? '' : 'checked'  ?>>Springer<br>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="details" style="white-space: nowrap">PDF viewer:</td>
                <td class="details">
                    <input type="radio" name="pdfviewer" value="internal" <?php print ($pdfviewer == 'internal') ? 'checked' : ''  ?>>internal I, Librarian viewer
                    <input type="radio" name="pdfviewer" value="external" <?php print ($pdfviewer == 'external') ? 'checked' : ''  ?>>web browser PDF plug-in
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
                        <input type="radio" name="watermarks" value="nocopy" <?php print ($watermarks == 'nocopy') ? 'checked' : ''  ?>>DO NOT COPY
                        <input type="radio" name="watermarks" value="confidential" <?php print ($watermarks == 'confidential') ? 'checked' : ''  ?>>CONFIDENTIAL
                        <input type="radio" name="watermarks" value="" <?php print ($watermarks == '') ? 'checked' : ''  ?>>no watermark
                    </td>
                </tr>

                <tr>
                    <td class="details" STYLE="white-space: nowrap">Sign in mode:</td>
                    <td class="details">
                        <input type="radio" name="signin_mode" value="dropdown" <?php print ($signin_mode == 'dropdown') ? 'checked' : ''  ?>>select box (more convenient)
                        <input type="radio" name="signin_mode" value="textinput" <?php print ($signin_mode == 'textinput') ? 'checked' : ''  ?>>text input (more secure)
                    </td>
                </tr>

                <tr>
                    <td class="details" STYLE="white-space: nowrap">User registration:</td>
                    <td class="details">
                        <input type="checkbox" name="disallow_signup" value="pubmed" <?php print (isset($disallow_signup)) ? '' : 'checked'  ?>>Allow new users to sign up themselves
                    </td>
                </tr>
                <tr>
                    <td class="details default_permissions" STYLE="white-space: nowrap">Default permissions:
                    </td>
                    <td class="details default_permissions">
                        <input type="radio" name="default_permissions" value="A" <?php print ($default_permissions == 'A') ? 'checked' : ''  ?>>
                        Super User - can add, delete and edit records, manages other users<br>
                        <input type="radio" name="default_permissions" value="U" <?php print ($default_permissions == 'U') ? 'checked' : ''  ?>>
                        User - can add and edit records, cannot delete records<br>
                        <input type="radio" name="default_permissions" value="G" <?php print ($default_permissions == 'G') ? 'checked' : ''  ?>>
                        Guest - cannot add, delete, or edit records
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
                        <td class="details" style="white-space: nowrap" colspan=2><input type="radio" name="connection" value="direct"<?php print ($connection == 'direct') ? ' checked' : ''  ?>>direct connection to the Internet</td>
                    </tr>
                    <?php
                    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                        ?>
                        <tr>
                            <td class="details" style="white-space: nowrap" colspan=2><input type="radio" name="connection" value="autodetect"<?php print ($connection == 'autodetect') ? ' checked' : ''  ?>>auto-detect proxy settings</td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td class="details" style="white-space: nowrap"><input type="radio" name="connection" value="url"<?php print ($connection == 'url') ? ' checked' : ''  ?>>automatic proxy configuration URL:</td>
                        <td class="details">
                            <input type="text" style="width:90%" name="wpad_url" value="<?php print (isset($wpad_url)) ? $wpad_url : ''  ?>" placeholder="http://wpad.example.com/wpad.dat or proxy.pac">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap" colspan=2><input name="connection" value="proxy" type="radio"<?php print ($connection == 'proxy') ? ' checked' : ''  ?>>manual HTTP proxy configuration:</td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy host:</td>
                        <td class="details">
                            <input type="text" name="proxy_name" value="<?php print (isset($proxy_name)) ? $proxy_name : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy port:</td>
                        <td class="details">
                            <input type="text" name="proxy_port" size=4 value="<?php print (isset($proxy_port)) ? $proxy_port : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy user name:</td>
                        <td class="details">
                            <input type="text" name="proxy_username" value="<?php print (isset($proxy_username)) ? $proxy_username : ''  ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="details" style="white-space: nowrap">&nbsp;Proxy password:</td>
                        <td class="details">
                            <input type="text" name="proxy_password" value="<?php print (isset($proxy_password)) ? $proxy_password : ''  ?>">
                        </td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <input type="hidden" name="signin_mode" value="<?php print (isset($signin_mode)) ? $signin_mode : ''  ?>">
                    <input type="hidden" name="connection" value="<?php print (isset($connection)) ? $connection : ''  ?>">
                    <input type="hidden" name="proxy_name" value="<?php print (isset($proxy_name)) ? $proxy_name : ''  ?>">
                    <input type="hidden" name="proxy_port" value="<?php print (isset($proxy_port)) ? $proxy_port : ''  ?>">
                    <input type="hidden" name="proxy_username" value="<?php print (isset($proxy_username)) ? $proxy_username : ''  ?>">
                    <input type="hidden" name="proxy_password" value="<?php print (isset($proxy_password)) ? $proxy_password : ''  ?>">
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
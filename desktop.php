<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect(IL_DATABASE_PATH, 'library');

$id_query = $dbHandle->quote($_SESSION['user_id']);

$result = $dbHandle->query("SELECT DISTINCT projects.projectID as projectID,project,projects.userID AS creator, projects.active
        FROM projects LEFT JOIN projectsusers ON projects.projectID=projectsusers.projectID
        WHERE projects.userID=$id_query OR projectsusers.userID=$id_query ORDER BY project COLLATE NOCASE ASC");
$projects = $result->fetchAll(PDO::FETCH_ASSOC);
$firstproject = '';
if (!empty($projects))
    $firstproject = $projects[0]['projectID'];

$dbHandle->exec("ATTACH DATABASE '" . IL_DATABASE_PATH . DIRECTORY_SEPARATOR . "users.sq3' AS usersdatabase");
$result2 = $dbHandle->query("SELECT userID,username FROM users ORDER BY username COLLATE NOCASE ASC");
$users = $result2->fetchAll(PDO::FETCH_ASSOC);
$dbHandle->exec("DETACH DATABASE usersdatabase");
$number_of_users = count($users);

?>
<div class="leftindex" id="leftindex-left" style="float:left;width:233px;height:100%;overflow:scroll">
    <form id="quicksearch" action="search.php" method="GET" target="rightpanel">
        <table class="ui-state-highlight" style="width:100%;border-bottom:1px solid rgba(0,0,0,0.15)">
            <tr>
                <td class="quicksearch">
                    <input type="text" name="anywhere" placeholder="Quick Search" style="width:99%"
                           value="<?php print isset($_SESSION['session_anywhere']) ? htmlspecialchars($_SESSION['session_anywhere']) : ''; ?>">
                    <input type="text" name="fulltext" placeholder="PDF Search" style="width:99%;display:none"
                           value="<?php print isset($_SESSION['session_fulltext']) ? htmlspecialchars($_SESSION['session_fulltext']) : ''; ?>">
                    <input type="text" name="pdfnotes" placeholder="PDF Notes Search" style="width:99%;display:none" 
                           value="<?php print isset($_SESSION['session_pdfnotes']) ? htmlspecialchars($_SESSION['session_pdfnotes']) : ''; ?>">
                    <input type="text" name="notes" placeholder="Rich-Text Notes Search" style="width:99%;display:none"
                           value="<?php print isset($_SESSION['session_notes']) ? htmlspecialchars($_SESSION['session_notes']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="quicksearch">
                    <table style="float:left;margin-top:0.2em;margin-left:2px">
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="AND" style="display:none"
                                <?php
                                if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'AND') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'AND') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> and&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="OR" style="display:none"
                                <?php
                                if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'OR') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'OR') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> or&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="PHRASE" style="display:none"
                                <?php
                                if (!isset($_SESSION['session_anywhere_separator']) || (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'PHRASE')) echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (!isset($_SESSION['session_anywhere_separator']) || (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'PHRASE')) {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> phrase
                            </td>
                        </tr>
                    </table>
                    <table style="display:none;float:left;margin-top:0.2em;margin-left:2px">
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="fulltext_separator" value="AND" style="display:none"
                                <?php
                                if (!isset($_SESSION['session_fulltext_separator']) || $_SESSION['session_fulltext_separator'] == 'AND') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (!isset($_SESSION['session_fulltext_separator']) || $_SESSION['session_fulltext_separator'] == 'AND') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> and&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="fulltext_separator" value="OR" style="display:none"
                                <?php
                                if (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'OR') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'OR') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> or&nbsp;&nbsp;
                            </td>
                        </tr>
                    </table>
                    <table style="display:none;float:left;margin-top:0.2em;margin-left:2px">
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="pdfnotes_separator" value="AND" style="display:none"
                                <?php
                                if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'AND') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'AND') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> and&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="pdfnotes_separator" value="OR" style="display:none"
                                <?php
                                if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'OR') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'OR') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> or&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="pdfnotes_separator" value="PHRASE" style="display:none"
                                <?php
                                if (!isset($_SESSION['session_pdfnotes_separator']) || (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'PHRASE')) echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (!isset($_SESSION['session_pdfnotes_separator']) || (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'PHRASE')) {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> phrase
                            </td>
                        </tr>
                    </table>
                    <table style="display:none;float:left;margin-top:0.2em;margin-left:2px">
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="notes_separator" value="AND" style="display:none"
                                <?php
                                if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'AND') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'AND') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> and&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="notes_separator" value="OR" style="display:none"
                                <?php
                                if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'OR') echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'OR') {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> or&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="notes_separator" value="PHRASE" style="display:none"
                                <?php
                                if (!isset($_SESSION['session_notes_separator']) || (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'PHRASE')) echo 'checked';
                                ?>
                                >
                                <i class="fa
                                <?php
                                if (!isset($_SESSION['session_notes_separator']) || (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'PHRASE')) {
                                    echo 'fa-circle';
                                } else {
                                    echo 'fa-circle-o';
                                }
                                ?>
                                "></i> phrase
                            </td>
                        </tr>
                    </table>
                    <button id="search" style="width:34px;height:24px" title="Search"><i class="fa fa-search"></i></button><button
                        id="clear" style="width:24px;height:24px" title="Clear"><i class="fa fa-trash-o"></i></button>
                </td>
            </tr>
        </table>
        <input type="hidden" name="select" value="desk">
        <input type="hidden" name="project" value="<?php print htmlspecialchars($firstproject); ?>">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="quick">
        <input type="hidden" name="rating[]" value="1">
        <input type="hidden" name="rating[]" value="2">
        <input type="hidden" name="rating[]" value="3">
    </form>
    <div id="search-menu" style="width:100%">
        <div class="tabclicked" title="Search metadata"><i class="fa fa-list"></i></div>
        <div class="" title="Search PDFs"><i class="fa fa-file-pdf-o"></i></div>
        <div class="" title="Search PDF notes"><i class="fa fa-comment"></i></div>
        <div class="" title="Search rich-text notes"><i class="fa fa-pencil"></i></div>
    </div>
    <div style="clear:both"></div>
    <div class="ui-state-highlight">
        <div id="advancedsearchbutton" style="width:50%;float:left;padding:4px 0">
            Advanced <i class="fa fa-search"></i>
        </div>
        <div id="expertsearchbutton" style="width:50%;float:left;padding:4px 0">
            Expert <i class="fa fa-search"></i>
        </div>
        <div style="clear:both"></div>
    </div>
    <br>
    <form action="ajaxdesk.php" method="GET">
        <input type="hidden" name="create" value="create">
        <input type="text" size="10" name="project" value="" style="width:125px;margin-left:3px" placeholder="Create project">
        <button id="createproject">Create</button>
    </form>
    <h4 style="margin-left:0.5em">Active</h4>
    <?php
    foreach ($projects as $project) {
        
        if ($project['active'] === '1') {
        ?>
        <table cellspacing=0 width="210px" style="margin:6px 0px" class="projectheader">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right">
                    <div style="width:200px;white-space:nowrap;overflow:hidden"><?php print htmlspecialchars($project['project']) ?></div>
                </td>
            </tr>
        </table>
        <div class="projectcontainer" id="project-<?php print intval($project['projectID']); ?>" style="display: none;width:200px;margin-left: 10px">
            <table style="width:98%">
                <tr>
                    <td class="select_span desk-active" style="width:50%">
                        <input type="checkbox" style="display:none" <?php echo (isset($project['active']) && $project['active'] == '1') ? 'checked' : '' ?>>
                        <i class="fa fa-<?php echo (isset($project['active']) && $project['active'] == '1') ? 'check-square' : 'square-o' ?>"></i>
                        active
                    </td>
                    <td style="text-align:right">
                        <a href="discussion.php?project=<?php print htmlspecialchars(urlencode($project['projectID'])) ?>" target="_blank">
                            <i class="fa fa-comments-o"></i> Discussion
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right">
                        <a href="projectnotes.php?projectID=<?php print htmlspecialchars(urlencode($project['projectID'])) ?>" target="_blank">
                            <i class="fa fa-pencil"></i> Notes
                        </a>
                    </td>
                </tr>
            </table>
            <b>Creator</b> &bull; <?php print htmlspecialchars(get_username($dbHandle, $project['creator'])) ?>
            <br>
            <?php
            if ($number_of_users > 1) {

                $collaborators = $dbHandle->query("SELECT userID FROM projectsusers WHERE projectID=" . intval($project['projectID']));
                $collaborators = $collaborators->fetchAll(PDO::FETCH_COLUMN);
            }

            if ($_SESSION['user_id'] == $project['creator']) {
                ?>
                <table cellspacing=0>
                    <tr>
                        <td>Users:</td>
                        <td>Collaborators:</td>
                    </tr>
                    <tr>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="adduser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (!in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . htmlspecialchars($user['username']) . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="removeuser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . htmlspecialchars($user['username']) . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding-top:2px">
                            <span class="ui-state-highlight ui-corner-bottom adduser">
                                &nbsp;Add <i class="fa fa-angle-right"></i>&nbsp;
                            </span>
                        </td>
                        <td style="text-align:center;padding-top:2px">
                            <span class="ui-state-highlight ui-corner-bottom removeuser">
                                &nbsp;<i class="fa fa-angle-left"></i> Remove&nbsp;
                            </span>
                        </td>
                    </tr>
                </table>
                <br>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="rename" value="Rename">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="text" name="project" value="<?php print htmlspecialchars($project['project']) ?>" style="width:47%">
                    <button class="renamebutton" style="margin:0;width:49%">Rename</button><br>
                </form>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="delete" value="">
                    <button class="deletebutton" style="margin:2px 0px;width:49%"><i class="fa fa-trash-o"></i> Delete</button>
                </form>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="empty" value="">
                    <button class="emptybutton" style="margin:2px 0px;width:49%"><i class="fa fa-external-link"></i> Empty</button>
                </form>
                <br>
                <?php
            }
            ?>
        </div>
        <?php
        }
    } //while
    echo '<h4 style="margin-left:0.5em">Inactive</h4>';
    foreach ($projects as $project) {
        
        if ($project['active'] === '0') {
        ?>
        <table cellspacing=0 width="210px" style="margin:6px 0px" class="projectheader">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right">
                    <div style="width:200px;white-space:nowrap;overflow:hidden"><?php print htmlspecialchars($project['project']) ?></div>
                </td>
            </tr>
        </table>
        <div class="projectcontainer" id="project-<?php print intval($project['projectID']); ?>" style="display: none;width:200px;margin-left: 10px">
            <table style="width:98%">
                <tr>
                    <td class="select_span desk-active" style="width:50%">
                        <input type="checkbox" style="display:none" <?php echo (isset($project['active']) && $project['active'] == '1') ? 'checked' : '' ?>>
                        <i class="fa fa-<?php echo (isset($project['active']) && $project['active'] == '1') ? 'check-square' : 'square-o' ?>"></i>
                        active
                    </td>
                    <td style="text-align:right">
                        <a href="discussion.php?project=<?php print htmlspecialchars(urlencode($project['projectID'])) ?>" target="_blank">
                            <i class="fa fa-comments-o"></i> Discussion
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align:right">
                        <a href="projectnotes.php?projectID=<?php print htmlspecialchars(urlencode($project['projectID'])) ?>" target="_blank">
                            <i class="fa fa-pencil"></i> Notes
                        </a>
                    </td>
                </tr>
            </table>
            <b>Creator</b> &bull; <?php print htmlspecialchars(get_username($dbHandle, $project['creator'])) ?>
            <br>
            <?php
            if ($number_of_users > 1) {

                $collaborators = $dbHandle->query("SELECT userID FROM projectsusers WHERE projectID=" . intval($project['projectID']));
                $collaborators = $collaborators->fetchAll(PDO::FETCH_COLUMN);
            }

            if ($_SESSION['user_id'] == $project['creator']) {
                ?>
                <table cellspacing=0>
                    <tr>
                        <td>Users:</td>
                        <td>Collaborators:</td>
                    </tr>
                    <tr>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="adduser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (!in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . htmlspecialchars($user['username']) . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="removeuser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . htmlspecialchars($user['username']) . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding-top:2px">
                            <span class="ui-state-highlight ui-corner-bottom adduser">
                                &nbsp;Add <i class="fa fa-angle-right"></i>&nbsp;
                            </span>
                        </td>
                        <td style="text-align:center;padding-top:2px">
                            <span class="ui-state-highlight ui-corner-bottom removeuser">
                                &nbsp;<i class="fa fa-angle-left"></i> Remove&nbsp;
                            </span>
                        </td>
                    </tr>
                </table>
                <br>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="rename" value="Rename">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="text" name="project" value="<?php print htmlspecialchars($project['project']) ?>" style="width:47%">
                    <button class="renamebutton" style="margin:0;width:49%">Rename</button><br>
                </form>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="delete" value="">
                    <button class="deletebutton" style="margin:2px 0px;width:49%"><i class="fa fa-trash-o"></i> Delete</button>
                </form>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="empty" value="">
                    <button class="emptybutton" style="margin:2px 0px;width:49%"><i class="fa fa-external-link"></i> Empty</button>
                </form>
                <br>
                <?php
            }
            ?>
        </div>
        <?php
        }
    } //while
    $dbHandle = null;
    ?>
</div>
<div class="alternating_row middle-panel"
     style="float:left;width:6px;height:100%;overflow:hidden;border-right:1px solid #b5b6b8;cursor:pointer">
    <i class="fa fa-caret-left" style="position:relative;left:1px;top:46%"></i>
</div>
<div style="width:auto;height:100%;overflow:auto" id="right-panel"></div>
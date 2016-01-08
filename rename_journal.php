<?php
include_once 'data.php';

if (isset($_SESSION['auth']) && isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    database_connect(IL_DATABASE_PATH, 'library');

    if (!empty($_GET['details'])) {

        $dbHandle->sqliteCreateFunction('levenshtein', 'sqlite_levenshtein', 2);

        if (!empty($_GET['journal'])) {
            $journal_query = $dbHandle->quote($_GET['journal']);
            $result = $dbHandle->query("SELECT journal,count(*) FROM library WHERE levenshtein(upper(journal), upper($journal_query)) < 4 GROUP BY journal");

            print '<b>Possible variants:</b>';

            while ($jour = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($jour['journal'])) {
                    print '<br><button class="rename-journal-button" style="margin:1px"><i class="fa fa-pencil"></i></button> ';
                    print '<span>' . htmlspecialchars($jour['journal']) . '</span> (' . $jour['count(*)'] . ')';
                }
            }

            $result = null;

            $result = $dbHandle->query("SELECT secondary_title,count(*) FROM library WHERE journal=$journal_query GROUP BY secondary_title");

            print '<br><b>Associated secondary titles:</b>';

            while ($jour = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($jour['secondary_title'])) {
                    print '<br><button class="rename-secondary-title-button" style="margin:1px"><i class="fa fa-pencil"></i></button> ';
                    print '<span>' . htmlspecialchars($jour['secondary_title']) . '</span> (' . $jour['count(*)'] . ')';
                }
            }
        }

        if (!empty($_GET['secondary_title'])) {
            $journal_query = $dbHandle->quote($_GET['secondary_title']);
            $result = $dbHandle->query("SELECT secondary_title,count(*) FROM library WHERE levenshtein(upper(secondary_title), upper($journal_query)) < 4 GROUP BY secondary_title");

            print '<b>Possible variants:</b>';

            while ($jour = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($jour['secondary_title'])) {
                    print '<br><button class="rename-secondary-title-button" style="margin:1px"><i class="fa fa-pencil"></i></button> ';
                    print '<span>' . htmlspecialchars($jour['secondary_title']) . '</span> (' . $jour['count(*)'] . ')';
                }
            }

            $result = null;

            $result = $dbHandle->query("SELECT journal,count(*) FROM library WHERE secondary_title=$journal_query GROUP BY journal");

            print '<br><b>Associated journal abbreviations:</b>';

            while ($jour = $result->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($jour['journal'])) {
                    print '<br><button class="rename-journal-button" style="margin:1px"><i class="fa fa-pencil"></i></button> ';
                    print '<span>' . htmlspecialchars($jour['journal']) . '</span> (' . $jour['count(*)'] . ')';
                }
            }
        }

        die();
    }

    if (!empty($_GET['change_journal'])) {
        if (!empty($_GET['new_journal']) && !empty($_GET['old_journal'])) {
            if (!empty($_GET['parent_secondary_title'])) {
                $parent_query = $dbHandle->quote($_GET['parent_secondary_title']);
                $parent_query = 'secondary_title=' . $parent_query;
            } elseif (!empty($_GET['parent_journal'])) {
                $parent_query = $dbHandle->quote($_GET['parent_journal']);
                $parent_query = 'journal=' . $parent_query;
            }
            $old_journal_query = $dbHandle->quote($_GET['old_journal']);
            $new_journal_query = $dbHandle->quote($_GET['new_journal']);

            $dbHandle->exec("UPDATE library SET journal=" . $new_journal_query
                    . " WHERE journal=" . $old_journal_query . " AND " . $parent_query);
        }
        if (!empty($_GET['new_secondary_title']) && !empty($_GET['old_secondary_title'])) {
            if (!empty($_GET['parent_secondary_title'])) {
                $parent_query = $dbHandle->quote($_GET['parent_secondary_title']);
                $parent_query = 'secondary_title=' . $parent_query;
            } elseif (!empty($_GET['parent_journal'])) {
                $parent_query = $dbHandle->quote($_GET['parent_journal']);
                $parent_query = 'journal=' . $parent_query;
            }
            $old_secondary_title_query = $dbHandle->quote($_GET['old_secondary_title']);
            $new_secondary_title_query = $dbHandle->quote($_GET['new_secondary_title']);
            $dbHandle->exec("UPDATE library SET secondary_title=" . $new_secondary_title_query
                    . " WHERE secondary_title=" . $old_secondary_title_query . " AND " . $parent_query);
        }
        die();
    }

    $result = $dbHandle->query("SELECT DISTINCT journal,secondary_title FROM library");

    $dbHandle = null;

    $journal = array();
    $secondary_title = array();

    $result->bindColumn(1, $jour);
    $result->bindColumn(2, $sec);

    while ($result->fetch(PDO::FETCH_BOUND)) {

        if (!empty($jour))
            $journal[] = $jour;
        if (!empty($sec))
            $secondary_title[] = $sec;
    }

    $journal = array_unique($journal);
    natcasesort($journal);
    $secondary_title = array_unique($secondary_title);
    natcasesort($secondary_title);
    ?>
    <form action="rename_journal.php" method="GET" id="rename-journal-form">
        <table style="width:100%">
            <tr>
                <td class="details alternating_row"><b>Consolidate journal names. Click on a journal name to see options.</b></td>
            </tr>
        </table>
        <div id="edit-journal-list" style="width:48%;float:left;padding:4px">
            <b>Journal abbreviations:</b>
            <?php
            while (list($key, $value) = each($journal)) {
                print PHP_EOL . '<div class="journal-name">' . htmlspecialchars($value) . '</div>';
            }
            reset($journal);
            ?>
        </div>
        <div id="edit-secondary-title-list" style="width:48%;float:left;padding:4px">
            <b>Secondary titles (full journal names):</b>
            <?php
            while (list($key, $value) = each($secondary_title)) {
                print PHP_EOL . '<div class="journal-name">' . htmlspecialchars($value) . '</div>';
            }
            reset($secondary_title);
            ?>
        </div>
    </form>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>
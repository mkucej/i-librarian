<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_SESSION['auth'])) {

    database_connect(IL_DATABASE_PATH, 'discussions');

    if (!empty($_POST['newmessage']) && !empty($_POST['file'])) {

        $stmt = $dbHandle->prepare("INSERT INTO filediscussion (fileID, user, timestamp, message) VALUES (:fileID, :user, :timestamp, :message)");

        $stmt->bindParam(':fileID', $fileID);
        $stmt->bindParam(':user', $user);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':message', $message);

        $fileID = $_POST['file'];
        $user = $_SESSION['user'];
        $timestamp = time();
        $message = $_POST['newmessage'];

        $insert = $stmt->execute();
        $dbHandle = null;
        if ($insert)
            die('OK');
    }

    if (isset($_GET['delete']) && !empty($_GET['file'])) {

        $delete = $dbHandle->exec("DELETE FROM filediscussion WHERE fileID=" . intval($_GET['file']));
        $dbHandle = null;
        die('OK');
    }

    if (isset($_GET['read']) && !empty($_GET['file'])) {

        $result = $dbHandle->query("SELECT * FROM filediscussion WHERE fileID=" . intval($_GET['file']) . " ORDER BY id DESC LIMIT 100");
        $dbHandle = null;

        $ismessage = false;

        while ($message = $result->fetch(PDO::FETCH_ASSOC)) {

            $ismessage = true;

            $message['user'] = htmlspecialchars($message['user']);
            $message['message'] = htmlspecialchars($message['message']);
            $message['message'] = preg_replace('/(https?\:\/\/\S+)/i', '<a href="\\1" target="_blank">\\1</a>', $message['message']);
            $message['message'] = nl2br($message['message']);

            print "<div class=\"alternating_row\" style=\"padding:2px\"><b>" . date("M j, Y, h:i:s A", $message['timestamp']) . ", " . $message['user'] . ":</b></div>";
            print "<div style=\"padding:2px 2px 10px 2px\">$message[message]</div>" . PHP_EOL;
        }

        if (!$ismessage)
            die('No messages.');

        die();
    }
    ?>
    <table cellspacing="0" style="width:100%;height:100%;margin-top: 0px">
        <tr>
            <td class="alternating_row" style="width: 190px;padding: 5px">
                <form id="filediscussionform" action="file_discussion.php" method="GET">
                    <input type="hidden" name="file" value="<?php print htmlspecialchars($_GET['file']) ?>">
                    <textarea cols="10" rows="10" style="width:99%;height:200px"></textarea>
                    <button id="newmessage" style="margin-top:2px"><i class="fa fa-edit"></i> Post</button>
                </form>
                <br>
                <div class="separator" style="margin:50px 0 5px 0"></div>
                <button id="deletediscussion"><i class="fa fa-trash-o"></i> Delete Discussion</button>
            </td>
            <td style="padding: 5px">
                <div id="messages"></div>
            </td>
        </tr>
    </table>

    <?php
}
?>


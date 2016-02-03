<?php
include_once 'data.php';
include_once 'functions.php';
include_once 'index.inc.php';
session_write_close();

echo '<body class="xdiscussion" style="width:100%;height:100%">';

if (empty($_GET['project']) || !is_numeric($_GET['project'])) {
    displayError('No project ID provided.');
} else {
    $projectID = intval($_GET['project']);
}

database_connect(IL_DATABASE_PATH, 'library');

$id_query = $dbHandle->quote($projectID);

// Check if the user is in this project.
$stmt = $dbHandle->prepare("SELECT projects.project as p"
        . " FROM projects LEFT OUTER JOIN projectsusers ON projectsusers.projectID=projects.projectID"
        . " WHERE projects.projectID=:projectID AND (projectsusers.userID=:userID OR projects.userID=:userID)");

$stmt->bindParam(':userID', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindParam(':projectID', $projectID, PDO::PARAM_INT);

$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($result['p'])) {

    displayError('You are not authorized to see this project.');
}

$query = $dbHandle->query("SELECT project FROM projects WHERE projectID=$id_query LIMIT 1");
$project_name = $query->fetchColumn();
$dbHandle = null;

?>
<div id="firstrow" class="alternating_row" style="padding: 15px;font-size: 1.25em;font-weight:bold"
     data-projectid="<?php echo intval($_GET['project']) ?>">
    Discussion about the project: <?php echo htmlspecialchars($result['p']) ?>.
</div>
<div id="leftcolumn" class="alternating_row" style="float: left;width: 250px;height:100%;padding: 0 15px 15px 15px">
    <form id="filediscussionform" action="discussion.php" method="GET">
        <textarea style="width:99%;height:250px;resize: vertical"></textarea>
        <button id="newmessage" style="margin-top:2px;width:100%"><i class="fa fa-edit"></i> Post Message</button>
    </form>
    <br>
    <button id="deletediscussion" style="margin-top:1em;width:100%"><i class="fa fa-trash-o"></i> Delete Discussion</button>
</div>
<div id="messages" style="padding:15px;word-wrap: break-word;overflow: auto"></div>
<div id="dialog-confirm"></div>
<script type="text/javascript">
    // Page class.
    var projectDiscussion = {
        projectID: $('#firstrow').data('projectid'),
        adjustDivSize: function () {
            $('#leftcolumn').height($(window).height() - $('#firstrow').outerHeight() - 15);
            $('#messages').width($(window).width() - 310);
            $('#messages').height($(window).height() - $('#firstrow').outerHeight() - 30);
        },
        loadMessages: function () {
            $('#messages').load('ajaxdiscussion.php', 'project=' + this.projectID + '&read=1');
        }
    };
    $(document).ready(function () {
        // Container resizing.
        projectDiscussion.adjustDivSize();
        $(window).resize(function () {
            projectDiscussion.adjustDivSize();
        });
        // Message loading.
        projectDiscussion.loadMessages();
        setInterval(function () {
            projectDiscussion.loadMessages();
        }, 5000);
        // Buttons.
        $('button').button();
        // Dialog.
        $('#dialog-confirm')
            .html('<p><i class="fa fa-exclamation-triangle ui-state-error-text" style="float:left;margin:2px 6px;padding-bottom:2em"></i>Do you want to permanently delete this discussion?</p>')
            .dialog({
                autoOpen: false,
                width: 'auto',
                height: 'auto',
                title: 'Delete discussion?',
                buttons: [
                    {
                        text: "Delete",
                        click: function () {
                            $.get('ajaxdiscussion.php', {
                                'project': projectDiscussion.projectID,
                                'delete': 1
                            }, function (answer) {
                                if (answer === 'OK')
                                    projectDiscussion.loadMessages();
                            });
                            $(this).dialog("close");
                        }
                    },
                    {
                        text: "Close",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }]
            });
        // Submit new message.
        $('#filediscussionform').submit(function (e) {
            e.preventDefault();
            var newmessage = $(this).find('textarea').val();
            if (newmessage != '') {
                $.post('ajaxdiscussion.php', {project: projectDiscussion.projectID, newmessage: newmessage}, function (answer) {
                    if (answer == 'OK') {
                        $('textarea').val('');
                        projectDiscussion.loadMessages();
                    }
                });
            }
        });
        // Delete discussion.
        $('#deletediscussion').click(function () {
            $('#dialog-confirm').dialog('open');
        });
    });
</script>
</body>
</html>


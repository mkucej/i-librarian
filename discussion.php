<?php
include_once 'data.php';
include_once 'functions.php';
include_once 'index.inc.php';
session_write_close();

echo '<body class="discussion">';

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
} else {
    
    echo "<h2>Discussion about the project: \"" . htmlspecialchars($result['p']) . "\"</h2>";
}

$query = $dbHandle->query("SELECT project FROM projects WHERE projectID=$id_query LIMIT 1");
$project_name = $query->fetchColumn();
$dbHandle = null;


?>
<div class="messages <?php print intval($_GET['project']); ?>"
     style="height:50%;overflow: scroll;border: 1px solid rgba(0,0,0,0.25);background-color: white"></div>
<form action="discussion.php" method="GET">
<input type="hidden" name="project" value="<?php print htmlspecialchars($_GET['project']) ?>">
<button name="delete1" id="delete1">Delete Discussion</button>
<input type="checkbox" name="delete2" value="Delete Discussion" id="delete2">
</form>
<br><br>
<form action="discussion.php" method="GET">
<input type="hidden" name="project" value="<?php print htmlspecialchars($_GET['project']) ?>">
<textarea name="newmessage" cols="65" rows="10" wrap="virtual" style="border: 1px white inset"></textarea>
<br>
<button id="newmessage">Send Message</button>
</form>
<script type="text/javascript">
    var projectID=$('.messages').attr('class').split(' ').pop();
    function loadmessages() {
        $('.messages').load('ajaxdiscussion.php', 'project='+projectID+'&read=1');
    }
    loadmessages();
    setInterval(loadmessages,5000);
    $('#newmessage').click(function(e){
        e.preventDefault();
        var newmessage=$('textarea').val(), projectID=$(this).siblings('input[name=project]').val();
        if(newmessage!='') {
            $.post('ajaxdiscussion.php',{ project: projectID, newmessage: newmessage}, function(answer){
                if(answer=='OK') {
                     $('textarea').val('');
                     loadmessages();
                }
            });
        }
    });
    $('#delete1').click(function(e){
        e.preventDefault();
        var projectID=$(this).siblings('input[name=project]').val(), $checkbox=$('#delete2');
        if($checkbox.is(':checked')) {
            $.get('ajaxdiscussion.php',{ project: projectID, delete1: 1, delete2: 1}, function(answer){
                if(answer=='OK') {
                    $checkbox.prop('checked',false);
                    loadmessages();
                }
            });
        }
    });
    $('button').button();
</script>
</body>
</html>


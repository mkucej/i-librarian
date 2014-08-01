<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect($database_path, 'library');
$id_query = $dbHandle->quote($_GET['project']);
$query = $dbHandle->query("SELECT project FROM projects WHERE projectID=$id_query LIMIT 1");
$project_name = $query->fetchColumn();
$dbHandle = null;

include_once 'index.inc.php';
?>
<body class="discussion">
<div style="margin:8px;width:99%;height:99%">
<h2>Discussion about the "<?php if(isset($_GET['project'])) print htmlspecialchars($project_name) ?>" project</h2>
<div class="messages <?php print intval($_GET['project']); ?>"
     style="width:99%;height:50%;overflow: scroll;border: 1px white inset;background-color: white"></div>
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
</div>
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


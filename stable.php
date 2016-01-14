<?php
include_once 'data.php';
include_once 'functions.php';
include_once 'index.inc.php';
?>
<body style="margin:0px;padding:0">
<div class="ui-state-default ui-corner-top" style="float:left;margin:2px 4px 1px 4px;padding:1px 4px">
    <a href="<?php print IL_URL; ?>?id=<?php print $_GET['id']; ?>" style="display:block">
    <i class="fa fa-home"></i> Open in I, Librarian
    </a>
</div>
<div style="clear:both"></div>
<?php
if (isset($_GET['id'])) {
    $_GET['file'] = $_GET['id'];
    include 'file_top.php';
}
?>
<script type="text/javascript">
filetop.init();
</script>
</body>
</html>

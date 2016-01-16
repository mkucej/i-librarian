<input type="text" style="width:99%" id="filetree-input">
<?php
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    print '<input type="text" id="win-drive" size="1" maxlength="1" value="C">:';
}
?>
<div class="items" id="filetree" data-root="/" style="height:280px;overflow:auto"></div>

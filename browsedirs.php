<?php
$dir = array_shift(explode(DIRECTORY_SEPARATOR, dirname(__FILE__))).DIRECTORY_SEPARATOR;
$dir = str_replace ("\\", "/", $dir);
?>
<input type="text" style="width:99%" id="filetree-input">
<?php
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    print '<input type="text" id="win-drive" size="1" maxlength="1" value="C">:';
}
?>
<div class="items" id="filetree" data-root="<?php print $dir ?>" style="height:316px;overflow:auto"></div>
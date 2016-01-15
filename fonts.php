<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_SESSION['auth'])) {

if (file_exists('ilibrarian.ini')) {
    $ini_array = parse_ini_file("ilibrarian.ini", true);
} else {
    $ini_array = parse_ini_file("ilibrarian-default.ini", true);
}
$default_settings = $ini_array['fonts and appearance'];

database_connect(IL_USER_DATABASE_PATH, 'users');

$dbHandle->beginTransaction();

$user_query = $dbHandle->quote($_SESSION['user_id']);

########## this are the new settings ##########

if (isset($_GET['change'])) {

	while (list($get_setting_name, $get_setting_value) = each($_GET)) {

		if(substr($get_setting_name, -5) == 'color' && ctype_xdigit($get_setting_value)) $new_settings[$get_setting_name] = substr(strtoupper($get_setting_value), 0, 6);
		if(substr($get_setting_name, -6) == 'family' && ctype_print($get_setting_value)) $new_settings[$get_setting_name] = $get_setting_value;
		if(substr($get_setting_name, -4) == 'size' && ctype_digit($get_setting_value)) $new_settings[$get_setting_name] = substr($get_setting_value, 0, 3);
		if(substr($get_setting_name, -6) == 'height' && is_numeric($get_setting_value)) $new_settings[$get_setting_name] = $get_setting_value;
	}

	########## determine the new settings as the difference from the default ##########

	if (!empty($new_settings)) $new_settings = array_diff_assoc($new_settings, $default_settings);

	########## update the database with new settings, which differ from the default ##########

	$dbHandle->query("DELETE FROM settings WHERE userID=$user_query AND
		(setting_name LIKE 'top_window%' OR setting_name LIKE 'main_window%' OR setting_name LIKE 'left_window%' OR setting_name LIKE 'alternating_row%')");

	while (list($setting_name, $setting_value) = each($new_settings)) {

		$dbHandle->query("INSERT INTO settings (userID, setting_name, setting_value) VALUES ($user_query, '$setting_name', '$setting_value')");
	}

} elseif (isset($_GET['default'])) {

	$dbHandle->query("DELETE FROM settings WHERE userID=$user_query AND
		(setting_name LIKE 'top_window%' OR setting_name LIKE 'main_window%' OR setting_name LIKE 'left_window%' OR setting_name LIKE 'alternating_row%')");
}

$dbHandle->commit();

########## read users settings ##########

$result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=$user_query");

while ($custom_settings = $result->fetch(PDO::FETCH_ASSOC)) {

	$custom_setting{$custom_settings['setting_name']} = $custom_settings['setting_value'];
}

if (!empty($custom_setting)) {
    $default_settings = array_merge($default_settings, $custom_setting);
}

while (list($setting_name, $setting_value) = each($default_settings)) {

	${$setting_name} = $setting_value;
}

$dbHandle = null;

print '<form action="fonts.php" method="GET">';

print '<table cellspacing="0" style="width: 100%">';

print "\n<tr>";

print "\n<td class=\"details alternating_row\" style=\"width: 100%\" colspan=4><b>General text</b></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details\" style=\"width:25%\">Font:";
print "\n<input type=\"text\" size=15 name=\"main_window_font_family\" value=\"$main_window_font_family\"></td>";
print "\n<td class=\"details\" style=\"\">Text Size:";
print "\n<input type=\"text\" size=2 maxlength=2 name=\"main_window_font_size\" value=\"$main_window_font_size\"> px (11-14)</td>";
print "\n<td class=\"details\" style=\"\">Text Color: ";
print "RGB# <input type=\"text\" size=6 maxlength=6 name=\"main_window_color\" value=\"$main_window_color\" title=\"hexadecimal code\"></td>";
print "\n<td class=\"details\" style=\"\">Line spacing: ";
print "\n<input type=\"text\" size=3 name=\"main_window_line_height\" value=\"$main_window_line_height\"></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4><b>Links, highlights</b></td></tr>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details\" style=\"\">Link Color: ";
print "RGB# <input type=\"text\" size=6 maxlength=6 name=\"main_window_link_color\" value=\"$main_window_link_color\" title=\"hexadecimal code\"></td>";

print "\n<td class=\"details\" style=\"\" colspan=3>Highlight Color: ";
print "RGB# <input type=\"text\" size=6 maxlength=6 name=\"main_window_highlight_color\" value=\"$main_window_highlight_color\" title=\"hexadecimal code\"></td>";
print "</td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details alternating_row\" style=\"width: 100%\" colspan=4><b>Titles</b></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details\" style=\"\">Font:";
print "\n<input type=\"text\" size=15 name=\"main_window_title_font_family\" value=\"$main_window_title_font_family\"></td>";
print "\n<td class=\"details\" style=\"\" colspan=3>Text Size:";
print "\n<input type=\"text\" size=2 maxlength=2 name=\"main_window_title_font_size\" value=\"$main_window_title_font_size\"> px (11-18)</td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details alternating_row\" style=\"width: 100%\" colspan=4><b>Abstracts</b></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details\" style=\"\">Font:";
print "\n<input type=\"text\" size=15 name=\"main_window_abstract_font_family\" value=\"$main_window_abstract_font_family\"></td>";
print "\n<td class=\"details\" style=\"\">Text Size:";
print "\n<input type=\"text\" size=2 maxlength=2 name=\"main_window_abstract_font_size\" value=\"$main_window_abstract_font_size\"> px (11-14)</td>";
print "\n<td class=\"details\" style=\"\" colspan=2>Line spacing:";
print "\n<input type=\"text\" size=3 name=\"main_window_abstract_line_height\" value=\"$main_window_abstract_line_height\"></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details alternating_row\" style=\"width: 100%\" colspan=4><b>Form fields</b></td>";

print "\n</tr>";
print "\n<tr>";

print "\n<td class=\"details\" style=\"\">Font:";
print "\n<input type=\"text\" size=15 name=\"main_window_form_font_family\" value=\"$main_window_form_font_family\"></td>";
print "\n<td class=\"details\" style=\"\" colspan=3>Text Size:";
print "\n<input type=\"text\" size=2 maxlength=2 name=\"main_window_form_font_size\" value=\"$main_window_form_font_size\"> px (11-14)</td>";

print "\n</tr>";

print '</table>';

print '<table cellpadding="0" style="width: 100%">';
print "\n<tr>";
print "\n<td class=\"details alternating_row\" colspan=2><b>Main Panel:</b></td>";
print "\n</tr>";
print "\n<tr>";
print "\n<td class=\"details\" style=\"width: 25%\">Page Color: ";
print "RGB#<input type=\"text\" size=6 maxlength=6 name=\"main_window_background_color\" value=\"$main_window_background_color\" title=\"hexadecimal code\"></td>";
print "\n<td class=\"details\">Alternating&nbsp;Row: ";
print "RGB#<input type=\"text\" size=6 maxlength=6 name=\"alternating_row_background_color\" value=\"$alternating_row_background_color\" title=\"hexadecimal code\"></td>";
print "\n</tr>";
print '</table>';

print '<table cellpadding="0" style="width: 100%">';
print "\n<tr>";
print "\n<td class=\"details alternating_row\" colspan=2><b>Top Panel:</b></td>";
print "\n</tr>";
print "\n<tr>";
print "\n<td class=\"details\" style=\"width: 25%\">Page Color: ";
print "RGB#<input type=\"text\" size=6 maxlength=6 name=\"top_window_background_color\" value=\"$top_window_background_color\" title=\"hexadecimal code\"></td>";
print "\n<td class=\"details\">Text Color: ";
print "RGB#<input type=\"text\" size=6 maxlength=6 name=\"top_window_color\" value=\"$top_window_color\" title=\"hexadecimal code\"></td>";
print "\n</tr>";
print '</table>';

print "\n<br>&nbsp;<input type=\"submit\" name=\"change\" value=\"Change\">";
print "\n<input type=\"submit\" name=\"default\" value=\"Use Default\">";

print '</form><br><br>';

print '&nbsp;<b>Preview:</b><div style="padding:6px">
	<div class="alternating_row" style="padding:6px" id="lorem-alternating-row">
	<div class="titles"><span id="lorem-title">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</span></div>
	<span id="lorem-text">Smith, J, Carpenter, P<br>Journal of Lorem Ipsum (2011)</span>
        <br><a name="" id="lorem-link">Link</a>
	</div>
	<div class="abstract" id="lorem-abstract">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus tincidunt ipsum at nisl consequat hendrerit. Proin non tellus nisi, eu posuere erat. Ut ut ligula et dolor ultrices consectetur ut non elit. Aliquam tristique condimentum magna, id adipiscing metus semper at. Pellentesque mollis velit sit amet nunc pellentesque egestas. Aenean varius scelerisque ipsum, nec ornare ipsum lobortis vel. Nunc venenatis ornare erat eget fringilla. Cras ullamcorper dolor non massa commodo a porttitor quam gravida.</div>
	</div>';

} else {
print 'Authorization required.';
}
?>
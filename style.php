<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$default_settings = parse_ini_file("ilibrarian.ini");

if (isset($_SESSION['auth'])) {

    ########## read users settings ##########

    database_connect($usersdatabase_path, 'users');
    $style_user_query = $dbHandle->quote($_SESSION['user_id']);
    $style_result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=$style_user_query");
    $dbHandle = null;
    while ($custom_settings = $style_result->fetch(PDO::FETCH_BOTH)) {

        $custom_setting{$custom_settings['setting_name']} = $custom_settings['setting_value'];
    }
}

if (!empty($custom_setting)) {

    $user_settings = array_merge($default_settings, $custom_setting);
} else {

    $user_settings = $default_settings;
}

while (list($setting_name, $setting_value) = each($user_settings)) {

    ${$setting_name} = $setting_value;
}

$content = "body {
    font-family: '" . $main_window_font_family . "',sans-serif;
    font-size: " . $main_window_font_size . "px;
    background-color: #" . $main_window_background_color . ";
    color: #" . $main_window_color . ";
    margin: 0;
    line-height: " . $main_window_line_height . "
}

td {
    font-size: " . $main_window_font_size . "px;
    vertical-align: top;
    padding: 0;
    line-height: " . $main_window_line_height . "
}

a {
    text-decoration: none;
    color: #" . $main_window_link_color . ";
}

input[type=\"text\"], input[type=\"password\"],
textarea, option, select {
    font-family: '" . $main_window_form_font_family . "', sans-serif !important;
    font-size: " . $main_window_form_font_size . "px !important;
}

table.items {
    margin-left: auto;
    margin-right: auto;
    border-bottom: 1px #" . $alternating_row_background_color . " solid;
    width: 100%
}

td.items, div.items {
    background-color: #" . $alternating_row_background_color . "
}

.titles, .titles-pdf {
    font-family: '" . $main_window_title_font_family . "', sans-serif;
    font-size: " . $main_window_title_font_size . "px;
    color: #" . $main_window_color . ";
    font-weight: bold;
    cursor: pointer;
    text-shadow: 1px 1px 1px white;
}
                
.brief, div.titles-pdf {
    padding: 0.2em 5px;
    overflow:hidden;
    text-overflow: ellipsis;
    white-space: nowrap
}

div.authors {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap
}

.abstract {
    padding-top: 3px;
    padding-bottom: 4px;
    padding-left: 7px;
    padding-right: 7px;
    text-align: justify;
    font-family: '" . $main_window_abstract_font_family . "', serif;
    font-size: " . $main_window_abstract_font_size . "px;
    line-height: " . $main_window_abstract_line_height . "
}

td.threed {
    padding: 3px;
    background-color: #" . $alternating_row_background_color . ";
    border-right: 1px #C6C8CC solid;
    border-bottom: 1px #C6C8CC solid;
    vertical-align: middle !important
}

td.threedleft {
    width: 12em;
    padding: 3px;
    background-color: #" . $alternating_row_background_color . ";
    border-top: 1px #FFFFFF solid;
    border-right: 1px #C6C8CC solid;
    border-bottom: 1px #C6C8CC solid;
    vertical-align: middle !important
}

td.threedright {
    padding: 3px;
    background-color: #" . $alternating_row_background_color . ";
    border-top: 1px #FFFFFF solid;
    border-left: 1px #FFFFFF solid;
    border-bottom: 1px #C6C8CC solid;
    vertical-align: middle !important
}

.select_span:hover {
    background-color: #" . $alternating_row_background_color . "
}

#top-panel, #top-panel-form {
    background-color: #" . $top_window_background_color . ";
    padding: 0px;
    border-bottom: 1px solid rgba(0,0,0,0.5)
}

#top-panel-form {
    padding:12px 12px;
    font-size:22px;
    text-align:center;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

td.topindex, div.topindex {
    color: #" . $top_window_color . "
}

a.topindex {
    display: inline-block;
    padding:2px 6px;
    font-size: 15px;
    font-weight: bold;
    color: #" . $top_window_color . ";
    text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

a.topindex:hover,
a.topindex:focus,
a.topindex_clicked {
    box-shadow: rgba(0,0,0,0.7) 0 0 2px 0 inset, rgba(255,255,255,0.3) 0 1px 2px 0;
    background-color: rgba(0,0,0,0.15);
}

#keyboardswitch {
    text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
}

a.navigation {
    color: #" . $main_window_color . "
}

body.leftindex, div.leftindex {
    background-color: #" . $left_window_background_color . ";
    margin: 0px;
}
                
.alternating_row {
    background-color: #" . $alternating_row_background_color . "
}

body.discussion {
    background-color: #" . $alternating_row_background_color . ";
    margin: 0;
    width:100%;
    height:100%
}

.clicked {
    color: #" . $main_window_link_color . " !important;
}

div.middle-panel:hover {
    background-color: #" . $top_window_background_color . ";
    color: #" . $top_window_color . "
}

input.bibtex {
    background-color: #" . $alternating_row_background_color . ";
    border: 0;
    font-family: '" . $main_window_font_family . "',sans-serif;
    font-size: " . $main_window_font_size . "px;
    color: #" . $main_window_color . ";
    cursor:pointer
}

.ui-widget {
    font-family: '" . $main_window_font_family . "',sans-serif;
    font-size: " . $main_window_font_size . "px
}

.ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button {
    font-family: '" . $main_window_font_family . "',sans-serif
}

body#notes_ifr {
    font-family: '" . $main_window_font_family . "',sans-serif;
    font-size: " . $main_window_font_size . "px;
    margin:8px;
}
";

header("Content-type: text/css; charset=utf-8");
?>
/* tipsy */
.tipsy {
    font-size: 1em
}

.tipsy-inner {
    box-shadow: #222 0 0 3px;
    -moz-box-shadow: #222 0 0 3px;
    -webkit-box-shadow: #222 0 0 3px;
    padding: 4px 7px;
    background: rgba(0,0,0,0.85);
    max-width: 220px
}

/*tiny mce*/
.mce-tinymce {
    border: 0 !important
}

/*jquery ui*/
.ui-button-text {text-shadow:1px 1px 0px rgba(255,255,255,0.8)}
.ui-button {border-bottom :1px solid rgba(0,0,0,0.2) !important}

.ui-state-highlight {
    cursor: pointer;
    padding:2px 1px;
    text-align: center;
    text-shadow:1px 1px 0px rgba(255,255,255,0.8)
}

.ui-dialog	{
    padding: 0;
    box-shadow: 0 0 16px rgba(0,0,0,0.6);
}

.ui-dialog-titlebar, .ui-datepicker-header {
    text-shadow: 1px 1px 0 rgba(255,255,255,0.8);
    border-radius: 3px 3px 0 0;
    border-top: 0;
    border-left: 0;
    border-right: 0;
}

.ui-dialog-titlebar {
    padding-top: 6px !important;
    padding-bottom: 6px !important;
}

.ui-dialog > .ui-dialog-buttonpane	{
    padding: 2px 6px;
    margin-top: 0
}

.ui-datepicker {
    padding: 0;
    width: 200px;
}

.ui-datepicker table {
    margin: 1px 2px;
    width: 98%
}

.ui-autocomplete {
    max-height: 200px;
    overflow: auto;
    /* add padding to account for vertical scrollbar */
    padding-right: 10px;
}

#dialog-confirm {
    padding: 0
}

#dialog-confirm > p {
    padding:1.5em 2em
}

sup {
    font-size:0.75em;
    position: relative;
    top:0.3em
}

sub {
    font-size:0.75em;
    position: relative;
    bottom:0.2em
}

input[type="text"],input[type="password"],textarea
{
    margin: 0;
    padding: 1px 0;
    border-radius:3px;
    border-left: 1px solid #a6a8ac;
    border-top: 1px solid #a6a8ac;
    border-bottom: 1px solid #e6e8ec;
    border-right: 1px solid #e6e8ec
}

input.matcher {
    width: 50%
}

form		{
    margin: 0px;
    display: inline
}

table		{
    margin: 0px;
    padding: 0px;
    border: 0px;
    border-spacing: 0px;
}

table.top	{
    width: 100%
}

td.omnitooltd {
    padding-bottom:6px
}

td.items,div.items	{
    padding: 8px 12px;
    overflow: hidden
}

td.quicksearch
{
    text-align: right;
    padding: 2px 4px 2px 4px;
}

.fa {
    font-size:14px
}

.fa-circle, .fa-circle-o {
    font-size: 10px;
    position: relative;
    bottom:0.1em
}

.fa-check-square, .fa-square-o {
    width: 1em;
}

#search > span, #clear > span {
    padding:0
}

.leftbutton	{
    height:28px;
    line-height:28px;
    text-align: center;
    cursor: pointer;
    text-shadow:1px 1px 0 rgba(255,255,255,0.5);
    font-weight: normal;
    border-bottom:1px solid rgba(0,0,0,0.15);
    border-left:0
}

.leftleftbutton
{
    width: 6px;
    height: 28px;
    background-color: #b6b8bc;
    padding-bottom: 1px;
    border-bottom:1px solid rgba(0,0,0,0.15);
    text-shadow:1px 1px 0 rgba(255,255,255,0.4);
}

.del-saved-search, .empty-flagged {
    float:right;
    margin:1px 0;
    padding:0 4px
}

.categorydiv {
    resize: vertical
}

table.threed	{
    border-spacing: 1px;
    margin: 0px;
}

.details	{
    padding:4px 8px;
    border-bottom: dotted 1px #b6b8bc;
    text-align: left;
    height:17px;
    line-height:17px;
}

#customization td {
    line-height:16px;
    padding: 1px
}

.cat1, .cat2, .cat3, .cat4,
.author, .jour, .jour2,
.sec, .sec2, .key, .misc,
.savedsearch, .journal-name,
.listleft, .flag, .update_clipboard,
.update_shelf, .update_project, .star,
.author_expander,.expander,
.select_span, .flipnames
{
    cursor:pointer
}

.savedsearch	{
    padding:2px 4px;
    margin:2px 0;
    width:180px;
}

div.file-title  {
    cursor: auto !important
}

.abstract {
    column-count:2;
    column-gap:2em;
    column-rule: 2px groove rgba(255,255,255,0.75);
    -moz-column-count:2;
    -moz-column-gap:2em;
    -moz-column-rule: 2px groove rgba(255,255,255,0.75);
    -webkit-column-count:2;
    -webkit-column-gap:2em;
    -webkit-column-rule: 2px groove rgba(255,255,255,0.75);
}

.item-sticker {
    box-shadow: 0 0 2px rgba(0,0,0,0.15);
}

.file-grid {
    float:left;
    width:33.2%;
    width:calc(33.33% - 1px);
    border:1px solid rgba(0,0,0,0.2);
    border-left:none
}

.lib-shadow-top {
    box-shadow: 0 -1px 2px rgba(0,0,0,0.3);
}

.lib-shadow-bottom {
    box-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.jgrowl-error {
    background: rgba(120,0,0,0.85) !important
}

div.save_container {
    display:none;
    height:580px;
    overflow:auto;
    background-color:#fff;
    margin:10px;
    border-left: 1px solid #999;
    border-top: 1px solid #999;
    border-bottom: 1px solid #ccc;
    border-right: 1px solid #ccc
}

.saved-search, .flagged-items, .select-import {
    cursor:pointer;
    font-weight:bold;
}

iframe {display:block}

#signin-background {
    background-color: #eeeeef;
    background: url('img/gridme.png');
}

#signin-container {
    box-shadow: 0 0 4px rgba(0,0,0,0.1);
}

/*ICON VIEW*/

.new-item {
    position:absolute;
    top:0;
    right:0;
    width:3.5em;
    text-align:center;
    background-color:rgba(222,222,222,0.85);
    padding:2px 0;
    font-weight:bold
}

.thumb-items {
    width:360px;
    float:left;
    margin:20px 0 0 4px
}

.thumb-items > div {
    position:relative;
    width:360px;
    height:240px;
    background-color:white;
    overflow:hidden;
    box-shadow:0 0 3px #888;
}

.thumb-titles {
    position:absolute;
    bottom:0;
    left:0;
    background-color:rgba(0,0,0,0.8);
    color:white;
    width:348px;
    padding:2px 6px 4px 6px;
    cursor:pointer
}

#contextmenu {
    text-align:center;
    padding:10px;
    cursor:pointer;
    width:120px;
    position:absolute;
    box-shadow:#333 0 0 5px;
}

#menuwrapper {
    position:absolute;
    top:0;
    left:0
}

/*keyboard*/

#keyboard {
    display:none;
    height:14.5em;
    width:100%;
    position:fixed;
    bottom:0;
    left:0;
    z-index:9999;
    color:#fff;
    background-color:rgba(33,33,33,0.75)
}

#keyboard-header {
    background-color:#000;
    padding:0.5em
}

#keyboard-header > div > div {
    display:inline-block;
    padding:0 1em;
    text-align:center;
    cursor:pointer
}

.keyboard-header-active {
    background-color:#fff;
    color:#000
}

.keyboard-content {
    display:none;
    overflow:auto;
    height:11em;
    padding:0.5em
}

#keyboard > .keyboard-content > div {
    background-color:#000;
    display:inline-block;
    width:1.6em;
    height:1.4em;
    margin:0 0.3em 0.38em 0;
    padding:0.25em;
    font-size:16px;
    border-bottom:1px solid rgba(255,255,252,0.5);
    text-align:center;
    cursor:pointer
}

#keyboard-drag {
    position:absolute;
    right:2.5em;
    top:0.5em;
    padding:0 1em;
    cursor:pointer
}

#keyboard-close {
    position:absolute;
    right:5px;
    top:0.5em;
    padding:0 0.4em;
    cursor:pointer
}

/*pdf viewer*/

#pdf-viewer-controls	{
    width:100%;
    height:72px;
}

.pdf-viewer-control-row {
    overflow:hidden;
    height:35px;
    visibility:hidden
}

#pdf-viewer-img	{
    display:inline-block;
    background-repeat:no-repeat;
    background-size:contain;
    background-color: #fff;
    box-shadow: #666 0px 0px 3px;
    -moz-box-shadow: #666 0px 0px 3px;
    -webkit-box-shadow: #666 1px 1px 2px;
}

#pdf-viewer-div	{
    box-shadow: #666 0px 0px 2px 0 inset;
    -moz-box-shadow: #666 0px 0px 2px 0 inset;
    -webkit-box-shadow: #666 0px 0px 2px 0 inset;
    width:100%;
    height:calc(100% - 73px);
    position:relative;
    border-top:solid #86888c 1px;
    background-color:#c6c8cc;
    overflow:auto;
    text-align:center
}

#pdf-viewer-img-div    {
    position:relative;
    width:auto;
    height:100%;
    text-align:center;
    overflow:auto;
    cursor:pointer
}

#thumbs {
    padding-bottom: 100px
}

.pdf-viewer-thumbs	{
    box-shadow: #666 1px 1px 2px;
    -moz-box-shadow: #666 1px 1px 2px;
    -webkit-box-shadow: #666 1px 1px 2px;
    cursor: pointer;
}

.pdfviewer-highlight       {
    position:absolute;
    background-color:rgba(255,60,0,0.2);
    padding:0 0.5%;
    display: none
}

.pdf-text-div       {
    position:absolute;
}

.pdf-text-div.ui-selecting      {
    background-color:rgba(0,20,255,0.1);
}

.search-result {
    padding:0 6px;
    cursor:pointer
}

#highlight-container, #annotation-container       {
    position:absolute;
    top: 0;
}

#annotation-container       {
    /*THIS COMPENSATES FOR THE INABILITY OF IE TO BIND EVENTS TO AN EMPTY DIV WITH ABSOLUTE POSITION*/
    background-color:rgba(255,255,255,0.01);
}

.separator	{
    height:0px;
    border-top:1px solid rgba(0,0,0,0.15);
    border-bottom:1px solid rgba(255,255,255,0.75);
    margin:0;
    padding:0;
    line-height:0px;
    font-size:0px
}
.vertical-separator {
    width:0px;
    height:24px;
    border-left:1px solid rgba(0,0,0,0.15);
    border-right:1px solid rgba(255,255,255,0.75);
    display:inline-block;
    margin-left:4px;
    margin-right:4px;
    margin-top:4px
}

.marker-yellow {
    background-color:rgba(0,20,255,0.15);
    height:1.2%;
    position:absolute
}

.marker-yellow-others {
    background-color: rgba(255,0,255,0.20);
    height:1.2%;
    position:absolute
}


.marker-note {
    background-color:rgba(255,255,255,0.6);
    line-height:40px;
    width:40px;
    height:40px;
    position:absolute;
    border:10px solid;
    border-color: rgba(40,50,255,0.35);
    border-radius: 30px
}

.marker-note-others {
    background-color:rgba(255,255,255,0.6);
    line-height:40px;
    width:40px;
    height:40px;
    position:absolute;
    border:10px solid;
    border-color: rgba(255,0,255,0.35);
    border-radius: 30px
}

.marker-note:hover {
    border-color: rgba(40,50,255,0.5);
}

.marker-note-others:hover {
    border-color: rgba(255,0,255,0.5);
}

.note-ta {
    width:233px;
    height:145px;
    resize:none;
    padding:0;
    margin:0;
    margin-top:4px;
}

.save-note {
    width:18px;
    margin-top:2px
}

#zoom   {
    float:left;
    width:120px;
    margin-left: 4px;
    margin-right:10px;
    margin-top:2px
}

#navpane {
    float:left;
    text-align:center;
    width:210px;
    height:100%;
    overflow:scroll;
    overflow-x:hidden;
    overflow-y:scroll
}

#cursor {
    box-shadow: #666 1px 1px 2px;
    -moz-box-shadow: #666 1px 1px 2px;
    -webkit-box-shadow: #666 1px 1px 2px;
    display:none;
    position:fixed;
    background-color:rgb(153,159,255);
    z-index:5000;
    padding:0.5em 1em
}

#pdf-viewer-delete-menu  {
    position:fixed;
    top:75px;
    left:335px;
    z-index:100;
    width:160px;
    padding:2px;
    border:1px solid #b6b8bc;
    box-shadow: #aaa 0 0 6px;
    -moz-box-shadow: #aaa 0 0 6px;
    -webkit-box-shadow: #aaa 0 0 6px
}

#pdf-viewer-delete-menu > div  {
    padding-left:20px;
    cursor: pointer
}

#pdf-viewer-delete-menu > div:hover  {
    background-color:#fff;
}

.bookmark, .annotation {
    padding:1px 6px;
    cursor:pointer;
    font-size:12px
}

@media print
{
    #top-panel,
    #leftindex-left,
    #customization,
    table.top,
    .noprint {display: none}
    * {height:auto !important;overflow: visible !important}
    #right-panel {display:inline}
    .item-sticker {float:none !important;border:0}
    body,
    a,
    .titles	{color: #000000}
    #pdf-viewer-controls, #thumbs {display: none}
    #pdf-viewer-img {display:inline}
}

/*Ubuntu fonts*/
@font-face {
    font-family: Ubuntu;
    font-weight: normal;
    src: url('fonts/Ubuntu-R.ttf');
}

@font-face {
    font-family: Ubuntu;
    font-weight: bold;
    src: url('fonts/Ubuntu-B.ttf');
}

@font-face {
    font-family: Ubuntu;
    src: url('fonts/Ubuntu-RI.ttf');
    font-style: italic;
}

@font-face {
    font-family: Ubuntu;
    src: url('fonts/Ubuntu-BI.ttf');
    font-weight: bold;
    font-style: italic;
}

<?php
echo $content;
?>
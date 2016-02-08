<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (file_exists('ilibrarian.ini')) {
    $ini_array = parse_ini_file("ilibrarian.ini");
} else {
    $ini_array = parse_ini_file("ilibrarian-default.ini");
}

if (isset($_SESSION['auth'])) {

    ########## read users settings ##########

    database_connect(IL_USER_DATABASE_PATH, 'users');
    $style_user_query = $dbHandle->quote($_SESSION['user_id']);
    $style_result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=$style_user_query");
    $dbHandle = null;
    while ($custom_settings = $style_result->fetch(PDO::FETCH_BOTH)) {

        $custom_setting{$custom_settings['setting_name']} = $custom_settings['setting_value'];
    }
}

if (!empty($custom_setting)) {

    $user_settings = array_merge($ini_array, $custom_setting);
} else {

    $user_settings = $ini_array;
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
}
                
.brief, div.titles-pdf {
    padding: 0.25em 0.25em;
    overflow:hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    padding: 0.25em;
    background-color: #" . $alternating_row_background_color . ";
    border: 1px solid rgba(0,0,0,0.1);
    vertical-align: middle !important
}

td.threedleft {
    width: 12em;
    padding:0.25em 0.25em 0.25em 0.5em;
    background-color: #" . $alternating_row_background_color . ";
    border: 1px solid rgba(0,0,0,0.1);
    vertical-align: middle !important
}

td.threedright {
    padding:0.25em;
    background-color: #" . $alternating_row_background_color . ";
    border: 1px solid rgba(0,0,0,0.1);
    vertical-align: middle !important
}

.select_span:hover {
    background-color: #" . $alternating_row_background_color . "
}

#top-panel, #top-panel-form {
    background-color: #" . $top_window_background_color . ";
    padding: 0px;
    border-bottom: 1px solid rgba(0,0,0,0.25)
}

#top-panel-form {
    padding:12px 12px;
    font-size:22px;
    text-align:center;
}

td.topindex, div.topindex {
    color: #" . $top_window_color . "
}

a.topindex {
    padding:4px 8px;
    font-size: 17px;
    color: #" . $top_window_color . ";
    border: 1px solid transparent;
}

a.topindex:hover,
a.topindex:focus,
a.topindex_clicked {
    border: 1px solid #" . $top_window_color . ";
}

#search-menu > div:hover,
#search-menu > div:focus,
#search-menu > div.tabclicked,
#items-menu > div:hover,
#items-menu > div:focus,
#items-menu > div.tabclicked,
#items-item-menu,
#items-notes-menu,
#items-pdf-menu-a,
#items-pdf-menu-b,
.ui-state-active,
.ui-state-hover,
.ui-state-focus,
#items-left .items:hover{
    background-color: #" . $top_window_background_color . " !important;
    color: #" . $top_window_color . " !important;
    border-color: #" . $top_window_background_color . " !important;
    text-shadow: none;
}

#items-item-menu,
#items-notes-menu,
#items-pdf-menu-a,
#items-pdf-menu-b {
    box-shadow: 1px 1px 3px rgba(0,0,0,0.33)
}

a.navigation {
    color: #" . $main_window_color . "
}

.alternating_row, .leftindex {
    background-color: #" . $alternating_row_background_color . ";
    color: #" . $main_window_color . "
}

body.discussion {
    background-color: #" . $alternating_row_background_color . ";
    margin: 0;
    height: 100%;
    padding:1em 2em;
}

.clicked {
    color: #" . $main_window_link_color . " !important;
}

#items-left > .clicked {
    background-color: #" . $top_window_background_color . ";
    color: #" . $top_window_color . " !important;
}

div.middle-panel:hover {
    background-color: #" . $top_window_background_color . ";
    color: #" . $top_window_color . "
}

input.bibtex {
    background-color: #" . $alternating_row_background_color . ";
    border: 0;
    outline:none;
    font-family: monospace !important;
    font-size: " . $main_window_font_size . "px;
    color: #" . $main_window_color . ";
    cursor:pointer
}

body#notes_ifr {
    font-family: '" . $main_window_font_family . "',sans-serif;
    font-size: " . $main_window_font_size . "px;
    margin:8px;
}

.ui-state-error-text {
color: #" . $main_window_highlight_color . " !important;
}

.ui-widget, .ui-widget button {
    font-family: '" . $main_window_font_family . "', sans-serif;
}

.ui-widget-content {
color: #" . $main_window_color . ";
background-color: #" . $main_window_background_color . ";
}

.ui-widget-content a {
color: #" . $main_window_color . ";
}

.ui-widget-header {
color: #" . $main_window_color . ";
background-color: #" . $alternating_row_background_color . ";
}
";

header("Content-type: text/css; charset=utf-8");
?>
/* tipsy */
.tipsy {
font-size: 1em
}

.tipsy-inner {
box-shadow: rgba(0,0,0,0.15) 0 0 3px;
padding: 4px 7px;
background: rgba(20,20,20,0.9);
max-width: 220px
}

/* jQuery UI Tooltip */

.ui-tooltip.ui-widget.ui-widget-content {
background-color: rgba(60,60,70,1);
border: none;
max-width: 250px;
padding: 0.75em 1.25em;
color: white;
border-radius: 0px;
box-shadow: 0 0 6px 0px rgba(0,0,0,0.75);
}

/*tiny mce*/
.mce-tinymce {
border: 0 !important
}

/*jquery ui*/

.ui-state-default {
cursor: pointer;
}

.ui-dialog {
padding: 0;
box-shadow: 0 0 16px rgba(0,0,0,0.6);
}

.ui-dialog-titlebar, .ui-datepicker-header {
font-size:1.1em;
border-top: 0;
border-left: 0;
border-right: 0;
}

.ui-dialog-titlebar {
padding-top: 0.6em !important;
padding-bottom: 0.6em !important;
}

.ui-dialog > .ui-dialog-buttonpane	{
padding: 0.25em 1em;
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
}

.ui-autocomplete .ui-state-focus {
border:0;
margin:0
}

.ui-selectmenu-button span.ui-selectmenu-text {
padding-top: 0.25em;
padding-bottom: 0.25em;
line-height:1.3
}

.ui-button-text-only .ui-button-text {
line-height:1.3
}

#leftindex-left .menu,
#leftindex-left .projectheader,
#addarticle-left button,
#tools-left button {
    width: 100%;
    border-left: none;
    border-right: none;
    margin: 0.5em 0 0 0;
    display: block
}

#overlay {
z-index:10000;
cursor:wait;
width:100%;
}

#overlay > img {
margin-left: calc(50% - 32px);
}

#dialog-confirm {
padding: 0
}

#dialog-confirm > p {
padding:1.5em 2em
}

#pdf-div {
display: block;
width:100%;
height:100%;
border:0
}

#preview {
    display:none;
    position:fixed;
    top:4px;
    right:4px;
    border:solid 1px #C3C3C3;
    box-shadow:0 0 6px rgba(0,0,0,0.3);
    z-index:100;
    max-width: 500px
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
padding: 1px;
border-radius:0px;
border-left: 1px solid rgba(0,0,0,0.3);
border-top: 1px solid rgba(0,0,0,0.3);
border-bottom: 1px solid rgba(0,0,0,0.1);
border-right: 1px solid rgba(0,0,0,0.1)
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

td.quicksearch input
{
width: calc(100% - 0.8em);
border:0;
padding:0.4em 0.4em
}

.separators {
width:100%;
}

.separators > label {
float:left;
border-left: none;
border-right: none;
width: 33.333%;
margin: 0 !important
}

.separators > label > span {
padding-left: 0;
padding-right: 0
}

.fa-circle, .fa-circle-o {
font-size: 11px;
position: relative;
bottom:0.1em
}

.fa-check-square, .fa-square-o {
width: 1em;
}

.del-saved-search, .empty-flagged {
float:right;
margin:1px 0;
padding:0 4px
}

.highlight-search {
    background-color: orange;
}

.categorydiv {
resize: vertical
}

table.threed	{
margin: 0px;
border-collapse: collapse;
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
.author_expander,.quick-view,
.quick-view-external,.select_span, .flipnames
{
cursor:pointer
}

.savedsearch	{
padding:2px 4px;
margin:2px 0;
width:180px;
}

#nav-prev, #nav-next {
width:100%;
border-left:none;
border-right:none;
display:block
}

.items-nav {
width:33.33%;
border-left:none;
border-right:none;
display:block;
float: left;
margin:0;
}

#items-menu {
float:left;
width:4em;
height:100%;
border:0;
padding:0;
line-height: 1.1em;
border-top:1px solid rgba(0,0,0,0.1);
border-right:1px solid rgba(0,0,0,0.1);
text-align: center
}

#items-menu > div {
padding: 8px 0
}

#items-menu .fa {
font-size: 16px
}

#search-menu > div {
    width: 20%;
    float:left;
    text-align:center;
    padding: 5px 0;
    cursor: pointer;
    border-left: none;
    border-right: none;
    border-bottom: none;
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

.file-grid > div:first-child {
padding-left: 0.75em
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
background-color: #eeeeee;
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
box-shadow:0 0 3px rgba(0,0,0,0.5);
}

.thumb-titles {
position:absolute;
bottom:0;
left:0;
background-color:rgba(20,20,20,0.85);
color:white;
width:348px;
padding:2px 6px 4px 6px;
cursor:pointer;
}

.thumb-titles > div {
font-size:1.1em;
}

.thumb-titles > div {
overflow:hidden;
white-space:nowrap;
}

#contextmenu {
text-align:center;
padding:10px;
cursor:pointer;
width:120px;
position:absolute;
box-shadow:rgba(0,0,0,0.15) 0 0 5px;
}

#menuwrapper {
position:absolute;
top:0;
left:0;
z-index:1000
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
border-bottom:solid 1px rgba(0,0,0,0.33);
}

#pdf-viewer-controls .ui-button.ui-state-default {
border: none !important;
background-color:  transparent;
}

.pdf-viewer-control-row {
overflow:hidden;
height:35px;
visibility:hidden;
padding-left:0.25em;
display: inline-block;
}

.pdf-viewer-img	{
position:relative;
display:inline-block;
background-repeat:no-repeat;
background-size:contain;
background-color: #fff;
box-shadow: 0px 0px 4px rgba(0,0,0,0.33);
width:calc(100%-30px);
height:1500px;
margin:4px 2px 0 2px;
}

.pdf-viewer-img > i {
font-size:96px;
position: absolute;
top:calc(50% - 0.5em);
left:calc(50% - 0.5em);
color:rgba(0,0,0,0.75)
}

#pdf-viewer-div	{
box-shadow: inset 0 0 3px rgba(0,0,0,0.33);
width:100%;
height:calc(100% - 73px);
position:relative;
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
cursor:grab
}

#thumbs {
padding-bottom: 100px
}

.pdf-viewer-thumbs	{
box-shadow: 0 0 4px rgba(0,0,0,0.33);
cursor: pointer;
background-color: white;
margin:auto;
}

.pdfviewer-highlight       {
position:absolute;
background-color:rgba(255,60,0,0.2);
}

.pdfviewer-link {
position:absolute;
cursor: pointer;
}

.pdfviewer-link:hover {
background-color: rgba(255, 0, 0, 0.05);
border-bottom: 1px dashed rgba(255, 0, 0, 0.5);
}

.pdf-text-div       {
position:absolute;
color: transparent
}

.pdf-text-div.ui-selecting      {
background-color:rgba(0,20,255,0.1);
}

.search-result {
padding:0 6px;
cursor:pointer
}

.annotation-container,
.text-container,
.highlight-container {
position:absolute;
top: 0;
width:100%;
height:100%;
display:none
}

.text-container > div {
border-bottom: 1px dotted rgba(0,0,0,0.25)
}

#annotation-container       {
/*THIS COMPENSATES FOR THE INABILITY OF IE TO BIND EVENTS TO AN EMPTY DIV WITH ABSOLUTE POSITION*/
background-color:rgba(255,255,255,0.01);
}

.separator	{
height:1px;
background-color:rgba(0,0,0,0.12);
margin:0;
padding:0;
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
background-color:rgba(255,255,255,0.8);
line-height:40px;
width:40px;
height:40px;
position:absolute;
border:10px solid;
border-color: rgba(40,50,255,0.35);
border-radius: 30px
}

.marker-edit {
border-color:rgba(255,50,0,0.35);
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
border-color:rgba(255,50,0,0.35);
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

.annotation, .search-result, .bookmark {
border: 1px solid transparent
}

.annotation > div {
padding: 0.25em 0.5em
}

.annotation > div:nth-child(2) {
white-space:pre-wrap;
word-wrap:break-word
}

.annotation > textarea {
width:175px;
height:10em;
resize:vertical;
display:none
}

.note-save, .note-edit {
text-align: center
}

#zoom   {
float:left;
width:100px;
margin-left: 8px;
margin-right:10px;
margin-top:4px
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
display:none;
position:fixed;
background-color:rgba(255,255,255,0.66);
z-index:5000;
width:1.6em;
height:1.6em;
border-radius:0.8em
}

#pdf-viewer-delete-menu  {
position:fixed;
top:75px;
left:335px;
z-index:100;
width:160px;
padding:0;
border:1px solid #b6b8bc;
box-shadow: rgba(0,0,0,0.15) 0 0 6px;
-moz-box-shadow: rgba(0,0,0,0.15) 0 0 6px;
-webkit-box-shadow: rgba(0,0,0,0.15) 0 0 6px
}

#pdf-viewer-delete-menu > div  {
padding-left:20px;
cursor: pointer
}

#pdf-viewer-delete-menu > div:hover  {
background-color:rgba(0,0,0,0.1);
}

.bookmark, .annotation {
padding:4px 6px;
margin-top:2px;
cursor:pointer;
}

.bookmark-level-1 {
    font-weight: bold;
}

.bookmark-level-2 {
    font-style: italic;
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
font-family: 'Liberation Sans';
font-weight: normal;
src: url('fonts/LiberationSans-Regular.ttf');
}

@font-face {
font-family: 'Liberation Sans';
font-weight: bold;
src: url('fonts/LiberationSans-Bold.ttf');
}

@font-face {
font-family: 'Liberation Sans';
src: url('fonts/LiberationSans-Italic.ttf');
font-style: italic;
}

@font-face {
font-family: 'Liberation Sans';
src: url('fonts/LiberationSans-BoldItalic.ttf');
font-weight: bold;
font-style: italic;
}

<?php
echo $content;
?>
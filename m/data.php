<?php
// I, Librarian version
$version = '3.2';

ini_set('user_agent', $_SERVER['HTTP_USER_AGENT']);
ini_set('default_charset', 'UTF-8');
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', true);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '128M');
date_default_timezone_set(@date_default_timezone_get());

// find out what the url string is
$protocol = 'http';
if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
    $protocol = 'https';
$parsed_file = parse_url($protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'], PHP_URL_PATH);
$parsed_file = str_replace(basename($parsed_file), '', $parsed_file);
$url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $parsed_file;
if (substr($url, -1) != "/")
    $url = $url . "/";

//mobile specific
chdir('..');
$library_path = getcwd() . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR;
$database_path = $library_path . 'database' . DIRECTORY_SEPARATOR;
$usersdatabase_path = $library_path . 'database' . DIRECTORY_SEPARATOR;

if (!is_writable($library_path)) {
    die('Error! Directory: "' . $library_path . '" must be writable.');
}
chdir('m');
//mobile specific

// set temp dir for this installation
$temp_dir = sys_get_temp_dir();
if (PHP_OS == 'Linux')
    $temp_dir = '/var/tmp';
if (substr($temp_dir, -1) == DIRECTORY_SEPARATOR)
    $temp_dir = substr($temp_dir, 0, -1);
$temp_dir .= DIRECTORY_SEPARATOR . 'i-librarian' . DIRECTORY_SEPARATOR . md5(strstr($url, '://'));
if (!is_dir($temp_dir))
    @mkdir($temp_dir, 0700, true);

### permanent sessions and garbage collection ###
### session garbage collection ###
$probability = rand(1, 100000);
if ($probability == 50000) {
    $session_dir = $temp_dir . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions';
    if (is_dir($session_dir)) {
        $clean_files = glob($session_dir . DIRECTORY_SEPARATOR . 'sess_*', GLOB_NOSORT);
        if (is_array($clean_files)) {
            foreach ($clean_files as $clean_file) {
                if (time() - filemtime($clean_file) > 31536000)
                    @unlink($clean_file);
            }
        }
    }
}
### session garbage collection ###

if (!is_dir($temp_dir . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions'))
    mkdir($temp_dir . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions');
session_save_path($temp_dir . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions');
session_start();

// PREVENT ACCESSING PAGES WHEN SIGNED OUT, SENDS FLAG TO JAVASCRIPT CLIENT
$allowed_pages = array('index2.php');
if (!in_array(basename($_SERVER['PHP_SELF']), $allowed_pages) && !isset($_SESSION['auth']))
    die('signed_out');

$keepsigned = '';
$cookietimeout = 0;

if (isset($_POST['keepsigned']) && $_POST['keepsigned'] == 1) {

    $cookietimeout = 604800;
} elseif (!empty($_SESSION['user_id'])) {

    if (file_exists($usersdatabase_path . 'users.sq3') && !isset($_SESSION['keepsigned'])) {
        try {
            $dbHandle = new PDO('sqlite:' . $usersdatabase_path . 'users.sq3');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            print "PHP extensions PDO and PDO_SQLite must be installed.";
            die();
        }
        $stmt = $dbHandle->prepare("SELECT setting_value FROM settings WHERE userID=:userID AND setting_name=:setting_name LIMIT 1");
        if (is_object($stmt)) {
            $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
            $stmt->bindParam(':setting_name', $setting_name, PDO::PARAM_STR);
            $userID = $_SESSION['user_id'];
            $setting_name = 'keepsigned';
            $stmt->execute();
            $keepsigned = $stmt->fetchColumn();
            $stmt = null;
            $_SESSION['keepsigned'] = $keepsigned;
        }
    } elseif (isset($_SESSION['keepsigned'])) {
        $keepsigned = $_SESSION['keepsigned'];
    }
    if ($keepsigned == 1)
        $cookietimeout = 604800;
}
$dbHandle = null;

setcookie(session_name(), session_id(), time() + $cookietimeout);
### permanent sessions and garbage collection ###

if (!is_dir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id()))
    mkdir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id());
?>

<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {

    $proxy_name = '';
    $proxy_port = '';
    $proxy_username = '';
    $proxy_password = '';

    if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
        if (!empty($_GET['proxystr'])) {
            $proxy_arr = explode(';', $_GET['proxystr']);
            foreach ($proxy_arr as $proxy_str) {
                if (stripos(trim($proxy_str), 'PROXY') === 0) {
                    $proxy_str = trim(substr($proxy_str, 6));
                    $proxy_name = parse_url($proxy_str, PHP_URL_HOST);
                    $proxy_port = parse_url($proxy_str, PHP_URL_PORT);
                    $proxy_username = parse_url($proxy_str, PHP_URL_USER);
                    $proxy_password = parse_url($proxy_str, PHP_URL_PASS);
                    break;
                }
            }
        }
    } elseif (isset($_SESSION['connection']) && $_SESSION['connection'] == "proxy") {
        if (isset($_SESSION['proxy_name']))
            $proxy_name = $_SESSION['proxy_name'];
        if (isset($_SESSION['proxy_port']))
            $proxy_port = $_SESSION['proxy_port'];
        if (isset($_SESSION['proxy_username']))
            $proxy_username = $_SESSION['proxy_username'];
        if (isset($_SESSION['proxy_password']))
            $proxy_password = $_SESSION['proxy_password'];
    }

    $current_version = '';
    $current_array = array();

    $response = getFromWeb('https://i-librarian.net/newversion.txt', $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    $current_array = explode(":", $response);

    if (count($current_array) == 2)
        $current_version = $current_array[1];

    if (version_compare($version, $current_version) == "-1")
        echo 'yes';
}
?>
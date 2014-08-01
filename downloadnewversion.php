<?php

include_once 'data.php';
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
    } else {
        if (isset($_SESSION['proxy_name']))
            $proxy_name = $_SESSION['proxy_name'];
        if (isset($_SESSION['proxy_port']))
            $proxy_port = $_SESSION['proxy_port'];
        if (isset($_SESSION['proxy_username']))
            $proxy_username = $_SESSION['proxy_username'];
        if (isset($_SESSION['proxy_password']))
            $proxy_password = $_SESSION['proxy_password'];
    }

    $response_string = '';
    $current_version = '';
    $current_array = array();

    if (isset($proxy_name) && !empty($proxy_name)) {

        $proxy_fp = @fsockopen($proxy_name, $proxy_port, $e1, $e2, 5);

        if ($proxy_fp) {

            fputs($proxy_fp, "GET http://i-librarian.net/newversion.txt HTTP/1.0\r\nHost: $proxy_name\r\n");
            if (!empty($proxy_username))
                fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_username:$proxy_password") . "\r\n");
            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

            while (!feof($proxy_fp)) {
                $response_string .= fgets($proxy_fp, 128);
            }

            fclose($proxy_fp);
        } else {
            die();
        }
    } else {

        $proxy_fp = @fsockopen('i-librarian.net', 80, $e1, $e2, 5);

        if ($proxy_fp) {

            $pdf_string = '';
            $cookies = array();

            fputs($proxy_fp, "GET /newversion.txt HTTP/1.0\r\n");
            fputs($proxy_fp, "Host: i-librarian.net\r\n");
            fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n\r\n");

            while (!feof($proxy_fp)) {
                $response_string .= fgets($proxy_fp, 128);
            }

            fclose($proxy_fp);
        } else {
            die();
        }
    }

    $response_string = strstr($response_string, "current-version:");
    $current_array = explode(":", $response_string);
    if (count($current_array) == 2)
        $current_version = $current_array[1];

    if (version_compare($version, $current_version) == "-1")
        echo 'yes';
}
?>
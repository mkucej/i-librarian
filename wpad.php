<?php
include_once 'data.php';

//check if proxy is set to autodetect

if (isset($_SESSION['connection']) && $_SESSION['connection'] == "autodetect") {

    session_write_close();

//get local network hostname

    $ipconfig = array ();

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        exec('ipconfig', $ipconfig);
        foreach ($ipconfig as $row) {
            if (preg_match('/ip address/i', $row)) {
                $row_arr = explode (':', $row);
                if (!empty($row_arr[1])) $ip = trim ($row_arr[1]);
            }
        }
    }

    if (empty($ip)) die();
    $localhostname = gethostbyaddr($ip);
    if ($localhostname == $ip) die();
    $hostname_arr = explode ('.', $localhostname);
    $hostname_arr2 = $hostname_arr;

//look for wpad.dat

    for ($i=0; $i<count($hostname_arr)-2; $i++) {

        unset($hostname_arr2[$i]);
        $hostname = implode ('.', $hostname_arr2);
        $wpad_url = 'http://wpad.'.$hostname.'/wpad.dat';
        $wpad = @file_get_contents ($wpad_url);
        if (!empty($wpad)) {
            print $wpad;
            die();
        }
    }

//look for proxy.pac

    for ($i=0; $i<count($hostname_arr)-2; $i++) {

        unset($hostname_arr2[$i]);
        $hostname = implode ('.', $hostname_arr2);
        $wpad_url = 'http://wpad.'.$hostname.'/proxy.pac';
        $wpad = @file_get_contents ($wpad_url);
        if (!empty($wpad)) {
            print $wpad;
            die();
        }
    }
}

//check if proxy is set to WPAD url

if (isset($_SESSION['connection']) && $_SESSION['connection'] == "url" && !empty($_SESSION['wpad_url'])) {
    $wpad = @file_get_contents ($_SESSION['wpad_url']);
    if (!empty($wpad)) {
        print $wpad;
        die();
    }
}
?>
<?php

define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');
require_once('../../lib/filelib.php');
require_login();
// TODO: Need to much rework in this file
$filename = required_param("file", PARAM_RAW);
$area= optional_param("area","iomad_bulk_logs",PARAM_RAW);
$filepath  = $CFG->tempdir . "/$area/" . $filename;

if (!file_exists($filepath)) {

    send_file_not_found();
} else {

    send_respone_file($filepath,$filename,  0, true);
}

function send_respone_file($fileurl, $filename, $lifetime = 0,  $forcedownload = true)
{
    global $CFG, $COURSE, $SESSION;
    $dontdie = false;
    if (!$fileurl or is_dir($fileurl)) {
        // nothing to serve
        if ($dontdie) {
            return;
        }
        die();
    }

    if ($dontdie) {
        ignore_user_abort(true);
    }

    $session = new \core\session\manager();

    $session->write_close(); // unlock session during fileserving
 
    $mimetype =  'application/x-forcedownload';

    $lastmodified = filemtime($fileurl);
    $filesize = filesize($fileurl);

    if ($lifetime > 0 && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        // get unixtime of request header; clip extra junk off first
        $since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
        if ($since && $since >= $lastmodified) {
            header('HTTP/1.1 304 Not Modified');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
            header('Cache-Control: max-age=' . $lifetime);
            header('Content-Type: ' . $mimetype);
            if ($dontdie) {
                return;
            }
            die();
        }
    }

    // do not put '@' before the next header to detect incorrect moodle configurations,
    // error should be better than "weird" empty lines for admins/users
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT');
    // if user is using IE, urlencode the filename so that multibyte file name will show up correctly on popup
    if (core_useragent::check_browser_version('MSIE')) {
        $filename = rawurlencode($filename);
    }

        header('Content-Disposition: attachment; filename="' . $filename . '"');

    if ($lifetime > 0) {

        header('Cache-Control: max-age=' . $lifetime);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
        header('Pragma: ');

        if (empty($CFG->disablebyteserving) && $mimetype != 'text/plain' && $mimetype != 'text/html') {

            header('Accept-Ranges: bytes');

            if (!empty($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_RANGE'], 'bytes=') !== FALSE) {

                $ranges = false;
                if (preg_match_all('/(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $ranges, PREG_SET_ORDER)) {
                    foreach ($ranges as $key => $value) {
                        if ($ranges[$key][1] == '') {
                            // suffix case
                            $ranges[$key][1] = $filesize - $ranges[$key][2];
                            $ranges[$key][2] = $filesize - 1;
                        } else
                        if ($ranges[$key][2] == '' || $ranges[$key][2] > $filesize - 1) {
                            // fix range length
                            $ranges[$key][2] = $filesize - 1;
                        }
                        if ($ranges[$key][2] != '' && $ranges[$key][2] < $ranges[$key][1]) {
                            // invalid byte-range ==> ignore header
                            $ranges = false;
                            break;
                        }
                        // prepare multipart header
                        $ranges[$key][0] = "\r\n--" . BYTESERVING_BOUNDARY . "\r\nContent-Type: $mimetype\r\n";
                        $ranges[$key][0] .= "Content-Range: bytes {$ranges[$key][1]}-{$ranges[$key][2]}/$filesize\r\n\r\n";
                    }
                } else {
                    $ranges = false;
                }
                if ($ranges) {
                }
            }
        } else {
            header('Accept-Ranges: none');
        }
    } else { // Do not cache files in proxies and browsers
        if (strpos($CFG->wwwroot, 'https://') === 0) { // https sites - watch out for IE! KB812935 and KB316431
            header('Cache-Control: max-age=10');
            header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
            header('Pragma: ');
        } else { // normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
            header('Pragma: no-cache');
        }
        header('Accept-Ranges: none'); // Do not allow byteserving when caching disabled
    }



    if ($mimetype == 'text/plain') {
        header('Content-Type: Text/plain; charset=utf-8'); // add encoding
    } else {
        // echo $mimetype;die;
        header('Content-Type: ' . $mimetype);
    }

    header('Content-Length: ' . $filesize);

    // flush the buffers - save memory and disable sid rewrite
    // this also disables zlib compression
    // prepare_file_content_sending();
    // send the contents
    ob_start();
    readfile($fileurl);
    echo ob_get_clean();

    if ($dontdie) {
        return;
    }
    die(); // no more chars to output!!!
}

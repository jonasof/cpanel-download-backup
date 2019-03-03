<?php

require "vendor/autoload.php";

use Gufy\CpanelPhp\Cpanel;

$config = require('config.php');

notifyStart:
    if ($config['notify_to']) {
        $message = "Backup of site {$config['host']} is starting";
        mail($config['notify_to'], $message, $message);
    }

getBackupName:
    $cpanel = new Cpanel($config);
    $response = $cpanel->execute_action(3, 'Backup', 'list_backups', $config['username']);
    $backup = json_decode($response)->result->data[0] ?? null;
    if (!$backup) throw new Exception("Account backup file not found");

loginAndDownload:
    $session = createSession($config);
    downloadBackup($config, $session, $backup);

notifyEnd:
    if ($config['notify_to']) {
        $message = "Backup of site {$config['host']} is done";
        mail($config['notify_to'], $message);
    }

function downloadBackup($config, $session, $backup) {
    $fileHandle = fopen($config['destination'], 'w+');

    $host = $config['host'];
    $url = "$host/$session/getbackupdate/?backupdate=$backup&backupdatebtn=Download";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FILE, $fileHandle);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, getCookieFileName());
    curl_setopt($ch, CURLOPT_TIMEOUT, -1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($cp, $data) use ($fileHandle){
      return fwrite($fileHandle, $data);
    });

    curl_exec($ch);
    curl_close($ch);
}

  function createSession($config) {
    $cp_user = $config['username'];
    $cp_pwd = $config['password'];
    $url = $config['host'] . "/login";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, getCookieFileName());
    curl_setopt($ch, CURLOPT_POSTFIELDS, "user=$cp_user&pass=$cp_pwd");
    curl_setopt($ch, CURLOPT_TIMEOUT, 100020);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $f = curl_exec($ch);
    $h = curl_getinfo($ch);
    curl_close($ch);

    if ($f && strpos($h['url'], "cpsess"))
    {
        $pattern = "/.*?(\/cpsess.*?)\/.*?/is";
        $preg_res = preg_match($pattern, $h['url'], $cpsess);
    }

    return $cpsess[1] ?? "";
}

function getCookieFileName() {
    static $file;

    if (!$file) {
        $file = stream_get_meta_data(tmpfile())['uri'];
        register_shutdown_function(function () use($file) {
            unlink($file);
        });
    }

    return $file;
}

<?php
/**
 * Created by PhpStorm.
 * User: Niklas
 * Date: 14.12.2018
 * Time: 08:06
 */

$projectdir = $_SERVER['DOCUMENT_ROOT'];
$logFolder = $projectdir . '/jtlconnector/logs/';
$downloadFolder = $projectdir . '/jtlconnector/install/';

if (isset($_REQUEST['download'])) {
    //Reset log zip
    if (file_exists($downloadFolder . 'logs.zip')) {
        unlink($downloadFolder . 'logs.zip');
    }
    
    $zip = new \ZipArchive();
    $zip->open($downloadFolder . 'logs.zip', \ZipArchive::CREATE);
    
    //Add logs to new zip
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            $zip->addFile($logFolder . $file, $file);
        }
    }
    $zip->close();
    
    //Download zip file
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="logs.zip"');
    readfile($downloadFolder . 'logs.zip');

} elseif (isset($_REQUEST['clear'])) {
    //Clear all logs
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            unlink($logFolder . $file);
        }
    }
    
    //Redirect to connector page
    header('Location: /jtlconnector/install/#dev_logging');
}

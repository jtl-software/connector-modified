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
    if (file_exists($downloadFolder . 'logs.zip')) {
        unlink($downloadFolder . 'logs.zip');
    }
    
    $zip = new \ZipArchive();
    $zip->open($downloadFolder . 'logs.zip', \ZipArchive::CREATE);
    
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            $zip->addFile($logFolder . $file, $file);
        }
    }
    $zip->close();
    
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="logs.zip"');
    readfile($downloadFolder . 'logs.zip');

} elseif (isset($_REQUEST['clear'])) {
    foreach (scandir($logFolder) as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess') {
            unlink($logFolder . $file);
        }
    }
    
    header('Location: /jtlconnector/install/#dev_logging');
}

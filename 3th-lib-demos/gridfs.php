<?php

require 'vendor/autoload.php';

$client = new \MongoDB\Client('mongodb://localhost:27017');
$database = $client->selectDatabase('demo');
// GridFS
$bucket = $database->selectGridFSBucket();
// var_dump($bucket);
$bucket_name = $bucket->getBucketName();
// var_dump($bucket_name);

$gridfs_filename = 'test-gridfs.txt';
/*
$stream = $bucket->openUploadStream($gridfs_filename);
$contents = file_get_contents(__DIR__ . '/test-gridfs.txt');
fwrite($stream, $contents);
fclose($stream);
*/

$stream = $bucket->openDownloadStreamByName($gridfs_filename);
$file_id = $bucket->getFileIdForStream($stream);
// var_dump($file_id);
$metadata = $bucket->getFileDocumentForStream($stream);
// var_dump($metadata);
$contents = stream_get_contents($stream);
var_dump($contents);
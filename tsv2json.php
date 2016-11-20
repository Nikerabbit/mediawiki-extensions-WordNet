<?php

require __DIR__ . '/vendor/autoload.php';
ini_set( 'memory_limit', '1G' );

$csv = new parseCSV();
$csv->delimiter = "\t";
$csv->heading = false;
$csv->enclosure = '';

$csv->parse( $argv[1] );
echo json_encode( $csv->data );

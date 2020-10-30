<?php

require_once __DIR__ . '/index.php';
ini_set( 'memory_limit', '3G' );

foreach ( [ 'fi', 'en' ] as $code ) {
	$parser = new WordNetParser();
	$data = $parser->getData( $code );

	$i = 0;
	while ( count( $data ) ) {
		$i++;
		$out = array_splice( $data, 0, 10000 );
		$json =
			json_encode(
				$out,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
		file_put_contents( "cache/compiled-$code-$i.json", $json );
	}
}

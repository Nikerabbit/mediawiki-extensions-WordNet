<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['minimum_target_php_version'] = '8.4';
$cfg['suppress_issue_types'] = array_values( array_filter(
	$cfg['suppress_issue_types'],
	static fn ( string $issue ): bool => !str_starts_with( $issue, 'PhanDeprecated' )
) );

$cfg['file_list'] = array_merge( $cfg['file_list'], [
	'alias.php',
	'compile.php',
	'import.php',
	'index.php',
	'tsv2json.php',
] );

$cfg['directory_list'][] = '../SemanticMediaWiki/src';
$cfg['directory_list'][] = '../SemanticMediaWiki/includes';
$cfg['exclude_analysis_directory_list'][] = '../SemanticMediaWiki';

$cfg['directory_list'][] = 'vendor/parsecsv/php-parsecsv/src';
$cfg['exclude_analysis_directory_list'][] = 'vendor';

return $cfg;

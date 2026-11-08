<?php

declare( strict_types=1 );

use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths( [
		__DIR__ . '/alias.php',
		__DIR__ . '/compile.php',
		__DIR__ . '/import.php',
		__DIR__ . '/index.php',
		__DIR__ . '/tsv2json.php',
		__DIR__ . '/src',
	] )
	->withPhpSets()
	->withComposerBased()
	->withSkip( [
		// Keep consecutive class member declarations together.
		NewlineBetweenClassLikeStmtsRector::class,
	] )
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		privatization: true,
	);

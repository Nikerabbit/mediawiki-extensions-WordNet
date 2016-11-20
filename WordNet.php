<?php

$wgHooks['ParserFirstCallInit'][] = function( $parser ) {
	$parser->setHook( 'includesubpages', function( $data, $params, $parser ) {
		$title = $parser->getTitle();

		$out = '';
		foreach ( $title->getSubpages() as $subpage ) {
			$out .= '{{:' . $subpage->getPrefixedText() . '}}' . "\n";
		}
		$out = $parser->recursiveTagParse( $out );
		return array( $out, 'noparse' => false );
	} );
};


$wgSpecialPages['WordNet'] = 'SpecialWordNet';
$wgAutoloadClasses['SpecialWordNet'] = __DIR__ . '/SpecialWordNet.php';
$wgExtensionMessagesFiles['WordNetAlias'] = __DIR__ . '/alias.php';

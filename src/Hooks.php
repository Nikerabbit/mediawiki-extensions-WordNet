<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\WordNet;

use Parser;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks {
	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setHook(
			'includesubpages',
			function ( $data, $params, $parser ) {
				$title = $parser->getTitle();

				$out = '';
				foreach ( $title->getSubpages() as $subpage ) {
					$out .= '{{:' . $subpage->getPrefixedText() . '}}' . "\n";
				}
				$out = $parser->recursiveTagParse( $out );
				return [ $out, 'noparse' => false ];
			}
		);
	}
}

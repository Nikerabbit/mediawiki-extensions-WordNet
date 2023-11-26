<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\WordNet;

use MediaWiki\Hook\ParserFirstCallInitHook;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks implements ParserFirstCallInitHook {
	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setHook(
			'includesubpages',
			static function ( $data, $params, $parser ) {
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

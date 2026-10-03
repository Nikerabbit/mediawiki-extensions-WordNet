<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\WordNet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Title\Title;
use Override;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks implements ParserFirstCallInitHook {
	/** @inheritDoc */
	#[Override]
	public function onParserFirstCallInit( $parser ): void {
		$parser->setHook(
			'includesubpages',
			static function ( $data, $params, $parser ): array {
				$title = Title::castFromPageReference( $parser->getPage() );
				if ( $title === null ) {
					return [ '', 'noparse' => false ];
				}

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

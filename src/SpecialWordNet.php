<?php

namespace MediaWiki\Extensions\WordNet;

use Html;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Services\ServicesFactory;
use SMWQueryProcessor;
use SpecialPage;
use Xml;

/**
 * @author Niklas Laxstörm
 * @license GPL-2.0-or-later
 */
class SpecialWordNet extends SpecialPage {
	public function __construct() {
		parent::__construct( 'WordNet' );
	}

	/** @inheritDoc */
	protected function getGroupName(): string {
		return 'pages';
	}

	/** @inheritDoc */
	public function execute( $par ): void {
		$this->setHeaders();
		$this->outputHeader();
		$out = $this->getOutput();

		$par ??= $this->getRequest()->getText( 'query' );
		$form = Html::rawElement(
			'form',
			[ 'action' => wfScript() ],
			Html::hidden( 'title', $this->getPageTitle()->getPrefixedText() ) .
			Html::input( 'query' ) . Xml::submitButton( 'Hae' )
		);
		$out->addHtml( $form );
		if ( $par === '' ) {
			return;
		}

		$keywords = [ $par ];

		$results = [];
		foreach ( $keywords as $keyword ) {
			$results += $this->getSynsets( $keyword );
		}

		if ( !$results ) {
			$sp = SpecialPage::getTitleFor( 'Search' );
			$this->getOutput()->redirect( $sp->getLocalUrl( [ 'search' => $par, 'ns1202' => 1 ] ) );
			return;
		}

		foreach ( $results as $page => $info ) {
			$names = $info['printouts']['Wn/expression'];
			$names = implode( ' | ', $names );
			$desc = $info['printouts']['Wn/description'][0];
			$this->getOutput()->addWikiTextAsInterface( "== [[$page|$names]] ==" );
			$this->getOutput()->addWikiTextAsInterface( "<div class=wordnet-desc>$desc</div>" );
		}
	}

	protected function getSynsets( string $expression ): array {
		$parameters = [
			'limit' => '1000',
		];

		$factory = DataValueFactory::getInstance();
		$prop1 = $factory->newPropertyValueByLabel( 'Wn/expression' );
		$prop2 = $factory->newPropertyValueByLabel( 'Wn/description' );

		$printouts = [
			new PrintRequest(
				PrintRequest::PRINT_PROP, $prop1->getWikiValue(), $prop1
			),
			new PrintRequest(
				PrintRequest::PRINT_PROP, $prop2->getWikiValue(), $prop2
			),
		];

		$query = SMWQueryProcessor::createQuery(
			"[[Category:WordNet]][[Wn/expression::$expression]]",
			SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
			SMWQueryProcessor::SPECIAL_PAGE,
			null,
			$printouts
		);

		$results = ServicesFactory::getInstance()->getStore()->getQueryResult( $query );
		$array = $results->serializeToArray();

		return $array['results'];
	}
}

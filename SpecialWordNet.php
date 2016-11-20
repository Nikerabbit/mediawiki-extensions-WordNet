<?php
/**
 * @file
 * @author Niklas Laxstörm
 * @license GPL%2.0+
 */

use SMW\ApplicationFactory;

class SpecialWordNet extends SpecialPage {
	function __construct() {
		parent::__construct( 'WordNet' );
	}

	protected function getGroupName() {
		return 'pages';
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();
		$out = $this->getOutput();

		$par = $this->getRequest()->getText( 'query', $par );
			$form = Html::rawElement( 'form', array( 'action' => wfScript() ),
				Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) .
				Html::input( 'query' ) .
				Xml::submitButton( 'Hae' )
			);
			$out->addHtml( $form );
		if ( strval( $par ) === '' ) {
			return;
		}

		$keywords = $this->getBaseForms( $par );

		if ( !$keywords ) {
			$keywords = array( $par );
		}

		$results = array();
		foreach ( $keywords as $keyword ) {
			$results += $this->getSynsets( $keyword );
		}

		if ( !$results ) {
			$sp = SpecialPage::getTitleFor( 'Search' );
			$this->getOutput()->redirect( $sp->getLocalUrl( array( 'search' => $par, 'ns1202' => 1 ) ) );
			return;
		}

		foreach ( $results as $page => $info ) {
			$names = $info['printouts']['Wn/expression'];
			$names = implode( ' | ', $names );
			$desc = $info['printouts']['Wn/description'][0];
			$this->getOutput()->addWikiText( "== [[$page|$names]] ==" );
			$this->getOutput()->addWikiText( "<div class=wordnet-desc>$desc</div>" );
		}
	}

	protected function getBaseForms( $input ) {
		return array();

		$query = FormatJSON::encode( array( 'input' => $input ) );
		$output = Http::post( 'http://nike.fixme.fi/wn', array( 'postData' => $query ) );
		$data = FormatJSON::decode( $output, true );

		$output = array();
		foreach ( $data['output'] as $value ) {
			$output[] = str_replace( '#', '', $value );
		}
		$output = array_unique( $output );

		return $output;
	}

	protected function getSynsets( $expression ) {
		$parameters = array(
			'limit' => '1000'
		);

		$prop1 = SMWPropertyValue::makeUserProperty( 'Wn/expression' );
		$prop2 = SMWPropertyValue::makeUserProperty( 'Wn/description' );

		$printouts = array(
			new SMW\Query\PrintRequest(
				SMW\Query\PrintRequest::PRINT_PROP,
				$prop1->getWikiValue(),
				$prop1
			),
			new SMW\Query\PrintRequest(
				SMW\Query\PrintRequest::PRINT_PROP,
				$prop2->getWikiValue(),
				$prop2
			)
		);

		$query = SMWQueryProcessor::createQuery(
			"[[Category:WordNet]][[Wn/expression::$expression]]",
			SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
			SMWQueryProcessor::SPECIAL_PAGE,
			null,
			$printouts
		);

		$results = ApplicationFactory::getInstance()->getStore()->getQueryResult( $query );
		$array = $results->serializeToArray();

		return $array['results'];
	}
}

<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

$env = getenv( 'MW_INSTALL_PATH' );
$IP = $env !== false ? $env : __DIR__ . '/../..';
require_once "$IP/maintenance/Maintenance.php";

require_once __DIR__ . '/index.php';

class WordNetImport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'partition', 'how to partition, default: 1:0', false, true );
		$this->addArg( 'source', 'JSON data' );
	}

	public function execute(): void {
		$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();

		$user = $userFactory->newFromId( 1 );
		$parser = new WordNetParser();

		$data = json_decode( file_get_contents( $this->getArg( 0 ) ), true );
		if ( !$data ) {
			$this->output( "No data.\n" );
			return;
		}

		[ $mod, $remainder ] = explode( ':', $this->getOption( 'partition', '1:0' ), 2 );
		$mod = (int)$mod;
		$remainder = (int)$remainder;

		$i = -1;
		$total = count( $data );
		$this->output( "$total synsets\n" );

		$chars = Title::legalChars();

		foreach ( $data as $id => $synset ) {
			$i++;
			if ( $i % $mod !== $remainder ) {
				continue;
			}

			foreach ( $parser->convertToWiki( $id, $synset ) as $name => $contents ) {
				$name = preg_replace( "/[^$chars]/", '', $name );
				$title = Title::newFromText( $name );

				$page = $wikiPageFactory->newFromTitle( $title );
				$content = ContentHandler::makeContent( $contents, $title );

				$page->newPageUpdater( $user )
					->setContent( SlotRecord::MAIN, $content )
					->saveRevision( CommentStoreComment::newUnsavedComment( '' ) );

				$this->output( "$i: " . $title->getPrefixedText() . "\n" );
			}
		}
	}
}

$maintClass = WordNetImport::class;
require_once RUN_MAINTENANCE_IF_MAIN;

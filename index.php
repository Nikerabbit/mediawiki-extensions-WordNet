<?php

require __DIR__ . '/vendor/autoload.php';
ini_set( 'memory_limit', '1G' );

class WordNetParser {
	public function getData( string $language = 'fi' ): array {
		$wn = [];
		$ip = __DIR__ . '/cache';

		$lang = $language . 'wn';

		echo "Parsing $ip/$lang-synsets.json\n";
		$data = json_decode( file_get_contents( "$ip/$lang-synsets.json" ) );
		foreach ( $data as $row ) {
			$id = self::getSynsetName( $row[0] );
			$wn[$id] = [
				'type' => $row[1],
				'description' => $row[4],
				'wsenses' => [],
				'semrels' => [],
			];
		}

		echo "Parsing $ip/$lang-wsenses.json\n";
		$data = json_decode( file_get_contents( "$ip/$lang-wsenses.json" ) );
		foreach ( $data as $row ) {
			$id = self::getSynsetName( $row[0] );
			$wn[$id]['wsenses'][$row[1]] = [
				'lexrels' => [],
				'transrels' => [],
			];
		}

		echo "Parsing $ip/$lang-semrels.json\n";
		$data = json_decode( file_get_contents( "$ip/$lang-semrels.json" ) );
		foreach ( $data as $row ) {
			$id = self::getSynsetName( $row[0] );
			$tid = self::getSynsetName( $row[1] );
			$type = $row[3];

			$wn[$id]['semrels'][] = [
				'synset' => $tid,
				'type' => $row[3],
			];

			// Hyponyms are not explicitly in the data
			if ( $type === 'hypernym' ) {
				$wn[$tid]['semrels'][] = [
					'synset' => $id,
					'type' => 'hyponym',
				];
			}
		}

		echo "Parsing $ip/$lang-lexrels.json\n";
		$data = json_decode( file_get_contents( "$ip/$lang-lexrels.json" ) );
		foreach ( $data as $row ) {
			$id = self::getSynsetName( $row[0] );
			$tid = self::getSynsetName( $row[2] );
			$wn[$id]['wsenses'][$row[1]]['lexrels'][] = [
				'synset' => $tid,
				'word' => $row[3],
				'lexrel' => $row[5],
			];
		}

		echo "Parsing $ip/fiwn-transls.json\n";
		$data = json_decode( file_get_contents( "$ip/fiwn-transls.json" ) );
		foreach ( $data as $row ) {
			if ( $language === 'fi' ) {
				$id = self::getSynsetName( $row[0] );
				$tid = self::getSynsetName( $row[2] );
				$word = $row[1];
				$foreignWord = $row[3];
				$transrel = $row[4];
			} else {
				$id = self::getSynsetName( $row[2] );
				$tid = self::getSynsetName( $row[0] );
				$word = $row[3];
				$foreignWord = $row[1];
				$transrel = $row[4];
				if ( $transrel === 'hypernym' ) {
					$transrel = 'hyponym';
				} elseif ( $transrel === 'hyponym' ) {
					$transrel = 'hypernym';
				}
			}

			$wn[$id]['wsenses'][$word]['transrels'][] = [
				'synset' => $tid,
				'word' => $foreignWord,
				'transrel' => $transrel,
				'note' => $row[5],
			];
		}

		foreach ( array_keys( $wn ) as $id ) {
			usort(
				$wn[$id]['semrels'],
				static function ( $a, $b ) {
					$type = strcmp( $a['type'], $b['type'] );
					return $type === 0 ? strcmp( $a['synset'], $b['synset'] ) : $type;
				}
			);
		}

		return $wn;
	}

	protected static function getSynsetName( string $id ): string {
		$id = str_replace( 'en-3.0', 'en', $id );
		$lang = substr( $id, 0, 2 );
		return 'WordNet:' . substr( $id, 3 ) . '/' . $lang;
	}

	/**
	 * Example input:
	 *
	 * @code
	 * "WordNet:a00001740/en": {
	 *    "type": "a",
	 *    "description": "(usually followed by `to') having the necessary means or skill or [...]",
	 *    "wsenses": {
	 *        "able": {
	 *            "lexrels": [
	 *                {
	 *                    "synset": "WordNet:a00002098/en",
	 *                    "word": "unable",
	 *                    "lexrel": "antonym"
	 *                },
	 *                {
	 *                    "synset": "WordNet:n05616246/en",
	 *                    "word": "ability",
	 *                    "lexrel": "derivationally related"
	 *                },
	 *                {
	 *                    "synset": "WordNet:n05200169/en",
	 *                    "word": "ability",
	 *                    "lexrel": "derivationally related"
	 *                }
	 *            ],
	 *            "transrels": [
	 *                {
	 *                    "synset": "WordNet:a00001740/fi",
	 *                    "word": "pystyvä",
	 *                    "transrel": "synonym",
	 *                    "note": ""
	 *                },
	 *                {
	 *                    "synset": "WordNet:a00001740/fi",
	 *                    "word": "kykenevä",
	 *                    "transrel": "synonym",
	 *                    "note": ""
	 *                },
	 *                {
	 *                    "synset": "WordNet:a00001740/fi",
	 *                    "word": "taitava",
	 *                    "transrel": "synonym",
	 *                    "note": ""
	 *                }
	 *            ]
	 *        }
	 *    },
	 *    "semrels": [
	 *        {
	 *            "synset": "WordNet:n05200169/en",
	 *            "type": "attribute"
	 *        },
	 *        {
	 *            "synset": "WordNet:n05616246/en",
	 *            "type": "attribute"
	 *        }
	 *    ]
	 * },
	 * @endcode
	 */
	public function convertToWiki( string $id, array $synset ): array {
		$pages = [];

		[ , $language ] = explode( '/', $id, 3 );

		foreach ( $synset['wsenses'] as $word => $info ) {
			$pages["$id/$word"] = self::formatTemplate(
				'wn/word',
				[
					'language' => $language,
					'value' => $word,
					'lexrels' => self::mapFormat( 'wn/lexrel', $info['lexrels'] ),
					'transrels' => self::mapFormat( 'wn/transrel', $info['transrels'] ),
				]
			);
		}

		$pages[$id] = self::formatTemplate(
			'wn/synset',
			[
				'language' => $language,
				'type' => $synset['type'],
				'description' => $synset['description'],
				'semrels' => self::mapFormat( 'wn/semrel', $synset['semrels'] ),
			]
		);

		return $pages;
	}

	public static function formatTemplate( string $name, array $parameters ): string {
		$params = '';
		foreach ( $parameters as $key => $value ) {
			// If no params, the new line never gets added and we get {{daa}}
			if ( $params === '' ) {
				$params = "\n";
			}
			$params .= "|$key=$value\n";
		}

		return '{{' . $name . $params . '}}';
	}

	public static function mapFormat( string $template, array $data ): string {
		return implode(
			"\n",
			array_map(
				static function ( $rel ) use ( $template ) {
					return WordNetParser::formatTemplate( $template, $rel );
				},
				$data
			)
		);
	}
}

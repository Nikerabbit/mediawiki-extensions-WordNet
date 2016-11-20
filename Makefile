source := fiwn-2.0/rels

.PHONY: download unzip convert compile

all: download unzip convert compile

cache/%.json: $(source)/%.tsv
	 php tsv2json.php $^ > $@

wordnet.zip:
	wget http://www.ling.helsinki.fi/cgi-bin/fiwn/download?fiwn_rels_fi.zip -O wordnet.zip

fiwn-2.0:
	unzip wordnet.zip

cache:
	mkdir cache


download: wordnet.zip

unzip: fiwn-2.0

sources := $(wildcard $(source)/*.tsv)
targets := $(sources:$(source)%.tsv=cache%.json)
convert: cache $(targets)

compile: cache/compiled-fi.json cache/compiled-en.json

cache/compiled-%.json:
	cd cache
	php compile.php

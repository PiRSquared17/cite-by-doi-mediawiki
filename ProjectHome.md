This [MediaWiki](http://http://www.mediawiki.org) extension allows citing documents, which have an assigned Digital Object Identifier (DOI), by simply supplying the DOI.

It creates a `<doi></doi>` pseudo-tag, which is parsed at the time the article is saved/previewed and replaced with proper document information. The document meta-data is retrieved from http://data.crossref.org and is formatted in the AMA citation style.

This extension works very well with the [Cite extension](http://www.mediawiki.org/wiki/Extension:Cite).

It is important to note that the parsing of the DOI tag, unlike typical mediawiki tags, is done at the time the page is saved, which keeps the number of HTTP requests to the crossref.org servers to the minimum.
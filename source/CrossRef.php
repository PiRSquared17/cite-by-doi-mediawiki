<?php
/*
 * CrossRef
 *
 * @author Aurimas Vinckevicius
 * @email	aurimas.dev@gmail.com
 * @license GNU General Public Licence 3.0 or later
 * 
 * Retrieves document metadata when provided with a valid DOI.
 * Queries crossref.org for information.
 *
 * Errors are reported in the returned array in 'error' and 'errorNo' indeces.
 *
 * The returned array contains as much information as is returned by the server
 * but may not be complete. The following structure is followed:
 *
 * $meta = (
 * 		['authors'] = (
 * 				['first'] = (
 * 						['first_name'] = <string>,	//contains all the given names
 * 						['last_name'] = <string>
 * 				),
 * 				['additional'] = (
 * 						[] = (							//number indexed array
 * 								['first_name'] = <string>,
 * 								['last_name'] = <string>
 * 						),
 * 						...
 * 				)
 * 		),
 * 		['journal'] = (
 * 				['full_title'] = <string>,
 * 				['abbrev_title'] = <string>		//this is only set if full title is available
 *		),
 *		['title'] = <string>,		//the title of the article
 *		['pages'] = <string>,		//if both start and end pages are given this is
 *														//in the format start-end, otherwise this is
 *														//equal to start page
 *		['volume'] = <string>,
 *		['issue'] = <string>,
 *		['pub_date'] = (
 *				['online'|'print'] = (		//both online and print dates may be given
 *						['year'] = <string>,
 *						['month'] = <string>,
 *						['day'] = <string>
 *				)
 *		),
 *		['doi'] = <string>
 *	)
 */

class CrossRef
{
	private static $query_url = 'http://data.crossref.org/%s';

	public static function doiToMeta($doi)
	{
    //blank DOIs are invalid
    if(strlen($doi)<1)
    {
      return array('error' => 'Malformed DOI',
                  'errorNo' => 2);
    }

    $url = sprintf(self::$query_url,$doi);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/unixref+xml'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($ch);
    curl_close($ch);

    //DOI not found
    if(strpos($ret,'Malformed DOI') !== FALSE)
    {
      return array('error' => 'Malformed DOI',
                   'errorNo' => 2);
    }
		elseif(strpos($ret,'<?xml') === FALSE)
		{
			return array('error' => 'DOI not found',
                   'errorNo' => 3);
		}

    try
    {
      $xml = new SimpleXMLElement($ret, LIBXML_NOBLANKS);
    }
    catch(LibXMLError $e)
    {
      user_error("CrossRef: Encountered error while parsing XML data from $url " .
                 "({$e->code}) {$e->message}");
      return array('error' => 'Internal error.',
                   'errorNo' => 1);
    }

    //make metadata array
    $meta = array();
    $record = $xml->doi_record->crossref->journal;

    //authors
    foreach($record->journal_article->contributors->person_name as $v)
    {
      if($v->attributes()->contributor_role == 'author')
      {
        if(!isset($meta['authors']))
        {
          $meta['authors'] = array();
        }

        if($v->attributes()->sequence == 'first')
        {
          $meta['authors']['first'] = array('first_name' => (string)$v->given_name,
                                            'last_name'  => (string)$v->surname);
        }
        else
        {
					if(!isset($meta['authors']['additional']))
					{
						$meta['authors']['additional'] = array();
					}
          $meta['authors']['additional'][] = array('first_name' => (string)$v->given_name,
                                                   'last_name'  => (string)$v->surname);
        }
      }
    }

    //journal title
    if($record->journal_metadata->full_title)
    {
      $meta['journal'] = array('full_title' => (string)$record->journal_metadata->full_title);
      if($record->journal_metadata->abbrev_title)
      {
        $meta['journal']['abbrev_title'] = (string)$record->journal_metadata->abbrev_title;
      }
    }

    //article title
    if($record->journal_article->titles->title)
    {
      $meta['title'] = (string)$record->journal_article->titles->title;
    }

    //pages
    if($record->journal_article->pages)
    {
      $meta['pages'] = (string)$record->journal_article->pages->first_page .
												(((string)$record->journal_article->pages->last_page)?'-' . (string)$record->journal_article->pages->last_page:'');
    }

    //volume
    if($record->journal_issue->journal_volume)
    {
      $meta['volume'] = (string)$record->journal_issue->journal_volume->volume;
    }

    //issue
    if($record->journal_issue->issue)
    {
      $meta['issue'] = (string)$record->journal_issue->issue;
    }

    //publication dates
    foreach($record->journal_article->publication_date as $v)
    {
      if(!isset($meta['pub_date']))
      {
        $meta['pub_date'] = array();
      }
      $meta['pub_date'][(string)$v->attributes()->media_type] = array();
			//create a shortcut
			$date = &$meta['pub_date'][(string)$v->attributes()->media_type];

			if($v->year)
			{
				$date['year'] = (string)$v->year;
				if($v->month)
				{
					$date['month'] = (string)$v->month;
					if($v-day)
					{
						$date['day'] = (string)$v->day;
					}
				}
			}
			unset($date);
    }

    //doi
    if($record->journal_article->doi_data->doi)
    {
      $meta['doi'] = (string)$record->journal_article->doi_data->doi;
    }

    return $meta;
  }
}
<?php
/*
 * CrossRef
 *
 * @author Aurimas Vinckevicius
 * @email	aurimas.dev@gmail.com
 * @license GNU General Public Licence 3.0 or later
 * 
 * Given the output of CrossRef::doiToMeta, formats the array into selected
 * citation style.
 *
 * New styles may be defined and must be named as follows:
 * style<StyleName>(array $metadata)
 */

class BibFormat
{
  public static function style(array $meta, $style = 'AMA')
  {
    $func = 'self::style' . $style;
    if(!is_callable($func))
    {
      user_error("BibFormat: Invalid style specified to ::style() method. Style '$style' is not defined.");
      //fallback to the default
      $func = 'self::style' . 'AMA';
    }

    return call_user_func($func, $meta);
  }

  //The Americal Medical Association citation style
  //http://www.amamanualofstyle.com
  public static function styleAMA(array $meta)
  {
    $str = '';

    //format names according to AMA style
    function formatName(array $name)
    {
      return $name['last_name'] . ' ' . preg_replace('/\b([A-Z]).*?(?:\s|$)+/','$1',$name['first_name']);
    }

    //if less than 6 authors, list all; otherwise, list first 3 followed by et al
    if(isset($meta['authors']) && isset($meta['authors']['first']))
    {
      $str .= formatName($meta['authors']['first']);
      
      if(isset($meta['authors']['additional']))
      {
        if(count($meta['authors']['additional'])<6)
        {
          foreach($meta['authors']['additional'] as $v)
          {
            $str .= ', ' . formatName($v);
          }
        }
        else
        {
          //only include 2 additional authors
          $c = 2;
          foreach($meta['authors']['additional'] as $v)
          {
            $str .= ', ' . formatName($v);
            $c--;
            if($c<=0)
            {
              break;
            }
          }
          $str .= ', et al';
        }
      }
      $str .= '.';
    }

    if(isset($meta['title']))
    {
      $str .= (($str)?' ':'') . $meta['title'] . '.';
    }

    if(isset($meta['journal']))
    {
      $str .= (($str)?' ':'') . "''" .
              ((isset($meta['journal']['abbrev_title']))?$meta['journal']['abbrev_title']:$meta['journal']['full_title']) .
              "''.";
    }

    //at this point there has to be something set
    if(!$str)
    {
      return '';
    }

    if(isset($meta['pub_date']))
    {
      $str .= ' ';
      if(isset($meta['pub_date']['print']))
      {
        $str .= $meta['pub_date']['print']['year'];
      }
      else
      {
        $str .= $meta['pub_date']['online']['year'];
      }
      $str .= (isset($meta['volume']))?';':'.';
    }

    if(isset($meta['volume']))
    {
      if(substr($str,-1) != ';')
      {
        $str .= ' ';
      }
      $str .= $meta['volume'];

      if(isset($meta['issue']))
      {
        $str .= '(' . $meta['issue'] . ')';
      }

      if(isset($meta['pages']))
      {
        $str .= ':' . $meta['pages'];
      }

      $str .= '.';
    }

    if(isset($meta['doi']))
    {
      $str .= ' [http://dx.doi.org/' . $meta['doi'] . ' doi:' . $meta['doi'] . '].';
    }

    return $str;
  }
}
?>
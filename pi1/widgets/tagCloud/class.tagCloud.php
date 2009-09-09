<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 snowflake <info@snowflake.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tagCloud extends tslib_pibase {
	var $localPiVars;
	var $globalPiVars;
	var $conf;
	var $prefixId = 'tagCloud';
	var $scriptRelPath = 'pi1/widgets/tagCloud/class.tagCloud.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $_tags; 			// array( [tag]=>(string)NAME, [count]=>(int)COUNT )
	var $link; 				// string
	var $minsize; 			// float
	var $maxsize; 			// float
	var $maxTagsToShow;     // int
	var $unit; 				// string
	var $thresholds; 		// int
	var $mincolor; 			// string
	var $maxcolor; 			// string
	var $count; 			// int
	var $sort; 				// string
	var $descasc; 			// string
	var $link_title; 		// string
	var $distribution; 		// string
	var $string; 			// boolean
	var $tag_array = array();


	/**
	 * The main method of the PlugIn
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * 
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prefixId];
		$this->conf = $conf;
		$this->init();
		$this->link = '/%tag%';
		$this->minsize = $conf['minFontSize'];
		$this->maxsize = $conf['maxFontSize'];
		$this->maxTagsToShow = $conf['maxTagsToShow'];
		$this->unit = $conf['unit'];
		$this->thresholds = 8;
		$this->mincolor = $conf['minColor'];
		$this->maxcolor = $conf['maxColor'];
		$this->count = 0;
		$this->sort = 'tag';
		$this->descasc = 'asc';
		$this->link_title = '%count% items';
		$this->distribution = $conf['renderingAlgorithm'];
		$this->string = FALSE;
		
		global $TSFE;
		mb_internal_encoding($TSFE->renderCharset);

		$this->pi_setPiVarDefaults();

		$this->_tags  = $this->getTags_array();
		$content = $this->calculateTagCloud();

		$title = $this->pi_getLL('tagCloudTitle');
		return t3blog_div::getSingle(array('data'=>$content,'title'=>$title),'globalWrap');
	}

	/**
	 * Initial Method
	 */
	function init(){
		$this->pi_loadLL();
	}

	/**
	 * Generate a tag clound array. It looks like this:
	 * array(
	 * 	array(
	 * 		'tag'=>"snowflake",
	 * 		'count'=>60,
	 * 	),
	 * 	array(
	 * 		'tag'=>"Typo3",
	 * 		'count'=>45,
	 * 	),
	 * 	array(
	 * 		'tag'=>"Opensource",
	 * 		'count'=>25 ,
	 * 	),
	 * );
	 *
	 * @return Array[]	Which tag occures how much times.
	 */
	function getTags_array(){
		$table = 'tx_t3blog_post';
		$where = 'pid = '.t3blog_div::getBlogPid();
		$where.= $this->cObj->enableFields($table);
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$table, $where);
		$befor_value = array();
		$tags = array();
		$i_count = 0;
		while ($resPost = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($posts)) {
			$tags = array_merge($tags, explode(',' , mb_strtolower($resPost['tagClouds'])));
		}
		$tagArray = array();
		foreach ($tags as $tag) {
			// makes lowercase tags
			$tag = mb_strtolower(trim($tag));

			if (isset($tagArray[$tag])) {
				// count to display the quantity of tags
				$tagArray[$tag]['count']++;
			} else {
				$tagArray[$tag] = array(
					'tag' => $tag,
					'count' => 1,
				);
			}
		}
		
		return array_values($tagArray);
	}
	
	
	/**
	 * main function to calculate tags size and color
	 *
	 * @return string cloud
	 */
	function calculateTagCloud() {
		if(  !is_array( $this->_tags )|| empty( $this->_tags[0]['tag'] )|| empty( $this->_tags[0]['count'] )|| empty( $this->minsize )|| empty( $this->maxsize )|| empty( $this->unit )|| !is_string( $this->unit )){
			return FALSE;
		}
			
		$_tags = $this->_tags;
		
		if( $this->count>0 && count($_tags) > $this->count ){
			usort( $_tags, array( $this, "CountCompare" ) );
			array_splice( $_tags, $this->count );
		}
		
		if( mb_strtolower($this->sort) == 'tag' ){
			$_tags = $this->ArraySort2D( $_tags, 'tag', $this->descasc );
		} elseif( mb_strtolower($this->sort) == 'count' ){
					usort( $_tags, array( $this, "CountCompare" ) );
		} else {
					shuffle( $_tags );
		}

		foreach( $_tags as $k=>$_v ) {
				if( empty($maxcount) || $_v['count']>$maxcount ) $maxcount=$_v['count'];
				if( empty($mincount) || $_v['count']<$mincount ) $mincount=$_v['count'];
		}
		
		if( in_array( mb_strtolower($this->unit), array('em','ex','cm','in') ) ) {
			$this->maxsize *= 100;
			$this->minsize *= 100;
		}
		
		if( empty($this->thresholds) ) {
			$this->thresholds = $this->maxsize - $this->minsize + 1;		
		} 
		
		if( !in_array( mb_strtolower($this->unit), array('em','ex','cm','in','pt','px','mm','pc','%') ) ) {
			$this->maxsize = $this->minsize + $this->thresholds - 1;
		} elseif( !empty($this->mincolor) && !empty($this->maxcolor) ) {
				$_colors = $this->GetColorThresholds( $this->mincolor, $this->maxcolor, $this->thresholds );
		}
		
		$_s = array();
		$_c = array();
		$cloud = '';
		
		// Added 23.12.2008 | Thomas Imboden <timboden@snowflake.ch> 
		// Begin
		// to sort the array by count values
		function sortByCountValue ($a, $b) {
			return ($b['count'] > $a['count'] ? 1 : -1);		
		}
		
		uasort($_tags, 'sortByCountValue');
		array_splice($_tags, $this->maxTagsToShow);
		// End
		
		foreach($_tags as $k=>$_v ) {
			if( empty($_s[$_v['count']]) ) {
				if( $this->distribution=='lin' ) {
				$_s[$_v['count']] = $this->GetTagSizeLinear( $_v['count'], $mincount, $maxcount, $this->minsize, $this->maxsize, $this->thresholds );
					if( $_colors ) {
					$_c[$_v['count']] = $this->GetTagSizeLinear( $_v['count'], $mincount, $maxcount, 1, $this->thresholds, $this->thresholds );
					}
				}
				else {
				$_s[$_v['count']] = $this->GetTagSizeLogarithmic( $_v['count'], $mincount, $maxcount, $this->minsize, $this->maxsize, $this->thresholds );
					if( $_colors ) {
					$_c[$_v['count']] = $this->GetTagSizeLogarithmic( $_v['count'], $mincount, $maxcount, 1, $this->thresholds, $this->thresholds );
					}
				}
			}
			
			$l 	= str_replace( '%tag%', $_v['tag'], $this->link );
			$lt = str_replace( '%tag%', $_v['tag'], $this->link_title );
			$lt = str_replace( '%count%', $_v['count'], $lt );
			if( in_array( mb_strtolower($this->unit), array('pt','px','mm','pc','%') ) ) :
			$s 	= "style=\"font-size:".$_s[$_v['count']].mb_strtolower($this->unit).";".($_colors?"color:".$_colors[$_c[$_v['count']]-1].";":"")."\"";
			elseif( in_array( mb_strtolower($this->unit), array('em','ex','cm','in' ) ) ) :
			$s 	= "style=\"font-size:".($s_[$_v['count']]/=100).mb_strtolower($this->unit).";".($_colors?"color:".$_colors[$_c[$_v['count']]-1].";":"")."\"";
			else :
			$s 	= "class=\"".$this->unit."-".$_s[$_v['count']]."\"";
			endif;

			$l 	= substr($l, 1, strlen($l));
			if($_v['tag']){
				$cloud .= t3blog_div::getSingle(array('text'=>$this->getLink($_v['tag'], array('tags' => $l), $s.(empty($lt)?"":" title=\"".$lt."\""))),'item');
			}
		}
		
		$cloud = t3blog_div::getSingle(array('text'=>$cloud),'list');
		if( $this->string ) return $cloud;
		else return $cloud;
	}


	/**
	 * Gets link to cloud
	 * 
	 * @param 	string		$str: name of the cloud
	 * @param	array		$overrulePIVars
	 * @param	string		$additionalATagParams
	 * 
	 * @return	link to cloud
	 * 
 	*/ 
	function getLink($str, $overrulePIVars, $additionalATagParams) {
		$overrulePIVars = t3lib_div::array_merge_recursive_overrule($this->piVars, $overrulePIVars);
		$conf = array(
			'additionalParams' => t3lib_div::implodeArrayForUrl('tx_t3blog_pi1',array('blogList' => $overrulePIVars),'',1),
			'ATagParams' => $additionalATagParams,
			'parameter' => t3blog_div::getBlogPid(),
			'useCacheHash' => 1
		);

		return $this->cObj->typoLink($str, $conf, 1);
	}
	
	
	/**
	 * Sorts an array
	 * 
	 * @param 	array		$_array: array to be sorted
	 * @param	string		$key
	 * @param	string		$descasc
	 * 
	 * @return	sorted array
	 * 
 	*/ 
	function ArraySort2D($_array, $key, $descasc='asc' ) {
		if( !is_array($_array) || empty($_array) ) return FALSE;
		$_a = array();
		foreach( $_array as $k => $_v ){
			$_a[$k]=$_v[$key];
		}
		natcasesort($_a);
		if( mb_strtolower($descasc)=='desc' ) {
			$_a = array_reverse($_a,TRUE);
		}
		$_arr = array();
		foreach($_a as $k => $v){
			array_push( $_arr, $_array[$k] );
		}
		return $_arr;
	}
	
	
	/**
	 * Compares counts
	 * 
	 * @param 	array		$_a
	 * @param	array		$_b
	 * 
	 * @return	amount 
 	*/ 
	function CountCompare($_a, $_b) {
		if( $_a['count']==$_b['count'] ) {
			return strnatcasecmp( $_a['tag'], $_b['tag'] );
		} else{
			$da = ($this->descasc=='asc') ? 1 : -1;
			return ( $_a['count'] < $_b['count'] ) ? -1*$da : 1*$da;
		}
	}
	
	
	/**
	 * Gets tag size
	 * 
	 * @param 	string		$count
	 * @param	string		$mincount
	 * @param	string		$maxcount
	 * @param	string		$minsize
	 * @param	string		$maxsize
	 * @param	string		$thresholds
	 * 
	 * @return	rounded tag size 
 	*/ 
	function GetTagSizeLinear($count, $mincount, $maxcount, $minsize, $maxsize, $thresholds) {
		if( !is_int($thresholds) || $thresholds<2 ) {
			$thresholds = $maxsize-$minsize;
			$threshold = 1;
		} else {
			$threshold = ($maxsize-$minsize)/($thresholds-1);
		}
		if(intval( $maxcount-$mincount ) != 0 ){
			$a = round( ( ($thresholds-1)*($count-$mincount) ) / ( $maxcount-$mincount ) ,0);
		}else{
			$a = round( ( ($thresholds-1)*($count-$mincount) ));
		}
		return round($minsize+$a*$threshold,0);
	}


	/**
	 * Gets tag size logarithmic
	 * 
	 * @param 	string		$count
	 * @param	string		$mincount
	 * @param	string		$maxcount
	 * @param	string		$minsize
	 * @param	string		$maxsize
	 * @param	string		$thresholds
	 * 
	 * @return	rounded tag size 
 	*/ 
	function GetTagSizeLogarithmic($count, $mincount, $maxcount, $minsize, $maxsize, $thresholds) {
		if( !is_int($thresholds) || $thresholds<2 ) {
			$thresholds = $maxsize-$minsize;
			$threshold = 1;
		} else {
			$threshold = ($maxsize-$minsize)/($thresholds-1);
		}
		$a = $thresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
		return round($minsize+round($a)*$threshold);
	}


	/**
	 * Gets color of threshold
	 * 
	 * @param 	string		$c1
	 * @param	string		$c2
	 * @param	string		$thresholds
	 * 
	 * @return	color of threshold
 	*/ 
	function GetColorThresholds($c1, $c2, $thresholds) {
		$c1 = str_replace("#","",$c1);
		$c2 = str_replace("#","",$c2);
		if(  !preg_match('=^[a-f0-9]{6}$=i',$c1) || !preg_match('=^[a-f0-9]{6}$=i',$c2) )
		return FALSE;
		$_c1 = array( hexdec(substr($c1,0,2)), hexdec(substr($c1,2,2)), hexdec(substr($c1,4,2)) );
		$_c2 = array( hexdec(substr($c2,0,2)), hexdec(substr($c2,2,2)), hexdec(substr($c2,4,2)) );
		$_colors = array();
		for( $t=0; $t<$thresholds && $thresholds>1; $t++ ) {
			$color = "#";
			foreach( $_c1 as $k => $v ) {
				$delta = ($_c2[$k]-$_c1[$k])/($thresholds-1);
				$b = round($_c1[$k]+$t*$delta);
				if( $b<0 ) $b=0;
				if( $b>255 ) $b=255;
				$color .= (strlen($b)==1)?"0".dechex($b):dechex($b);
			}
		array_push( $_colors, $color );
		}
		return $_colors;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/tagCloud/class.tagCloud.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/tagCloud/class.tagCloud.php']);
}

?>

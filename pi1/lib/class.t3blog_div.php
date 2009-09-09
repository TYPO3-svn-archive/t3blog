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



class t3blog_div {
	
	/**
	 * Parses data through typoscript.
	 *
	 * @param	String[]	$data: Data which will be passed to the typoscript.
	 * @param	String		$ts: The typoscript which will be called.
	 */
	function getSingle($data, $ts) {
		
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		

		//Set the data array in the local cObj. This data will be available in the ts. E.G. {field:[fieldName]} or field = [fieldName]
		
		$cObj->data = $data;

		//Parse and return the result.
		return $cObj->cObjGetSingle($this->conf[$ts], $this->conf[$ts.'.']);
	}

	/**
	 * Checks if it is a real email
	 *
	 * @param string $email
	 * @return true if error
	 */
	function checkEmail($email){
		$error = false;
		if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",$email)){
			$error = true;
		}
		return $error;
	}

	/**
	 * Checks if it is a real http:// url
	 * adds a http://if there is none.
	 *
	 * @param string $url
	 * @return true if error
	 */
	function checkExternalUrl($url){
		$error = false;
		if(!preg_match('((http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?)',$url)){
			$url = 'http://'.$url;
			if(!preg_match('((http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?)',$url)){
				$error = true;
			}
		}
		return $error;
	}

	/**
	 * Returns the username (realname) from be_user by a uid
	 *
	 * @param  $uid
	 * @return Realname (string)
	 */
	function getAuthorByUid($uid){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'realName',		// SELECT ...
			'be_users',		// FROM ...
			'uid = '.t3lib_div::intval_positive($uid)		// WHERE ...
		);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['realName'];

	}
	
	/**
	 * returns the Category string by a single uid
	 *
	 * @param int  $uid
	 * @return string name
	 */
	function getCategoryNameByUid($uid){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'catname',		// SELECT ...
			'tx_t3blog_cat',		// FROM ...
			'uid = '.intval($uid),		// WHERE ...
			'uid',		// GROUP BY ...
			'uid',		// ORDER BY ...
			'0,1'		// LIMIT ...
		);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row['catname'];
		
	}
	

	/**
	 * returns the page browser of given table.
	 *
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param int $numOfEntries	total of all elements of a table
	 * @param string $ident		identifier for pointer. e.g. recently editet as more than 1 page browser on the site.
	 * @param $prefixId $todo		Functionname of what-is-to-do-after-page-click-function
	 *
	 * @return string HTML-Content 		The browser
	 */
	function getPageBrowser($numOfEntries, $ident, $prefixId, $llarray, $piVars,$conf, $limit = 10, $maxPages = 20)	{
		$this->limit = $limit;
		$this->maxShownPages = $maxPages;
		$this->prefixId = $prefixId;
		$this->conf  = $conf;

		$piBase = t3lib_div::makeInstance('tslib_pibase');
		$piBase->cObj = t3lib_div::makeInstance('tslib_cObj');

		$pages = $numOfEntries/$this->limit;

		$definitivePages = round($pages);

		//e.g. pages are 4.4 it rounds to 4 but we need 5.
		if($definitivePages < $pages)	{
			$definitivePages = $definitivePages+1;
		}

		//now we need the real number of pages. => pointervalue

		$items = '';
		$pointer = $_GET[$ident.'_pointer']?$_GET[$ident.'_pointer']:0;
		//previous link
		if($pointer > 0)	{
			$previous = (($pointer-$this->maxShownPages)<0)?0:$pointer-$this->maxShownPages;
			$previous_number = (($pointer-$this->maxShownPages)<0)?$pointer:$this->maxShownPages;

			$tempdata = array(
					'link' => $piBase->pi_linkTP_keepPIvars($llarray['previous'].' '.$previous_number, array($ident.'_pointer' => $previous), 1, 0, 0),
					'pbitemclass' => 'previous',
			);

			$items.= t3blog_div::getSingle($tempdata, 'pageBrowserElement');
		}

		//Main paging from
		if(($pointer+1) < $definitivePages)	{

				$next = (($pointer+$this->maxShownPages)>$definitivePages)?($definitivePages-1):$pointer+$this->maxShownPages;
				$next_number = (($pointer+$this->maxShownPages)>$definitivePages)?($definitivePages-1-$pointer):$this->maxShownPages;
				$tempdata = array(
					'link' => $piBase->pi_linkTP_keepPIvars($llarray['next'].' '.$next_number, array($ident.'_pointer' => $next), 1, 0, 0),
					'pbitemclass' => 'next',
				);

				$endOfItems = t3blog_div::getSingle($tempdata, 'pageBrowserElement');
		}

		$paging = '';
		//Gets the Range of the Browser.
		$diff = $this->maxShownPages/2;
		if($definitivePages > $this->maxShownPages)	{
			if(!$start)	{
				$start = ($pointer > ($this->maxShownPages/2))?$pointer-$diff:0;
			}

			$end = $start+$this->maxShownPages;

			if(($start+$this->maxShownPages) >= $definitivePages)	{
				$end = $definitivePages;
				$start = $definitivePages-$this->maxShownPages;
			}
		} else {
			$start = 0;
			$end = $definitivePages;
		}

		//whole main paging stuff form $start to $end (is defined above)
		$i = $start;
		for($i = $start; $i < $end; $i++)	{
			$tmpdata = array(
				'link' => $piBase->pi_linkTP_keepPIvars(($i+1), array($ident.'_pointer' => $i), 1, 0, 0),
				'pbitemclass' => ($pointer==$i)?'page cur':'page',
			);

			$items.= t3blog_div::getSingle($tmpdata, 'pageBrowserElement');
		}

		$items = $items.$endOfItems;

		$data['pbitems'] = $items;
		//return nothing if there is only 1 page.
		if($i>1){
			$return = t3blog_div::getSingle($data, 'pageBrowser');
		}else {
			$return = '';
		}
		return $return;
	}

	/**
	 * Sets an alternative blog Pid. 
	 *
	 * @param Pid of the record storage page
	 */
	function setAlternativeBlogPid($pid){
		$GLOBALS['alternativeBlogPid'] = $pid;
	}
	
	/**
	 * returns the blog storage folder pid
	 *
	 * @return int pid
	 */
	function getBlogPid(){
		// get pid
		if(isset($GLOBALS['alternativeBlogPid']) && $GLOBALS['alternativeBlogPid'] > 0){
			$pid = $GLOBALS['alternativeBlogPid'];
		}else{
			$pid = $GLOBALS['TSFE']->id;	
		}
		
		// get the Rootline
		$rootline = array_reverse($GLOBALS['TSFE']->tmpl->rootLine);

		$tmp->cObj = t3lib_div::makeInstance('tslib_cObj');
		// go throu rootline until a blogPid is found
		foreach ($rootline as $page){
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',		// SELECT ...
				'pages',		// FROM ...
				'uid = '.$page['uid'].' AND module = \'t3blog\' '.$tmp->cObj->enableFields('pages'),		// WHERE ...
				'uid',		// GROUP BY ...
				'uid',		// ORDER BY ...
				'0,1'		// LIMIT ...
			);
			if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){

				$pid = $row['uid'];
				return $pid;
			}
		}
		return $pid;
	}
	
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_div.php']);
}
?>
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
 * @author		snowflake <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class listFunctions extends blogList {
	var $prefixId      = 'listFunctions';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.listFunctions.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = false;
	var $prevPrefixId = 'blogList';
	var $localPiVars;
	var $globalPiVars;
	
	
	/**
	 * The main method of the PlugIn
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId]; //blogList pvars
		$this->conf = $conf;
		$this->init();
        
		//example pivar for communication interface
		//$this->piVars['widgetname']['action'] = "value";		
		$content = $this->getListItems();
		
		$data = array(
			'pageBrowser' 	=> t3blog_div::getPageBrowser($this->getListItems(true), 'tx_t3blog_post', $this->prefixId, array('previous' => $this->pi_getLL('previous'), 'next' => $this->pi_getLL('next')), $this->localPiVars, $this->conf, $this->conf['numberOfRecords'], $this->conf['maxPages']),
			'listItems'		=> $content,
		);
		
		return t3blog_div::getSingle($data,'list');
	}


	/**
	 * lists the blog entries and prepares the data.
	 * possible piVars: groupBy, orderBy, orderByDir, catIn, datefrom, dateto, pointer
	 * 
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 * 
	 * @param 	boolean 	$justNumOfItems
	 * @param 	boolean 	$justItemArray
	 *
	 * @return string with the content html.
	 */
	function getListItems($justNumOfItems = false, $justItemArray = false){
		$content = '';
		// prepare SQL statement for LIST view
		$fields = 'tx_t3blog_post.uid AS uid,tx_t3blog_post.pid,tx_t3blog_post.tstamp,tx_t3blog_post.crdate,tx_t3blog_post.cruser_id,tx_t3blog_post.title,tx_t3blog_post.author,tx_t3blog_post.date,tx_t3blog_post.allow_comments,tx_t3blog_post.cat, be_users.email, be_users.uid AS useruid, be_users.username, be_users.realName, be_users.tx_t3blog_avatar';
		$table = 'tx_t3blog_post';
		$additionalTables = ' JOIN be_users ON be_users.uid = tx_t3blog_post.author ';
		$where = 'tx_t3blog_post.pid = '. $GLOBALS['TSFE']->id; // only from current page
		$where .= $this->localcObj->enableFields($table);
		
		// Add category filter
		if($this->localPiVars['category'])	{
			$additionalTables .= ', tx_t3blog_post_cat_mm as mm';
			$uidList = $this->localPiVars['category'];
			$this->getCommaSeparatedCategories($uidList, $uidList);
			$where .= ' AND tx_t3blog_post.uid = mm.uid_local AND mm.uid_foreign IN ('. $GLOBALS['TYPO3_DB']->quoteStr($uidList, 'tx_t3blog_post_cat_mm'). ')';
		}
		
		// Add filter by Author
		if($this->localPiVars['author']){
			$where .= ' AND tx_t3blog_post.author = '.intval($this->localPiVars['author']);
		}

		// Add tagged search
		if($this->localPiVars['tags']){ // if tagCloud link has been clicked display tag
			$tags = $this->localPiVars['tags'];
			$where .= ' AND (tagClouds LIKE \'%'. $GLOBALS['TYPO3_DB']->quoteStr($tags,$table). '%\') ';
			$tagtitle = 'Tag '. $tags;
			$back = $this->pi_getLL('back');
		} else {
			$tagtitle = '';
			$back = '';
		}
		
		// Add search to where
		if($this->globalPiVars['sword']){
			$searchWord = $this->globalPiVars['sword'];
			$searchWord = $GLOBALS['TYPO3_DB']->quoteStr($searchWord,$table);
			
			// add search over tt_content		
			$additionalTables .= ' JOIN tt_content ON (tt_content.irre_parentid = tx_t3blog_post.uid AND tt_content.irre_parenttable = \'tx_t3blog_post\')';
			
			$where .= ' AND (';
			$where .= ' tt_content.header LIKE \'%'.$searchWord.'%\' ';
			$where .= ' OR tt_content.bodytext LIKE \'%'.$searchWord.'%\' ';
			$where .= ' OR tx_t3blog_post.title LIKE \'%'.$searchWord.'%\' ';
			$where .= ' OR tx_t3blog_post.tagClouds LIKE \'%'.$searchWord.'%\' ';
			$where .= ' ) ';
			
			
		}		
		
		// SET  group by and order by
		$groupBy = 'tx_t3blog_post.uid';
		$orderBy = 'tx_t3blog_post.date DESC';
		if($this->localPiVars['groupBy']){	//set the group by value
			$groupBy = $GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['groupBy'],$table);
		}
		if($this->localPiVars['orderBy']){	//set order by
			$orderBy = $GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['orderBy'],$table);
		}
		if($this->localPiVars['orderByDir'] 	//set order by dir
			&& (mb_strtolower($this->localPiVars['orderByDir'])=='asc'
			|| mb_strtolower($this->localPiVars['orderByDir'])=='desc')){
			$orderBy .= ' '. mb_strtoupper($GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['orderByDir'],$table));
		}
		
		//where additions
		$this->localPiVars['catIn'];
		
		//add DATEfrom and DATEto ->  where
		//convert url paramter datefrom and dateto in timestamp
		list ($yearFromdate, $monthFromdate, $dayFromdate) = explode('-', $this->localPiVars['datefrom']);
		if ($dayFromdate != ''){
			$fromdate = mktime(0, 0, 0, $monthFromdate, $dayFromdate, $yearFromdate);
		}
		
		list ($yearTodate ,$monthTodate, $dayTodate ) = explode('-', $this->localPiVars['dateto']);		
		if ($dayTodate != ''){
			$todate = mktime(0, 0, 0, $monthTodate, $dayTodate, $yearTodate);
		}
		
		if($todate || $fromdate){
			if(!$fromdate){
				$fromdate = 1;
			}
			if(!$todate){
				$todate = '9999999999';
			}

			if($fromdate > $todate){	//check if from to is wrongly twisted.
				$tmpdate = $todate;
				$todate = $fromdate;
				$fromdate = $tmpdate;
			}
			if ($fromdate == $todate){
				$divOneDay = '86400';
				$todate = $todate + $divOneDay;
			}
			$where .= ' AND date >= '.$fromdate.' AND date <= '.$todate.' ';
		}
		
		//add limit
		$limit = ($justNumOfItems == false) ? (intval($_GET['tx_t3blog_post_pointer']) ? intval($_GET['tx_t3blog_post_pointer']) * $this->conf['numberOfRecords'] : 0). ','. ($this->conf['numberOfRecords'] ? $this->conf['numberOfRecords'] : '10') : '';
		
		$RES = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$fields,
			$table.$additionalTables,
			$where,
			$groupBy,
			$orderBy,
			$limit
		);
	
		
		// if only num of results is requested jump in this.
		if($justNumOfItems == true)	{
			return $GLOBALS['TYPO3_DB']->sql_num_rows($RES);
		}

		$singleData['tags'] = $tagtitle;
		if($this->globalPiVars){
			if($this->globalPiVars['sword']){
				$singleData['filtered'] = $this->globalPiVars['sword'];	
			}else if ($this->localPiVars['tags']){
				$singleData['filtered'] = $this->localPiVars['tags'];
			}else if ($this->localPiVars['author']){
				$singleData['filtered'] = t3blog_div::getAuthorByUid($this->localPiVars['author']);
			}else if ($this->localPiVars['category']) {
				
				$singleData['filtered'] = t3blog_div::getCategoryNameByUid($this->localPiVars['category']);
			}
			
			$singleData['text']	= $this->pi_getLL('filteredByText');
			$singleData['resetText'] = $this->pi_getLL('resetText');
		}
		
		$content .= t3blog_div::getSingle($singleData, 'titelListItem');

		$itemArray = array();
		$i = 0;
		$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($RES);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($RES)) {
			$itemArray[] = $row;

			$convertTimestamp = date("Y-m-d",$row['date']);

			if($this->conf['gravatar']){	// set Gravatar or local pic if is stated
				$gravatar = $this->getGravatar($row['useruid'], $row['email'], $row['realName']);
			}else{
				$gravatar = '';
			}
		
			// get all content elemenets
			$resContent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid, CType, bodytext',		// SELECT ...
				'tt_content',		// FROM ...
				'irre_parentid = '.$row['uid'].' AND irre_parenttable = \'tx_t3blog_post\' '.$this->localcObj->enableFields('tt_content'),		// WHERE ...
				'uid',		// GROUP BY ...
				'sorting'		// ORDER BY ...
			);
			$uidContentList = '';
			$divider = false;
			while ( ($rowContent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resContent)) && !$divider) {
				
				// seperate by divider				
				$dividerInText 		= stripos($rowContent['bodytext'], '###MORE###');
				$textBeforeDivider	= $result = substr($rowContent['bodytext'], 0, strpos($rowContent['bodytext'], '###MORE###'));
				
				if($dividerInText > 0 ){
					$divider = true;
				} else {
					$uidContentList .= intval($rowContent['uid']).',';
				}
			}
			
			//print_r($row);
			if(strlen($uidContentList)>1){
				$uidContentList = substr($uidContentList,0,strlen($uidContentList)-1);
			}
			$data = array(
				'uid'			=> $row['uid'],
				'oddeven'		=> ($i%2==0 ? 'odd' : 'even'),
				'title'			=> $this->getTitleLinked($row['title'], $row['uid'], $row['date']),
				'date'			=> $this->getDate($row['date']),
				'author'		=> $this->getAuthor($row['realName']),
				'authorId'		=> $row['author'],
				'gravatar'		=> $gravatar,
				'email' 		=> $row['email'],
				'showMore'		=> $divider == true ? $textBeforeDivider. '<br/>'. $this->getTitleLinked($this->pi_getLL('moreText'),$row['uid'],$row['date'],'moreLink') : '',
				'contentUids'	=> $uidContentList,
				'time'			=> $this->getTime($row['date']),
				'categories'	=> $this->getCategoriesLinked($row['uid']),
				'comments'		=> $this->getCommentsLink($row['uid'], $row['date']),
				'tipafriendlinkText'=>	($this->conf['useTipAFriend']?$this->pi_getLL('tipafriendlinkText'):''),
				'blogUrl'		=>	'http://'.t3lib_div::getIndpEnv('HTTP_HOST').'/'.rawurlencode($this->getPermalink($row['uid'], $row['date'], true)),
				'permalink'		=> 	$this->getPermalink($row['uid'], $row['date']),
				'back'			=> $back,
			);
			$content .= t3blog_div::getSingle($data, 'listItem');
			$i++;
		}
		// if only the array is requested return it withour html parsing.
		if($justItemArray)	{
			return $itemArray;
		}
		
		// if no results have been found and any kind of filters have been sent...
		if($numRows<1 and (count($this->globalPiVars)>0 or count($this->localPiVars)>0)){
			$content .= t3blog_div::getSingle(array('text'=>$this->pi_getLL('noResult')),'noResultWrap');	
		}
		return $content;
	}


	/**
	 * gets the hierarchic categories and putsthem in the commaseparated list
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param 	int 	$parent
	 * @param 	string 	$uidList
	 */
	function getCommaSeparatedCategories($parent, &$uidList)	{
		$table = 'tx_t3blog_cat';
		$fields = 'uid';
		$where = 'parent_id = \''. t3lib_div::intval_positive($parent). '\'';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$uidList.= ','. $row['uid'];
			$this->getCommaSeparatedCategories($row['uid'], $uidList);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.listFunctions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.listFunctions.php']);
}
?>
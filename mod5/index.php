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

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH. 'init.php');
require_once($BACK_PATH. 'template.php');
require_once ('../mod1/class.functions.php');

$LANG->includeLLFile('EXT:t3blog/mod5/locallang.xml');
require_once(PATH_t3lib. 'class.t3lib_scbase.php');
// DEFAULT initialization of a module [END]

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returning the Blogroll administration
 *
 * @author		snowflake <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module5 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * Initializes the Module
	 */
	function init()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		parent::init();
	}

	/**
	 * adds items to the ->mod_menu array. used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
	}

	/**
	 * main function of the module. write the content to $this->content
	 * if you chose "web" as main module, you will need to consider the $this->id parameter
	 * which will contain the uid-number of the page clicked in the page tree
	 */
	function main()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		if(t3lib_div::GPVar('pid')){	// get the page id from the extension config
			$this->id = t3lib_div::GPVar('pid');
		}else{
			$this->id = $_GET['id'];
		}

		/* access check!
		 * the page will show only if there is a valid page and if this page may be viewed by the user
		 */
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			$this->blogfunctions = t3lib_div::makeInstance('blogfunctions');	// initialize blog function class
			// draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form = '<form action="" method="POST">';

			// javascript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
						return false;
					}
				</script>
			';
			$this->doc->postCode = '
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$this->doc->inDocStylesArray[]= $this->blogfunctions->getCSS();
			$headerSection =
				$this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']). '<br />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path'). ': '.
				t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'], 50);

			$this->content .=
				$this->doc->startPage($LANG->getLL('moduleTitle')).
				$this->doc->header($LANG->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->section('', $this->doc->funcMenu($headerSection, t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']))).
				$this->doc->divider(5);

			$this->moduleContent();	// render content

			if ($BE_USER->mayMakeShortcut())	{	// shortcut
				$this->content .= $this->doc->spacer(20). $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		} else {	// if no access or if id == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content .=
				$this->doc->startPage($LANG->getLL('moduleTitle')).
				$this->doc->header($LANG->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->spacer(10);
		}
	}


	/**
	 * Returns a function bar for the record list
	 *
	 * @param 	string 	$table: Table name
	 * @param 	string 	$row: Datarow
	 * @param 	int 	$allEntriesAmount: how many entries are there?
	 * @return 	Function bar
	 */
	function getFunctions($table, $row, $allEntriesAmount = 100){
		global $LANG;

		//TODO
		/*
			with the issueCommand function, the parameters like movedown and  moveup are still in the url.
			so after you move something and then unhide another record, you gonna move it again.
		*/

		$baseUrl = $this->blogfunctions->listURL($this->id, 'tx_t3blog_blogroll');
		
		// "edit" link: ( only if permissions to edit the page-record of the content of the parent page ($this->id)
		$params = '&edit['. $table. ']['. $row['uid']. ']=edit';
		$cells .= '<a href="'. $baseUrl. '#" onclick="'. htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->doc->backPath)). '">'.
				'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog'). 'icons/link_edit.png','width="16" height="16"'). ' title="'. $LANG->getLL('edit'). '" alt="'. $LANG->getLL('edit'). '" />'.
			'</a>';

		// "hide/unhide" links:
		if ($row['hidden'])	{
			$params = '&data['. $table. ']['. $row['uid']. ']['. 'hidden'. ']=0';
			$cells .= '<a href="'. $baseUrl. '#" onclick="'. htmlspecialchars('return jumpToUrl(\''. $GLOBALS['SOBE']->doc->issueCommand($params). '\');'). '">'.
					'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/button_unhide.gif', 'width="11" height="10"'). ' title="'. $LANG->getLL('unhide'). '" alt="'. $LANG->getLL('unhide'). '" />'.
				'</a>';
		} else {
			$params = '&data['. $table. ']['. $row['uid']. ']['. 'hidden'. ']=1';
			$cells .= '<a href="'. $baseUrl. '#" onclick="'. htmlspecialchars('return jumpToUrl(\''. $GLOBALS['SOBE']->doc->issueCommand($params). '\');'). '">'.
					'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/button_hide.gif', 'width="11" height="10"'). ' title="'. $LANG->getLL('hide'). '" alt="'. $LANG->getLL('hide'). '" />'.
				'</a>';
		}

		// "delete" blogroll:
		$params = '&cmd['. $table. ']['. $row['uid']. '][delete]=1';
		$cells .= '<a href="'. $baseUrl. '#" onclick="'. htmlspecialchars('if (confirm('. $GLOBALS['LANG']->JScharCode('Are you sure you want to delete this record?'.t3lib_BEfunc::referenceCount($table, $row['uid'],' (There are %s reference(s) to this record!)')). ')) {jumpToUrl(\''. $GLOBALS['SOBE']->doc->issueCommand($params). '\');} return false;'). '">'.
				'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/garbage.gif', 'width="11" height="12"'). ' title="'. $LANG->getLL('delete'). '" alt="'. $LANG->getLL('delete'). '" />'.
			'</a>';

		// move up & down:
		if ($row['sorting'] > 1) {	// (1st entry cant be moved up further)
			$params = '&move=moveup&uid='. $row['uid']. '&curPage='. intval(t3lib_div::_GP('curPage')). '&currSorting='. $row['sorting'];
			$cells.= '<a href="'. $baseUrl.'#" onclick="'. htmlspecialchars('return jumpToUrl(\''. $this->blogfunctions->listURL($this->id, 'tx_t3blog_blogroll'). $params. '\');'). '">'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog'). 'icons/arrow_up.png', 'width="16" height="16"'). ' title="'. $LANG->getLL('move_up'). '" alt="'. $LANG->getLL('move_up'). '" />'.
				'</a>';
		} else {
			$cells .= '<img'. t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog'). 'icons/blank.png', 'width="16" height="16"'). ' title="" alt="" />';
		}

		if ($row['sorting'] < $allEntriesAmount) {
			$params = '&move=movedown&uid='. $row['uid']. '&curPage='. intval(t3lib_div::_GP('curPage')). '&currSorting='. $row['sorting'];
			$cells .= '<a href="'. $baseUrl. '#" onclick="'. htmlspecialchars('return jumpToUrl(\''. $this->blogfunctions->listURL($this->id, 'tx_t3blog_blogroll'). $params. '\');'). '">'.
							'<img'. t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog'). 'icons/arrow_down.png', 'width="16" height="16"'). ' title="'. $LANG->getLL('move_down'). '" alt="'. $LANG->getLL('move_down'). '" />'.
					'</a>';
		}

		return $cells;
	}
	
	
	

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		global $LANG;

		if($this->id){	//resort content
			$resallsort = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',		// SELECT ...
				'tx_t3blog_blogroll',		// FROM ...
				'pid = '. $this->id. ' AND deleted = 0 ' ,		// WHERE ...
				'',		// GROUP BY ...
				'sorting ASC'		// ORDER BY ...
			);
			for ($i = 1; $rowsort = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resallsort); $i++){
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_t3blog_blogroll',		// table
					'uid='. $rowsort['uid'],	// where
					array('sorting'	=> $i)		// updateFields
				);
			}
		}

		// move up:
		if (t3lib_div::_GP('move') == 'moveup' && t3lib_div::_GP('uid') && t3lib_div::_GP('currSorting')) {
			$resUp = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,sorting',																				// SELECT
				'`'. t3lib_div::_GP('table'). '`',															// FROM
				(t3lib_div::_GP('currSorting') ? ' sorting < '.t3lib_div::_GP('currSorting').' AND ' : '').
				' pid = '. $this->id. ' AND deleted = 0 ' ,													// WHERE
				'',																							// GROUP BY
				'sorting DESC',																				// ORDER BY
				'0,1'																						// LIMIT
			);
			$rowUp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resUp);
			if($rowUp){
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'`'. t3lib_div::_GP('table'). '`',							// FROM ...
					'uid='. t3lib_div::intval_positive(t3lib_div::_GP('uid')),	// WHERE
					array('sorting' => $rowUp['sorting'])						// FIELDS
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'`'. t3lib_div::_GP('table'). '`',		// FROM ...
					'uid='. $rowUp['uid'],
					array('sorting'	=>	t3lib_div::intval_positive(t3lib_div::_GP('currSorting')))	//fields
				);
			}
		}

		// move down:
		if (t3lib_div::_GP('move') == 'movedown' && t3lib_div::_GP('uid')) {
			$resDown = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,sorting',		// SELECT ...
				'`'.t3lib_div::_GP('table').'`',		// FROM ...
				'sorting > '.t3lib_div::_GP('currSorting').' AND pid = '. $this->id. ' AND deleted = 0 ' ,		// WHERE ...
				'',		// GROUP BY ...
				'sorting ASC',		// ORDER BY ...
				'0,1'		// LIMIT ...
			);
			$rowDown = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resDown);
			if($rowDown){
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'`'.t3lib_div::_GP('table').'`',		// FROM ...
					'uid='. t3lib_div::intval_positive(t3lib_div::_GP('uid')),	//where
					array('sorting'	=>	$rowDown['sorting'])	//fields
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'`'. t3lib_div::_GP('table'). '`',		// FROM ...
					'uid='. $rowDown['uid'],
					array('sorting'	=> t3lib_div::intval_positive(t3lib_div::_GP('currSorting')))	//fields
				);
			}
		}

		if ($this->id) {
			// Set sort if there is a parameter
			if(t3lib_div::GPVar('sort')){
				$sort .= t3lib_div::GPVar('sort').' '.t3lib_div::GPVar('sortDir');
			}else{
				$sort = 'sorting asc';
			}

			// sorting
			// table-header & sorting links
			$i = (t3lib_div::_GP('curPage') ? t3lib_div::_GP('curPage') : 0);

			$fullTable=
				'<table cellspacing="0" cellpadding="0" class="recordlist">
				<tr>
					<!--th>
						<b>'. $LANG->getLL('sorting'). '</b>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()). '&curPage='. $i. '&search_field='. t3lib_div::GPVar('search_field'). '&search=Paging'. $i. '&sort=sorting&sortDir=ASC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='. $i. '&search_field='. t3lib_div::GPVar('search_field'). '&search=Paging'. $i. '&sort=sorting&sortDir=DESC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif', 'width="11" height="12"'). ' title="DESC" alt="" />
						</a>
					</th-->
					<th>
						<b>'.$LANG->getLL('title').'</b>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'. $i. '&sort=title&sortDir=ASC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'. $i. '&sort=title&sortDir=DESC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="'. $LANG->getLL('DESC'). '" alt="" />
						</a>
					</th>
					<th>
						<b>'.$LANG->getLL('description').'</b>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=description&sortDir=ASC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="'. $LANG->getLL('ASC'). '" alt="" />
						</a>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=description&sortDir=DESC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/reddown.gif', 'width="11" height="12"'). ' title="'. $LANG->getLL('DESC'). '" alt="" />
						</a>
					</th>

					<th>
						<b>'. $LANG->getLL('url'). '</b>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()). '&curPage='. $i. '&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=url&sortDir=ASC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/redup.gif', 'width="11" height="12"'). ' title="ASC" alt="" />
						</a>
						<a href='. htmlspecialchars($this->blogfunctions->listURL()). '&curPage='. $i. '&search_field='. t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=url&sortDir=DESC&pid='. $this->id. '>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						<b>'. $LANG->getLL('xfn'). '</b>
				</th>
				<th><b>'. $LANG->getLL('functions'). '</b></th>
			</tr>';

			// parameter examination
			// set the current page with parameter or default

			if(! t3lib_div::GPVar('curPage')){
				$curPage = 1;	// default page
			}else{
				$curPage = t3lib_div::GPVar('curPage');	// page from parameter
			}

			if(! t3lib_div::GPVar('search') && ! t3lib_div::GPVar('search_field')){	// add the search parameter to the query
				$queryPart = '';	// default
			}else{
				// get the query string for the table tx_t3blog_blogroll
				$queryPart = $this->blogfunctions->makeSearchString('tx_t3blog_blogroll');
			}

			// redirect to the first page after a search
			if(t3lib_div::GPVar('search') == 'Search'){
				$curPage = 1;
			}

			// paging
			// counts the number of database records with the given query

			// set the limit for one page
			$limitSize = 10;
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;

			// Get the amount of comments
			$rslimitMax = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count( tx_t3blog_blogroll.uid) as counter',
				'tx_t3blog_blogroll','tx_t3blog_blogroll.deleted=0 AND tx_t3blog_blogroll.pid='. $this->id. ' '. $queryPart.' ',
				''
			);
			while($dslimitMax = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rslimitMax)){
				$limitMax = $dslimitMax['counter'];
			}
			
			// calculate the first category on the current page
			$limitStart 	= ($curPage-1)*$limitSize;
			$limitStartShow	= $limitMax == 0 ? $limitStart : $limitStart+1;
			
			
			// calculate the last category on the current page
			$limitEnd 		= $curPage*$limitSize;		
			
			
			// calculates the number of records on the 'last' page
			if($limitEnd > $limitMax){
				
				$limitEffSize 	= $limitMax%$limitSize;
				$limitEnd 		= $limitMax;
				
			}else {
				
				$limitEffSize 	= $limitSize;
				
			}
			
			if(!isset($limitStart)) {
				$limitStart=1;
			}
			
			$recordFrame =
				'<div class="pagecount">'.
						$LANG->getLL('showRecords').': '.$limitStartShow.'-'.$limitEnd.' ('.$limitMax.') '.
					'</div>';
			$limit = $limitStart. ','. $limitEffSize;
			$numPages = ceil($limitMax/$limitSize);	// Calculate the number of pages
			// 	Creates 'Page X' links
			$paging = '<div class="paging">'. $LANG->getLL('pages'). ': ';

			for ($i = 1; $i <= $numPages; $i++) {
				$paging .= ' '.
					($i == $curPage ?
						'<strong>'. $i.'</strong> ' :

						'<a href="'.
							htmlspecialchars($this->blogfunctions->listURL()).
								'&curPage='. $i.
								'&search_field='. t3lib_div::GPVar('search_field').
								'&search=Paging&sort='. t3lib_div::GPVar('sort').
								'&sortDir='. t3lib_div::GPVar('sortDir').
								'&cat='. t3lib_div::GPVar('cat').
								'&pid='. $this->id.
						'">'. $i. '</a>'
					);
			}
			$paging .= '</div>';

			// show the current filter/sort/search settings and delete links
			//if(t3lib_div::GPVar('sortDir')){
			if(t3lib_div::GPVar('sortDir') == 'ASC'){
				$sortDirFull = 'ascending';
			} else {
				$sortDirFull = 'descending';
			}

			$curSettings = '<table class="highlight"><tr>';

			if(t3lib_div::GPVar('search_field')) {
					$curSettings  .=
				'<td  width="33%">
					<a href='. htmlspecialchars($this->blogfunctions->listURL()). '&curPage='. $curPage. '&search=Paging&sort='. t3lib_div::GPVar('sort').'&sortDir='. t3lib_div::GPVar('sortDir').'&pid='. $this->id. '>'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1). '" alt="" />'.
					'</a> <b>'.$LANG->getLL('search').'</b>: '.t3lib_div::GPVar('search_field').'</td>';
			}

			if(t3lib_div::GPVar('sort')) {
				$curSettings  .= '<td  width="33%">
					<a href='. htmlspecialchars($this->blogfunctions->listURL()).'&curPage='. $curPage.'&search_field='. t3lib_div::GPVar('search_field').'&search=Paging&pid='. $this->id.'>'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'),1).'" alt="" />'.
					'</a> <b>'. $LANG->getLL('sortBy').'</b>: '.t3lib_div::GPVar('sort').' '.$sortDirFull .'</td>';
			}
			$curSettings .= '</tr></table>';

			// show a table with of records that match the given query as well as the filter/sort/search settings
			$rsNormalList = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				' tx_t3blog_blogroll.uid as uid,'.
				' tx_t3blog_blogroll.xfn as xfn,'.
				' tx_t3blog_blogroll.sorting as sorting,'.
				' tx_t3blog_blogroll.title as title,'.
				' tx_t3blog_blogroll.url as url,'.
				' tx_t3blog_blogroll.description as description,'.
				' tx_t3blog_blogroll.hidden as hidden',					// SELECT
				'tx_t3blog_blogroll',									// TABLE
				'tx_t3blog_blogroll.deleted=0 '.						// WHERE
				'AND tx_t3blog_blogroll.pid='. $this->id.
				' '. $queryPart.' ',
				'',														// ORDER
				$sort,													// SORT
				$limit													// LIMIT
			);
			
				
			
			for ($i = 0; $dsNormalList = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsNormalList);$i++){
				$oddeven = 'odd';
				if($i % 2)$oddeven = 'even';
				$fullTable .= chr(10).
				'<tr class="'.$oddeven.'">
					<td width="100">'. $this->blogfunctions->truncate($dsNormalList['title'], 80). '</td>
					<td>'. $this->blogfunctions->truncate($dsNormalList['description'], 50). '&nbsp;</td>
					<td width="110">'. $dsNormalList['url']. '</td>
					<td width="120" title="'. $this->getXfnNames($dsNormalList['xfn']). '">'. $this->blogfunctions->truncate($this->getXfnNames($dsNormalList['xfn']), 50). '&nbsp;</td>
					<td width="100">'. $this->getFunctions('tx_t3blog_blogroll', $dsNormalList). '</td>
				</tr>';
			}
			$fullTable .= '</table>';

			// create new blogroll link:
			$createNewRecord  .= '<a href="#" class="newRecord" onclick="'. htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_t3blog_blogroll]['. $this->id. ']=new', $this->doc->backPath)).'">'.
							'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('t3blog'). 'icons/link_add.png', 'width="16" height="16"').' title="'. $GLOBALS['LANG']->getLL('new'. ($table == 'pages' ? 'Page' : 'Record'), 1). '" alt="" />&nbsp;'. $LANG->getLL('createNewBlogroll').
							'</a>';
			
			// building the content
			$content .=	
				$createNewRecord.
				$fullTable.
				$recordFrame.
				$paging.
				$curSettings.
				$this->blogfunctions->getSearchBox();
				

			$this->content .= $this->doc->section(''. $LANG->getLL('sectionTitle'), $content, 0, 1);

		}else{
			$this->content .= $this->doc->section($LANG->getLL('note'), $LANG->getLL('selABlog'), 0, 1);
		}
	}

	/**
	 * returns the xfn names commasepareted
	 *
	 * @param 	int 	$xfnIds
	 * @return 	string
	 */
	function getXfnNames($xfnIds){
		$return = '';
		if($xfnIds){
			$arrIds = split(',', $xfnIds);
			foreach ($arrIds as $id) {
				$return .= $GLOBALS['LANG']->getLL('xfn.I.'. $id);
				$return .= ', ';
			}
			if(strlen($return) > 2){
				$return = substr($return, 0, (strlen($return)-2));
			}
		}
		return $return;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod5/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod5/index.php']);
}

// make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module5');
$SOBE->init();

// include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>
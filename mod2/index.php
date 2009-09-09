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

require_once ('../mod1/class.functions.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:t3blog/mod2/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');

// DEFAULT initialization of a module [END]

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * returns the Blog entry administration
 *
 * @author	snowflake <info@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module2 extends t3lib_SCbase {
	var $pageinfo;
	var $blogfunctions;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 */
	function main()	{	
		
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
		

		if(t3lib_div::GPVar('pid')){
			$this->id = t3lib_div::GPVar('pid');
		}else{
			$this->id =  $_GET['id'];
		}

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			// Initialize Blog function class
			$this->blogfunctions = t3lib_div::makeInstance('blogfunctions');
			// Draw the header
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

			// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';
			$this->doc->inDocStylesArray[]= $this->blogfunctions->getCSS();

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content .=
				$this->doc->startPage($LANG->getLL('moduleTitle')).
				$this->doc->header($LANG->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']))).
				$this->doc->divider(5);

			$this->moduleContent();	// Render content

			if ($BE_USER->mayMakeShortcut())	{	// ShortCut
				$this->content .=
					$this->doc->spacer(20).
					$this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		} else {	// no access or if ID == zero
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
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Returns a function bar for the record list
	 *
	 * @param string $table: Table name
	 * @param string $row: Datarow
	 * @return Function bar
	 */
	function getFunctions($table,$row){
			// "Edit" link:
		$params = '&edit['.$table.']['.$row['uid'].']=edit';
		$cells .= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->doc->backPath)).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('t3blog').'icons/page_edit.png','width="16" height="16"').' title="Edit" alt="Edit" />'.
			'</a>';

			// "Hide/Unhide" links:
		if ($row['hidden'])	{
			$params = '&data['.$table.']['.$row['uid'].']['.'hidden'.']=0';
			$cells .= '<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');').'">'.
					'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="Un-hide" alt="Un-hide" />'.
				'</a>';
			} else {
				$params = '&data['.$table.']['.$row['uid'].']['.'hidden'.']=1';
				$cells .= '<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="Hide" alt="Hide" />'.
					'</a>';
		}

			// "Delete" link:
		$params = '&cmd['.$table.']['.$row['uid'].'][delete]=1';
		$cells .= '<a href="#" onclick="'.htmlspecialchars('if (confirm('.$GLOBALS['LANG']->JScharCode('Are you sure you want to delete this record?'.t3lib_BEfunc::referenceCount($table,$row['uid'],' (There are %s reference(s) to this record!)')).')) {jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');} return false;').'">'.
					'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="Delete" alt="Delete" />'.	
					'</a>';

			// Add comment link:
		$cells .= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_t3blog_com]['.$this->id.']=new&defVals[tx_t3blog_com][fk_post]='.$row['uid'],$this->doc->backPath)).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog').'icons/comment_add.png','width="16" height="16"').' title="Add comment" alt="Add comment" />'.
				'</a>';
			
			// Preview link:
		$cells .= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($this->id,$this->doc->backPath,'','#blogentry'.$row['uid'])).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog').'icons/magnifier.png','width="16" height="16"').' title="Preview" alt="Preview" />'.
			'</a>';

		return $cells;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		global $LANG;
		if ($this->id) {
			if(t3lib_div::GPVar('sort')){	// Sort if there is a parameter
				// Set the sort with the parameter received
				$sort .= t3lib_div::GPVar('sort'). ' '. t3lib_div::GPVar('sortDir');
			}else{
				// Set standart sort to date DESC if there is no parameter
				$sort .= 'date DESC';
			}

			// SORTING
			// Table-header & Sorting links
			$i = (t3lib_div::_GP('curPage')?t3lib_div::_GP('curPage'):0);
			$fullTable=
				'<table cellspacing="0" cellpadding="0" class="recordlist">
				<tr>
					<th>
						'.$LANG->getLL('dateAndTime').'
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=date&sortDir=ASC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=date&sortDir=DESC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						'.$LANG->getLL('title').'
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=title&sortDir=ASC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=title&sortDir=DESC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						'.$LANG->getLL('category').'
					</th>
					<th>
						'.$LANG->getLL('nrOfComments').'
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=comments&sortDir=ASC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=comments&sortDir=DESC&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>
							<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						'.$LANG->getLL('functions').'
					</th>
				</tr>';

				// PARAMETER EXAMINATION

				if(t3lib_div::GPVar('cat')){	// Use the cat parameter as filter
					$filter = 'AND tx_t3blog_post.uid IN (SELECT tx_t3blog_post.uid
							FROM tx_t3blog_post, tx_t3blog_cat, tx_t3blog_post_cat_mm
							WHERE tx_t3blog_post.uid = tx_t3blog_post_cat_mm.uid_local
							AND tx_t3blog_cat.uid = tx_t3blog_post_cat_mm.uid_foreign
							AND tx_t3blog_cat.catname  =\''.t3lib_div::GPVar('cat').'\')' ;
				}else{	// Use a selected category from the "Categories" module as filter
					if(t3lib_div::GPVar('linkCat')){
						$filter .= 'AND tx_t3blog_post.uid IN (SELECT tx_t3blog_post.uid
							FROM tx_t3blog_post, tx_t3blog_cat, tx_t3blog_post_cat_mm
							WHERE tx_t3blog_post.uid = tx_t3blog_post_cat_mm.uid_local
							AND tx_t3blog_cat.uid = tx_t3blog_post_cat_mm.uid_foreign
							AND tx_t3blog_post_cat_mm.uid_foreign ='.t3lib_div::GPVar('linkCat').')';
					}else{	// Otherwise disable the filter
						$filter = '';
					}
				}

				if(!t3lib_div::GPVar('curPage')){	// Set the current page with parameter or default
					$curPage = 1;	// Default
				}else{
					$curPage = t3lib_div::GPVar('curPage');	// Page from parameter
				}

				if(!t3lib_div::GPVar('search') && !t3lib_div::GPVar('search_field')){	// Add the search parameter to the query
					$queryPart = '';	// Default
				}else{
					$queryPart = $this->blogfunctions->makeSearchString('tx_t3blog_post');	// Get the query string for the table
				}

				if(t3lib_div::GPVar('search') == 'Search'){	// Redirect to the first page after a search
					$curPage = 1;
			 	}

				// FILTERING
				// Reads all category names from the database

				// Create a select option form
				$categoryFilters = '<select onchange="window.location.href=this.options[this.selectedIndex].value">';
				$categoryFilters .= '<option value="'.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&pid='.$this->id.'">'.$LANG->getLL('filterByCat').'</option>';
				// We also want to see the hidden and times records, but not the deleted ones
				$rsAllFilters=$GLOBALS['TYPO3_DB']->exec_SELECTquery('catname','tx_t3blog_cat','deleted=0 AND pid='.$this->id,'','catname');

				while($dsAllFilters=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsAllFilters)){
					if($dsAllFilters['catname'] == t3lib_div::GPVar('cat')){
						$selected = 'selected';
					}else {
						$selected = '';
					}

					// Populate the form with category names
					$categoryFilters .=
						'<option value="'. htmlspecialchars($this->blogfunctions->listURL()).
						'&curPage='.$i.
						'&search_field='.t3lib_div::GPVar('search_field').
						'&search=Paging'.
						'&sort='.t3lib_div::GPVar('sort').
						'&sortDir='.t3lib_div::GPVar('sortDir').
						'&cat='.$dsAllFilters['catname'].
						'&pid='.$this->id.
						'" '.$selected. '>'. $dsAllFilters['catname']. '</option>';
				}
				$categoryFilters .= '</select>';

				// PAGING
				// Counts the number of database records with the given query
				// Set the limit for one page
				$limitSize = 20;

				// Get the amount of posts
				$rslimitMax = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'count(distinct tx_t3blog_post.uid) as counter',	// select
						'tx_t3blog_post'.($filter?', tx_t3blog_post_cat_mm, tx_t3blog_cat':''),	//from
						'tx_t3blog_post.deleted=0 AND tx_t3blog_post.hidden=0 AND tx_t3blog_post.pid='.$this->id.' '.$queryPart.' '.$filter, //where 
						'',	//group by
						'' //order by
					);
				while($dslimitMax =$GLOBALS['TYPO3_DB']->sql_fetch_assoc($rslimitMax)){
					$limitMax = $dslimitMax['counter'];
				}

				// Calculate the first post on the current page
				$limitStart 	= ($curPage-1)*$limitSize;
				$limitStartShow	= $limitMax == 0 ? $limitStart : $limitStart+1;
				
				// Calculate the last post on the current page
				$limitEnd 		= $curPage*$limitSize;
				
				// Calculates the number of records on the 'last' page
				if($limitEnd > $limitMax){
					
					$limitEffSize 	= $limitMax%$limitSize;
					$limitEnd 		= $limitMax;
					
				}else{
					
					$limitEffSize 	= $limitSize;
					
				}
				
				if(!isset($limitStart)) {
					$limitStart=1;
				}
				
				$recordFrame = '<div class="pagecount">'.$LANG->getLL('showRecords').' '.$limitStartShow.'-'.$limitEnd.' ('.$limitMax.') </div>';
				$limit = $limitStart.','.$limitEffSize;
				$numPages = ceil($limitMax/$limitSize);	// Calculate the number of pages


				$paging = '<div class="paging">'. $LANG->getLL('pages'). ':';
				for ($i = 1; $i <= $numPages; $i++) {
					$paging .= ' ';
					if ($i == $curPage){
						$paging .= '<strong>'.$i.'</strong>';
					}else{
						$paging .= '<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>'.$i.'</a>';
					}
				}
				$paging .= '</div>';

				if(t3lib_div::GPVar('sortDir')){	// Show the current filter/sort/search settings and delete links
					if(t3lib_div::GPVar('sortDir') == 'ASC'){
						$sortDirFull = 'ascending';
					}else{
						$sortDirFull = 'descending';
					}
				}

				$curSettings = '<table><tr>';
				if(t3lib_div::GPVar('search_field')) {
					$curSettings .= '<td class="highlight">
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$curPage.'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'),1).'" alt="" />'.
						'</a> <strong>'.$LANG->getLL('search').'</strong>: '.t3lib_div::GPVar('search_field').'</td>';
				}

				if(t3lib_div::GPVar('sort'))	{
					$curSettings .= '<td class="highlight">
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$curPage.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />'.
						'</a> <strong>'.$LANG->getLL('sortBy').'</strong>: '.t3lib_div::GPVar('sort').' '.$sortDirFull .'</td>';
				}

				if(t3lib_div::GPVar('cat')) {
					$curSettings .= '<td class="highlight">
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$curPage.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&pid='.$this->id.'>'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />'.
						'</a> <strong>'.$LANG->getLL('filterCategory').'</strong>: '.t3lib_div::GPVar('cat').'</td>';
				}
				$curSettings .= '</tr></table>';

				// Show a table with of records that match the given query as well as the filter/sort/search settings
				$rsNormalList = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'distinct tx_t3blog_post.uid as uid, tx_t3blog_post.title as title, tx_t3blog_post.date as date, tx_t3blog_post.hidden as hidden, count(tx_t3blog_com.fk_post) as comments',	// select
					'tx_t3blog_post LEFT JOIN tx_t3blog_com ON(tx_t3blog_post.uid = tx_t3blog_com.fk_post AND tx_t3blog_com.deleted=0)', //from
					'tx_t3blog_post.deleted=0 AND tx_t3blog_post.pid='.$this->id.' '.$queryPart.' '.$filter, //where
					'uid',	//group by
					$sort,	// order by
					$limit	//limit
				);

				$i=0;
				while($dsNormalList=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsNormalList)){
					$i++;
					$oddeven = 'odd';
										
					// only if not hidden
					if($dsNormalList['hidden'] == 0){
						$trackbacksSent = $this->blogfunctions->sendTrackbacks($dsNormalList['uid'],$this->id);
					}
					if($i % 2)$oddeven = 'even';
					$fullTable .= chr(10).
					'<tr class="'.$oddeven.'">
						<td width="110">'. date("d.m.y H:i:s",$dsNormalList['date']).'</td>
						<td width="350">'. $dsNormalList['title'].'</td>
						<td width="100">'. $this->blogfunctions->getCategoryNames('tx_t3blog_post', $dsNormalList).'</td>
						<td width="" align="center"><a href="../mod3/index.php?linkCom='.$dsNormalList['uid'].'&pid='.$this->id.'" title="'.$GLOBALS['LANG']->getLL('seeComments').'">'.$dsNormalList['comments'].' <img'.t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog').'icons/comments.png','width="16" height="16"').' title="'.$GLOBALS['LANG']->getLL('seeComments').'" alt="'.$GLOBALS['LANG']->getLL('seeComments').'" /></a></td>
						<td width="100">'. $this->getFunctions('tx_t3blog_post', $dsNormalList).' <!-- trackbacks sent: '.$trackbacksSent.'--></td>
					</tr>';
				}

				$fullTable .= '</table>';
				
				// Create new Post link
				$createNewRecord = '<a class="newRecord" href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_t3blog_post]['.$this->id.']=new',$this->doc->backPath)).'">'.
								'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('t3blog').'icons/page_add.png','width="16" height="16"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />&nbsp;'.$LANG->getLL('createNewBlogPost').'</a>';
				
				// Building the content
				$content .= 	
					$createNewRecord.
					$categoryFilters.
					$fullTable.
					$recordFrame.
					$paging.
					$curSettings.
					$this->blogfunctions->getSearchBox();

				$this->content .= $this->doc->section($LANG->getLL('sectionTitle'), $content, 0, 1);
		}else{
			$this->content.= $this->doc->section($LANG->getLL('note'), $LANG->getLL('selABlog'), 0, 1);
		}

	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod2/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod2/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module2');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>
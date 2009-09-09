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
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once ('../mod1/class.functions.php');

$LANG->includeLLFile('EXT:t3blog/mod3/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
// DEFAULT initialization of a module [END]

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returns the Comments administration
 *
 * @author		snowflake <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module3 extends t3lib_SCbase {
	var $pageinfo;


	/**
	 * Initializes the Module
	 */
	function init()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		global $LANG;
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 */
	function main()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
		

		if(t3lib_div::GPVar('pid')){	// Get the page ID from the extension config
			$this->id = t3lib_div::GPVar('pid');
		}else {
			$this->id = $_GET['id'];
		}

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			$this->blogfunctions = t3lib_div::makeInstance('blogfunctions');	// Initialize Blog function class
			// Draw the header.
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
			
			$headerSection = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']).'<br />'. $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);
			$this->content .=
				$this->doc->startPage($LANG->getLL('moduleTitle')).
				$this->doc->header($LANG->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->section('', $this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']))).
				$this->doc->divider(5);

			$this->moduleContent();	// Render content

			if ($BE_USER->mayMakeShortcut())	{	// ShortCut
				$this->content.=$this->doc->spacer(20). $this->doc->section('', $this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)), $this->MCONF['name']));
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
	 * Returns a function bar for the record list
	 *
	 * @param 	string 			$table: Table name
	 * @param 	string 			$row: Datarow
	 * @return 	function bar
	 */
	function getFunctions($table, $row){

		global $LANG, $SENDERMAIL;
				
		// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		$params	=		'&edit['. $table. ']['. $row['uid']. ']=edit';		
		$cells .=		'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->doc->backPath)).'">'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('t3blog').'icons/comment_edit.png','width="16" height="16"').' title="Edit" alt="Edit" />'.
						'</a>';

		// "Hide/Unhide" links:
		if ($row['hidden'])	{
			
			$params	=	'&data['. $table. ']['. $row['uid']. ']['. 'hidden'. ']=0';
			$cells .= 	$this->makeCells($params, 'button_unhide.gif', 'Unhide', 'Unhide');
						
		} else {
			
			$params	=	'&data['. $table.']['. $row['uid']. ']['. 'hidden'. ']=1';
			$cells .= 	$this->makeCells($params, 'button_hide.gif', 'Hide', 'Hide');
		}

		// Approve / Unapprove button
		if ($row['approved'] == 1)	{
				
			// email new approved comment to subscribed users		
			if(!isset($this->i)) {
				// select all unapproved comments
				$table	= 'tx_t3blog_com';	
				$field	= 'DISTINCT uid, fk_post';
				$where	= 'pid = '.$this->id .' AND approved = 0 AND hidden = 0 AND deleted = 0';
				$com	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field, $table, $where);
				
				// get distinct posts
				foreach($com as $key => $value) {
					
					if(!strlen(strstr($posts, $value['fk_post'].','))) {
						$posts.= $value['fk_post'].',';
					}									
				}
				
				// implode posts
				$posts	= explode(',', $posts);
				foreach($posts as $key => $value) {
  					if($value == "") {
    					unset($posts[$key]);
  					}
				}
				
				$posts = array_values($posts); 
				
				// get all subscribers for this post
				foreach($posts as $key => $postuid) {
								
					// get users that subscribed to this post
					$table_send	= 'tx_t3blog_com_nl';	
					$field_send	= '*';
					$where_send	= 'post_uid = '.$postuid .' AND hidden = 0 AND deleted = 0';
					$subscriber	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_send, $table_send, $where_send);
				
					// get name of the post
					$table_post	= 'tx_t3blog_post';
					$field_post	= 'title';
					$where_post = 'uid ='.$postuid.' AND hidden = 0 AND deleted = 0';
					$post		= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_post, $table_post, $where_post);
					$posttitle	= $post['0']['title'];
											
					foreach($subscriber as $key => $value) {

						$table_com	= 'tx_t3blog_com';
						$field_com	= '*';
						$where_com	= 'date > '.$value['lastsent']. ' AND hidden = 0 AND deleted = 0 AND spam = 0 AND approved = 1';
						$comments	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_com, $table_com, $where_com);
						$message 	= '';
						
						if(count($comments) > 0) {				
											
							// assemble email
							$unsubscribe	= '<http://'.$_SERVER["SERVER_NAME"].'/?tx_t3blog_pi1[blogList][showUidPerma]='.$postuid.'&tx_t3blog_pi1[blogList][unsubscribe]=1&tx_t3blog_pi1[blogList][code]='.$value['code'].'>' ."\n";   
							$text			= '"'.trim($comments['0']['title']). ': '. trim($comments['0']['text']).'"'. "\n";                    
		 	            	$address		= str_replace(array('\\n', '\\r'), '', $value['email']); 	            
		 	            	$receiver   	= $address;
		 	             	$subject		= $LANG->getLL('subscribe.newComment').': '.$posttitle;
		 	             	$from       	= $SENDERMAIL;
		 	             	$headers    	= 'From: ' . $from;
		 	            	
		 	            	$message       .= $LANG->getLL('subscribe.salutation') .' '.$value['name'].','. "\n";
		 	            	$message       .= $LANG->getLL('subscribe.notification') . "\n\n";
		 	            	$message       .= $text . "\n";
		 	            		 	                      
		 	            	// unsubscribe
		 	            	$message       .= $LANG->getLL('subscribe.unsubscribe') ."\n";
						 	$message	   .= $unsubscribe;	 	                                  
		 	             	
		 	             	// send
					  		t3lib_div::plainMailEncoded($receiver,$subject,$message,$headers);
					  		
					  		// update lastsent to the last comment time
							$where_lastsent		= 'uid = '.$value['uid'];
							$fields_lastsent 	= array(
												'lastsent' => time(),
											  );
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table_send, $where_lastsent, $fields_lastsent);								
						}
						
					}									
				}
			}
			
			$this->i = 1;
			
			$params	=	'&data['. $table. ']['. $row['uid']. ']['.'approved'.']=0';
			$cells .= 	$this->makeCells($params, 'thumb_up.png', 'Unapprove', 'Unapprove');
			
		} else {
			
			$params	=	'&data['. $table. ']['.$row['uid'].']['.'approved'.']=1';
			$cells .= 	$this->makeCells($params, 'thumb_down.png', 'Approve', 'Approve');
			
			
		}

		// "Delete" link:
		$params	=		'&cmd['.$table.']['.$row['uid'].'][delete]=1';
		$cells .=		'<a href="#" onclick="'.htmlspecialchars('if (confirm('.$GLOBALS['LANG']->JScharCode('Are you sure you want to delete this record?'.t3lib_BEfunc::referenceCount($table, $row['uid'],' (There are %s reference(s) to this record!)')).')) {jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');} return false;').'">'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="Delete" alt="Delete" />'.
						'</a>';
				
		// Spam / not spam
		if ($row['spam'] == 1)	{
			
			$params	=	'&data'.$row['uid'].'&data['.$table.']['.$row['uid'].']['.'spam'.']=0';
			$cells .= 	$this->makeCells($params, 'flag_red.png', 'No, this is not spam', 'Spam');
						
		} else {
			
			$params	=	'&data'.$row['uid'].'&data['.$table.']['.$row['uid'].']['.'spam'.']=1';
			$cells .= 	$this->makeCells($params, 'flag_green.png', 'Mark as spam', 'Spam');
		}
		
		return $cells;
	}
	
	
	
	/**
	 * Makes specific links with icons
	 * @author 	Thomas Imboden <timboden@snowflake.ch>
	 *
	 * @param	string	$params: Parameters for this icon
	 * @param	string	$icon: Name of the icon inclusive datatype
	 * @param	string	$title: Titel of the cell
	 * @param	string	$alt: Alt tag to be shown
	 * 
	 * @return	string	link with images
	 */
	function makeCells($params, $icon, $title, $alt) {
		
		$cells .=	'<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');').'">'.
					'<img '. t3lib_iconWorks::skinImg($this->doc->backPath, t3lib_extMgm::extRelPath('t3blog').'icons/'.$icon,' width="18" height="16"').' title="'.$title.'" alt="'.$alt.'" />'.
					'</a>';
		
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
		global $LANG, $BE_USER;

		if ($this->id) {
			// Set sort if there is a parameter
			if(t3lib_div::GPVar('sort')){
				$sort .= t3lib_div::GPVar('sort').' '.t3lib_div::GPVar('sortDir');
			}else{
				$sort .= 'date DESC';
			}
			// SORTING
			// Table-header & Sorting links
			$i = intval(t3lib_div::_GP('curPage'));
			$fullTable=
				'<table cellspacing="0" cellpadding="0" class="recordlist">
				<tr>
					<th>
						<b>'.$LANG->getLL('dateAndTime').'</b>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=date&sortDir=ASC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=date&sortDir=DESC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						<b>'.$LANG->getLL('title').'</b>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=title&sortDir=ASC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=title&sortDir=DESC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</th>
					<th>
						<b>'.$LANG->getLL('text').'</b>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=text&sortDir=ASC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
						</a>
						<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=text&sortDir=DESC&pid='.$this->id.'>
							<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
						</a>
					</h>
					<th>
						<b>'.$LANG->getLL('author').'</b>
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=author&sortDir=ASC&pid='.$this->id.'>
						<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
					</a>
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=author&sortDir=DESC&pid='.$this->id.'>
						<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
					</a>
				</th>
				<th>
					<b>'.$LANG->getLL('post').'</b>
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=post_title&sortDir=ASC&pid='.$this->id.'>
						<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/redup.gif','width="11" height="12"').' title="ASC" alt="" />
					</a>
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging'.$i.'&sort=post_title&sortDir=DESC&pid='.$this->id.'>
						<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/reddown.gif','width="11" height="12"').' title="DESC" alt="" />
					</a>
				</th>
				<th><b>'.$LANG->getLL('functions').'</b></th>
			</tr>';


			// PARAMETER EXAMINATION
			// Set the current page with parameter or default

			if(!t3lib_div::GPVar('curPage')){
				// Default page
				$curPage = 1;
			}else{
				$curPage = t3lib_div::GPVar('curPage');	// Page from parameter
			}

			if(!t3lib_div::GPVar('search') && !t3lib_div::GPVar('search_field')){	// Add the search parameter to the query
				
				// Default
				$queryPart = '';
			}else{
				$queryPart = $this->blogfunctions->makeSearchString('tx_t3blog_com');	// Get the query string for the table tx_t3blog_com
			}

			// Redirect to the first page after a search
			if(t3lib_div::GPVar('search') == 'Search'){
				
				$curPage = 1;
			}
			
			// Only show comments for a post selected in the "Posts" module
			if(t3lib_div::GPVar('linkCom')){	
				
				// Partial query string
				$linkCom = 'AND tx_t3blog_com.fk_post = '.t3lib_div::GPVar('linkCom');
			}
			
			// Default
			else{
				$linkCom = '';	
			}

			// PAGING
			// Counts the number of database records with the given query

			// Set the limit for one page
			$limitSize = 20;

			// Get the amount of comments
			$rslimitMax = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'count(distinct tx_t3blog_com.uid) as counter',																		// SELECT
				//'tx_t3blog_com',																									// FROM
				//'tx_t3blog_com.deleted=0 AND tx_t3blog_com.hidden=0 AND tx_t3blog_com.pid='.$this->id.' '.$queryPart.' '.$linkCom, 	// WHERE
				'tx_t3blog_post,tx_t3blog_com',
				'(tx_t3blog_post.deleted=0 AND tx_t3blog_com.deleted=0)'.$queryPart.' '.$linkCom.' AND tx_t3blog_com.fk_post = tx_t3blog_post.uid AND tx_t3blog_com.pid ='.$this->id,
				''																													// GROUP BY
			);
			
			while($dslimitMax = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rslimitMax)) {
				
				$limitMax	= $dslimitMax['counter'];
			}
			
			
			// Calculates the first category on the current page
			$limitStart 	= ($curPage-1)*$limitSize;
			$limitStartShow	= $limitMax == 0 ? $limitStart : $limitStart+1;
			
			// Calculates the last category on the current page
			$limitEnd 		= $curPage*$limitSize;	
			
			// Calculates the number of records on the 'last' page
			if($limitEnd > $limitMax){	
				
				$limitEffSize 	= $limitMax%$limitSize;
				$limitEnd 		= $limitMax;
				
			} else {
				
				$limitEffSize 	= $limitSize;
			}
			
			if(!isset($limitStart)) {
				$limitStart=1;
			}
			
			$recordFrame 	= '<div class="pagecount">'.$LANG->getLL('showRecords').': '.$limitStartShow.'-'.$limitEnd.' ('.$limitMax.') </div>';
			$limit 			= $limitStart.','.$limitEffSize;
			
			// Calculate the number of pages
			$numPages 		= ceil($limitMax/$limitSize);	

			// Creates 'Page X' links
			$paging = '<div class="paging">'
					.$LANG->getLL('pages').':';
			for ($i = 1; $i <= $numPages; $i++) {
				$paging .= ' ';
				if ($i == $curPage){
					$paging .= '<strong>'.$i.'</strong>';
				}
				else{
					$paging .= '<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$i.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&cat='.t3lib_div::GPVar('cat').'&pid='.$this->id.'>'.$i.'</a>';
					}
			}
			$paging .= '</div>';

			// Show the current filter/sort/search settings and delete links					
			if(t3lib_div::GPVar('sortDir') == 'ASC'){
				$sortDirFull = 'ascending';
			}else{
				$sortDirFull = 'descending';
			}

			$curSettings = '<table class="highlight"><tr>';
			if(t3lib_div::GPVar('search_field'))$curSettings .='<td style="border-style:none;border-width:2px;" bgcolor="#CBDFC4" width="33%">
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$curPage.'&search=Paging&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&pid='.$this->id.'>'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />'.
					'</a> <b>'.$LANG->getLL('search').'</b>: '.t3lib_div::GPVar('search_field').'</td>';

			if(t3lib_div::GPVar('sort'))$curSettings .='<td style="border-style:none;border-width:2px;" bgcolor="#CBDFC4" width="33%">
					<a href='.htmlspecialchars($this->blogfunctions->listURL()).'&curPage='.$curPage.'&search_field='.t3lib_div::GPVar('search_field').'&search=Paging&pid='.$this->id.'>'.
						'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />'.
					'</a> <b>'.$LANG->getLL('sortBy').'</b>: '.t3lib_div::GPVar('sort').' '.$sortDirFull .'</td>';
			$curSettings .= '</tr></table>';

			// Show a table with of records that match the given query as well as the filter/sort/search settings
			$rsNormalList=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'distinct tx_t3blog_com.uid as uid,tx_t3blog_com.fk_post as fk_post, tx_t3blog_com.title as title,tx_t3blog_com.text as text, tx_t3blog_com.date as date,tx_t3blog_com.author as author, tx_t3blog_post.title as post_title, tx_t3blog_com.hidden as hidden, tx_t3blog_com.approved as approved, tx_t3blog_com.spam as spam, tx_t3blog_com.tstamp as tstamp, tx_t3blog_com.crdate as crdate',
				'tx_t3blog_post,tx_t3blog_com',
				'(tx_t3blog_post.deleted=0 AND tx_t3blog_com.deleted=0)'.$queryPart.' '.$linkCom.' AND tx_t3blog_com.fk_post = tx_t3blog_post.uid AND tx_t3blog_com.pid ='.$this->id,
				'', 
				$sort,
				$limit
			);
			
			for($i = 0;$dsNormalList = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsNormalList);$i++){
				// mark unseen comments
				if($BE_USER->user['lastlogin'] >= $dsNormalList['crdate'] && $dsNormalList['crdate']==$dsNormalList['tstamp']) {
					$newCommentColor='#FFC15F';
				}else {
					$newCommentColor='#ffffff';					
				}
				//update unseen comments
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com','uid = '.$dsNormalList['uid'],array('tstamp'=>time()));
				$oddeven = 'odd';
				
				
				if($i % 2)$oddeven = 'even';
				
				$fullTable .= chr(10).
				'<tr class="'.$oddeven.'">
					<td width="110">'.date("d.m.y H:i:s", $dsNormalList['date']).'</td>
					<td width="120">'.$this->blogfunctions->truncate($dsNormalList['title'],20).'</td>
					<td >'.$this->blogfunctions->truncate($dsNormalList['text'],50).'</td>
					<td width="80">'.$dsNormalList['author'].'</td>
					<td width="110">'.$dsNormalList['post_title'].' ('.$dsNormalList['fk_post'].')</td>
					<td width="100">'.$this->getFunctions('tx_t3blog_com', $dsNormalList).'</td>
				</tr>';
			}
			$fullTable .= '</table>';
			
			// Create new Comment link
			$createNewRecord .= '<a href="#" class="newRecord" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[tx_t3blog_com]['.$this->id.']=new', $this->doc->backPath)).'">'.
							'<img'. t3lib_iconWorks::skinImg($this->doc->backPath,t3lib_extMgm::extRelPath('t3blog').'icons/comment_add.png','width="16" height="16"').' title="'.$GLOBALS['LANG']->getLL('new'.($table == 'pages' ? 'Page' : 'Record'), 1).'" alt="" />&nbsp;'.$LANG->getLL('createNewComment').
							'</a>';
							
			// Building the content
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod3/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod3/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module3');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>
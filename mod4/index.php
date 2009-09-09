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

//including treeview class
require_once(t3lib_extMgm::extPath('t3blog'). 'lib/class.tx_t3blog_modfunc_selecttreeview.php');

$LANG->includeLLFile('EXT:t3blog/mod4/locallang.xml');
require_once(PATH_t3lib. 'class.t3lib_scbase.php');
//$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 * Returning the Category administration
 *
 * @author		snowflake <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module4 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * TreeViewObj
	 *
	 * @var tx_t3blog_tceFunc_selectTreeView
	 */
	var $treeViewObj;

	/**
	 * Initializes the Module
	 */
	function init()	{
		global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib. 'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
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

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			$this->blogfunctions = t3lib_div::makeInstance('blogfunctions');	// Initialize Blog function class
			// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST"></form>';

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
			$headerSection = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']). '<br />'. $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path'). ': '. t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content .=
				$this->doc->startPage($LANG->getLL('moduleTitle')).
				$this->doc->header($LANG->getLL('moduleTitle')).
				$this->doc->spacer(5).
				$this->doc->section('', $this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']))).
				$this->doc->divider(5);

			$this->moduleContent();	// Render content

			if ($BE_USER->mayMakeShortcut())	{	// ShortCut
				$this->content.=$this->doc->spacer(20).$this->doc->section('', $this->doc->makeShortcutIcon('id',implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
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
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 */
	function moduleContent()	{
		$this->id = $_GET['id'];
		$content='
				<!-- CATEGORY SELECTION made by karrer nicolas -->
				<hr />';
		if($this->id)	{
			$content.=
				'<a href="#" class="newRecord" onclick="'.t3lib_BEfunc::editOnClick('&edit[tx_t3blog_cat]['.$this->id. ']=new', $GLOBALS['BACK_PATH']). '">'.
					'<img '.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_extMgm::extRelPath('t3blog'). 'icons/chart_organisation_add.png'). ' title="'.$GLOBALS['LANG']->getLL('newCategory').'" width="16px" height="16px" style="margin-right: 5px;"/>'.
					$GLOBALS['LANG']->getLL('newCategory').
				'</a>'.
				$this->makeTree();

		} else {
			$content .= $GLOBALS['LANG']->getLL('selABlog');
		}

		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('sectionTitle'), $content, 0, 1);
	}

	/**
	 * Makes the Categorie tree in the module content.
	 * Uses class: tx_t3blog_modfunc_selecttreeview from the lib-folder
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>	 *
	 * @return string
	 */
	function makeTree()	{
		$this->treeViewObj = t3lib_div::makeInstance('tx_t3blog_modfunc_selecttreeview');
		$this->treeViewObj->table = 'tx_t3blog_cat';

		$where   = ' AND sys_language_uid = 0 AND l18n_parent = 0 AND tx_t3blog_cat.pid = '.$this->id;
		$orderBy = 'catname';

		$this->treeViewObj->init($where, $orderBy);
		$this->treeViewObj->parentField  = 'parent_id';
		$this->treeViewObj->expandAll    = 0;
		$this->treeViewObj->expandFirst  = 1;
		$this->treeViewObj->fieldArray   = array('uid', 'catname as title', 'catname as categoriename'); // those fields will be filled to the array $treeViewObj->tree
		$this->treeViewObj->ext_IconMode = '1'; // no context menu on icons
		$this->treeViewObj->title = $GLOBALS['LANG']->getLL('sectionTitle');
		$this->treeViewObj->thisScript = 'index.php?id='.$this->id;

		return $this->treeViewObj->getBrowsableTree();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod4/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod4/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_t3blog_module4');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>
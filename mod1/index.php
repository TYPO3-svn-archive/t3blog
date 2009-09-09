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

$LANG->includeLLFile('EXT:t3blog/mod1/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);

/**
 * Module 'T3BLOG' for the 't3blog' extension.
 *
 * @author		snowflake <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class  tx_t3blog_module1 extends t3lib_SCbase {
	var $doc;
	var $pageinfo;


	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		parent::init();

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		
		// Setting highlight mode:
		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');
		$this->doc->JScode = '';

		// Setting JavaScript for menu.
		$this->doc->JScode = $this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'
			function jumpTo(params,linkObj,highLightID)	{ //
				var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?"+params;
									if (top.condensedMode)	{
					top.content.document.location=theUrl;
				} else {
					parent.list_frame.document.location=theUrl;
				}
				'.($this->doHighlight?'hilight_row("row"+top.fsMod.recentIds["txt3blogM1"],highLightID);':'').'
				'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
				return false;
			}

			// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
			// See t3lib_BEfunc::getSetUpdateSignal()
			function refresh_nav() { //
				window.setTimeout("_refresh_nav();",0);
			}

			function _refresh_nav()	{ //
				document.location="'.htmlspecialchars(t3lib_div::getIndpEnv('SCRIPT_NAME').'?unique='.time()).'";
			}

			// Highlighting rows in the page tree:
			function hilight_row(frameSetModule,highLightID) { //
			// Remove old:
				theObj = document.getElementById(top.fsMod.navFrameHighlightedID[frameSetModule]);
				if (theObj)	{
					theObj.style.backgroundColor="";
				}
				// Set new:
				top.fsMod.navFrameHighlightedID[frameSetModule] = highLightID;
				theObj = document.getElementById(highLightID);
				if (theObj)	{
					theObj.style.backgroundColor="'. t3lib_div::modifyHTMLColorAll($this->doc->bgColor, -5). '";
				}
			}
		');
	}


	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS,$TYPO3_DB;
		
		$this->content = '';
		$this->content .= $this->doc->startPage('Navigation');

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 1;
		$perms_clause = $this->perms_clause;

		if ((isset($this->id) && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			$this->content .= '<h4>'.$LANG->getLL('navHeader').'</h4>';
			$res = $TYPO3_DB->exec_SELECTquery(
				'*',
				'pages',
				'doktype != 255 AND module in (\'t3blog\')'. t3lib_BEfunc::deleteClause('pages').t3lib_BEfunc::BEenableFields('pages')
			);
			$out = '';
			while ($row = $TYPO3_DB->sql_fetch_assoc($res)){
				$Pageperms_clause = $BE_USER->getPagePermsClause(1);
				$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
				$isInMount = $GLOBALS['BE_USER']->isInWebMount($row['uid'],$Pageperms_clause);

				if($isInMount){
					$out .=
						'<tr onmouseover="this.style.backgroundColor=\''.
							t3lib_div::modifyHTMLColorAll($this->doc->bgColor,-5).'\'" onmouseout="this.style.backgroundColor=\'\'">'.
								'<td id="t3blog_'.$row['uid'].'" ><a href="#" onclick="top.fsMod.recentIds[\'txt3blogM1\']='.$row['uid'].';jumpTo(\'id='.$row['uid'].'\',this,\'t3blog_'.$row['uid'].'\');">&nbsp;&nbsp;'.
									t3lib_iconWorks::getIconImage('pages',$row,$BACK_PATH,'title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($row['uid'], ' 1=1',20)).'" align="top"').
									htmlspecialchars($row['title']).
									'</a>'.
								'</td>'.
						'</tr>';
				}
			}

			$out = '<table cellspacing="0" cellpadding="0" border="0" width="100%">'.$out.'</table>';
			//$modlist
			$this->content.=
				$this->doc->section($LANG->getLL('t3blog_folders').
				t3lib_BEfunc::cshItem($this->cshTable,'folders',$BACK_PATH), $out, 1, 1, 0 , TRUE).
				$this->doc->spacer(10).chr(10).
				'<p class="c-refresh">
					<a href="'. htmlspecialchars(t3lib_div::linkThisScript(array('unique' => uniqid('t3blog_navframe')))).'">'.
					'<img'. t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_extMgm::extRelPath('t3blog').'icons/arrow_refresh.png','width="16" height="16"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.refresh',1).'" alt="" />'.
					$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.refresh', 1). '</a>
				</p>
				<br />';

			if ($this->doHighlight)	$this->content .=$this->doc->wrapScriptTags('	// Adding highlight - JavaScript
				hilight_row("",top.fsMod.navFrameHighlightedID["web"]);
			');

			if ($BE_USER->mayMakeShortcut())	{	// ShortCut
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {	// no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=
				$this->doc->startPage($LANG->getLL('title')).
				$this->doc->header($LANG->getLL('title')).
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

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/index.php']);
}

// Make instance:
$GLOBALS['SOBE'] = t3lib_div::makeInstance('tx_t3blog_module1');
$SOBE->init();

// Include files?
//foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->main();
$SOBE->printContent();
?>
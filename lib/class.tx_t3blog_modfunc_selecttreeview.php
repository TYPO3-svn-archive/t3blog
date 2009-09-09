<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2007 Nicolas Karrer <nkarrer@snowflake.ch>
* 
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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

require_once(PATH_t3lib.'class.t3lib_treeview.php');

/**
 * extends t3lib_treeview 
 *
 * @author	Nicolas Karrer <nkarrer@snowflake.ch>
 * 
 * @package TYPO3
 */
class tx_t3blog_modfunc_selecttreeview extends t3lib_treeview {
	
	/**
	 * Wrapping $title in a-tags.
	 * 
	 * Adding edit/delete function to edit the category directly from the tree.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title,$row,$bank=0)	{
		//$return = '<a onclick="window.location.href=\'alt_doc.php?returnUrl=db_list.php?id=0&edit[tx_t3blog_cat]['.$row['uid'].']=edit\'; return false;" href="#">edit</a>';
		
		
		
		if($row['uid'])	{
			/*
				first link = edit
				second link = new subcategorie (with default parent_id)
			*/
			
			
			
			$additional = '<span class="edit" style="position:absolute;left:300px;">
				<a href="#" onclick="'.t3lib_BEfunc::editOnClick('&edit[tx_t3blog_cat]['.$row['uid'].']=edit', $GLOBALS['BACK_PATH']).'">
					<img '.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_extMgm::extRelPath('t3blog').'icons/chart_organisation_edit.png').' title="edit" height="16px" widht="16px" />
				</a>
				<a href="#" style="margin-left:10px;" onclick="'.t3lib_BEfunc::editOnClick('&edit[tx_t3blog_cat]['.t3lib_div::_GP('id').']=new&overrideVals[tx_t3blog_cat][parent_id]='.$row['uid'], $GLOBALS['BACK_PATH']).'">
					<img '.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_extMgm::extRelPath('t3blog').'icons/chart_organisation_add.png').' title="create new subcategory" height="16px" widht="16px" />
				</a>';
				
				//get hide/unhide link
				if ($this->getHiddenParamFromUid($row['uid']))	{
					$params='&data[tx_t3blog_cat]['.$row['uid'].'][hidden]=0';
					$additional.='<a style="margin-left:10px;" href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');').'">'.
								'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_extMgm::extRelPath('t3blog').'icons/chart_organisation_unhide.png','width="16px" height="16px"').' title="Un-hide" alt="Un-hide" />'.
							'</a>';
					//wrap title in 'hidden'-span
					$title = '<span style="color:#ccc">'.$title.'</span>';
					
				} else {
					$params='&data[tx_t3blog_cat]['.$row['uid'].'][hidden]=1';
					$additional.='<a href="#" style="margin-left:10px;" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');').'">'.
							'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_extMgm::extRelPath('t3blog').'icons/chart_organisation_hide.png','width="16px" height="16px"').' title="Hide" alt="Hide" />'.
					'</a>';
				}
			
			$params='&cmd[tx_t3blog_cat]['.$row['uid'].'][delete]=1';	
			$additional.= 
				'<a href="#" style="margin-left:10px;" onclick="'.htmlspecialchars('if (confirm('.$GLOBALS['LANG']->JScharCode('Are you sure you want to delete this record?'.t3lib_BEfunc::referenceCount($table,$row['uid'],' (There are %s reference(s) to this record!)')).')) {jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params).'\');} return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_extMgm::extRelPath('t3blog').'icons/chart_organisation_delete.png','width="16px" height="16px"').' title="Delete" alt="Delete" />'.
				'</a>
			</span>';
		}		
		
		return $title.$additional;
		
	}
	
	/**
	 * Wrap the plus/minus icon in a link
	 * 
	 * Modificated it for the module
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($this->thisScript) {
			if ($bMark)	{
				$anchor = '#'.$bMark;
				$name=' name="'.$bMark.'"';
			}
			$aUrl = $this->thisScript.(strpos($this->thisScript, '?')?'&PM='.$cmd.$anchor:'?PM='.$cmd.$anchor);
			return '<a href="'.htmlspecialchars($aUrl).'"'.$name.'>'.$icon.'</a>';
		} else {
			return $icon;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $uid
	 * @return unknown
	 */
	function getHiddenParamFromUid($uid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('hidden', 'tx_t3blog_cat', 'uid = '.intval($uid));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
		return $row['hidden'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_modfunc_selecttreeview.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/lib/class.tx_t3blog_modfunc_selecttreeview.php']);
}
?>
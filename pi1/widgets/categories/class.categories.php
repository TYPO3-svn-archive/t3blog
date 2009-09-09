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
class categories extends tslib_pibase {
	var $prefixId      = 'categories';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/categories/class.categories.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $localPiVars;
	var $globalPiVars;
	var $conf;
	
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars 	= $piVars;
		$this->localPiVars 		= $piVars[$this->prefixId];
		$this->conf 			= $conf;
		$this->cObj 			= t3lib_div::makeInstance('tslib_cObj');
		
		$this->pi_loadLL();
		$this->pi_USER_INT_obj	= 0;		
		$categories 			= $this->listCategories();
		
		$categories['header'] 	= $this->pi_getLL('title');
		
		$content = t3blog_div::getSingle($categories, 'categories');
		
		return $content;
	}
	
	
	
	/**
	 * function to list the categories in FE.
	 * 	- tree view
	 * 	- open/closes subcategory - part with mootools-slide-object
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 * 
	 * @param 	int 	$parent
	 * @param 	int 	$level
	 * 
	 * @return array
	 */
	function listCategories($parent = 0, $level = 1)	{
		$table = 'tx_t3blog_cat';
		$field = '*';
		$where = 'pid = '.t3lib_div::intval_positive(t3blog_div::getBlogPid()).' AND parent_id = '.t3lib_div::intval_positive($parent);
		$where.= $this->cObj->enableFields('tx_t3blog_cat');
		$orderBy = 'catname';
		$set = false;
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field, $table, $where, '', $orderBy);
		$num_of_records = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$catArr = array();
			//get the subcategories
			if($row['uid'])	{
				$catArr[] = $this->listCategories($row['uid'], $level+1);
			}
			
			//which level
			$data['level'] = $level;
			//which element do we have to toggle?
			$data['toggleid'] = 'togglecat'.$row['uid'];
			//id of the ul-element (is the parent uid so we can act with the 'original' id to open- and close it.
			$data['id'] = 'togglecat'.$parent;
			
			//set the category informations
			//$row['catname'] = $this->pi_linkTP_keepPIvars($row['catname'],array('blogList' => array('category' => $row['uid'])), 0, 1,t3blog_div::getBlogPid());
			$dataLink = array(
				'catname'	=>	$row['catname'],
				'uid'		=>	$row['uid'],
				'blogPid'	=>	t3blog_div::getBlogPid()
			);
			$row['catname'] = t3blog_div::getSingle($dataLink,'catLink');
			$row['postnum'] = $this->getEntriesFromCategory($row['uid']);
			
			
			//if the category has any subcategories, we put an +/- image in front. Else we use a clear-image
			if($catArr[0]['content'])	{
				$row['catname'] = '<a href="#" id="img'.$data['toggleid'].'" class="iconbeforetext">[-]</a>'.$row['catname'];
			} else {
				$row['catname'] = $row['catname'];
			} 
			
			//render the list-items
			$data['content'].= t3blog_div::getSingle($row, 'listItem');
			
			//add subcategories
			foreach($catArr as $subCat)	{
				$data['content'].= $subCat['content'];
			}
			
			//Slide Subcategories part.
			if($catArr[0]['content'])	{
				$data['content'].= '<script type="text/javascript">
					/*<![CDATA[*/ 
							var mySlide'.$data['toggleid'].' = new Fx.Slide($(\''.$data['toggleid'].'\'));
							if(Cookie.get("mySlide'.$data['toggleid'].'")==1){
								mySlide'.$data['toggleid'].'.toggle();
								if($(\'img'.$data['toggleid'].'\').firstChild.nodeValue == "[+]")	{
									$(\'img'.$data['toggleid'].'\').firstChild.nodeValue = "[-]";
								} else {
									$(\'img'.$data['toggleid'].'\').firstChild.nodeValue = "[+]";
								}
							}
							
							$(\'img'.$data['toggleid'].'\').addEvent(\'click\', function(e) {
								e = new Event(e);			
								mySlide'.$data['toggleid'].'.toggle();
								if($(\'img'.$data['toggleid'].'\').firstChild.nodeValue == "[+]")	{
									Cookie.remove("mySlide'.$data['toggleid'].'");
									Cookie.set("mySlide'.$data['toggleid'].'","0",{path:"/"});
									$(\'img'.$data['toggleid'].'\').firstChild.nodeValue = "[-]";
								} else {
									Cookie.set("mySlide'.$data['toggleid'].'","1",{path:"/"});
									$(\'img'.$data['toggleid'].'\').firstChild.nodeValue = "[+]";
								}
								e.stop();
							}
							
							);
						
					/*]]>*/</script>';
			}
			
			$set = true;	//does it have any content. (to avoid from empty ul's	
		}
		
		$data['content'] = ($set)?t3blog_div::getSingle($data, 'list'):''; //if there are any entries wrap them in ul-tag
		
		return $data;
	}
	
	
	/**
	 * Gets the number of entries per category (and sub)
	 *
	 * @author	Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param 	int 	$category
	 * @return 	int
	 */
	function getEntriesFromCategory($category)	{
		$uidList = $category;
		$this->getCommaSeparatedCategories($category, $uidList);
		
		$table = 'tx_t3blog_post, tx_t3blog_post_cat_mm as mm';
		$where = 'tx_t3blog_post.uid = mm.uid_local AND uid_foreign IN('.$GLOBALS['TYPO3_DB']->quoteStr($uidList, 'tx_t3blog_post_cat_mm').')';
		$where.= $this->cObj->enableFields('tx_t3blog_post');
		$fields = '*';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		
		$num = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		return $num;
	}
	
	
	/**
	 * gets the hirarchic categories and put it in the commaseparated list
	 * 
	 * @author 	Nicolas Karrer <nkarrer@snowflake.ch>
	 * 
	 * @param 	int 	$parent
	 * @param 	string 	$uidList
	 */
	function getCommaSeparatedCategories($parent, &$uidList)	{
		$table = 'tx_t3blog_cat';
		$fields = 'uid';
		$where = 'parent_id = \''.t3lib_div::intval_positive($parent).'\'';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$uidList.= ','.$row['uid'];
			$this->getCommaSeparatedCategories($row['uid'], $uidList);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/categories/class.categories.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/categories/class.categories.php']);
}
?>
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
class blogrollList extends tslib_pibase {
	var $prefixId      = 'blogrollList';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogrollList/class.blogrollList.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.


	/**
	 * The main method of the PlugIn
	 *
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->conf = $conf;
		$this->init();

		$content = '';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*', 'tx_t3blog_blogroll',
			'pid=' . t3blog_div::getBlogPid() . $this->cObj->enableFields('tx_t3blog_blogroll'),
			'', 'sorting'
		);

		$listElements = '';
		for ($i = 0; $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); $i++) {
			$data = array(
				'odd'			=> $i%2 == 0 ? 'odd' : 'even',
				'title'			=> $row['title'],
				'url'			=> $row['url'],
				'image'			=> $this->getImage($row),
				'description'	=> $row['description'],
				'xfn'			=> $this->getXfnNames($row['xfn'])
			);
			$listElements .= t3blog_div::getSingle($data, 'listItem', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$content = t3blog_div::getSingle(array(
				'title' => $this->pi_getLL('latestPostsTitle'),
				'listItems' => $listElements
			), 'list', $this->conf);

		return $content;
	}

	/**
	 * Obtrains the image for the blog if set.
	 *
	 * @param  array $row
	 * @return string
	 */
	protected function getImage(array $row) {
		$image = '';
		if ($row['image']) {
			$cObj = t3lib_div::makeInstance('tslib_cObj');
			$cObj->start($row, 'tx_t3blog_blogroll');
			$image = $cObj->cObjGetSingle($this->conf['imgFieldList'], $this->conf['imgFieldList.']);
		}
		return $image;
	}



	/**
	 * Initial Method
	 */
	function init(){
		$this->pi_loadLL();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}


	/**
	 * returns the xfn names commasepareted
	 *
	 * @param 	int	$xfnIds
	 * @return 	string
	 */
	function getXfnNames($xfnIds) {
		$return = '';
		if ($xfnIds) {
			$arrIds = explode(',', $xfnIds);
			foreach ($arrIds as $id) {
				$return .= $this->pi_getLL('xfn.I.' . $id) . ' ';
			}
		}

		return $return;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogrollList/class.blogrollList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogrollList/class.blogrollList.php']);
}
?>
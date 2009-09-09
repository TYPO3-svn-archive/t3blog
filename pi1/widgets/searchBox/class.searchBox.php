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
 * @author	snowflake <info@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class searchBox extends tslib_pibase {
	var $prefixId      = 'searchBox';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/searchBox/class.searchBox.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $localPiVars;
	var $globalPiVars;
	var $conf;
	
	/**
	 * The main method of the PlugIn
	 * @author Manu Oehler <moehler@snowflake.ch>
	 * 
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prefixId];
		$this->conf = $conf;
		$this->init();
		
		/*******************************************************/
		//example pivar for communication interface
		//$this->piVars['widgetname']['action'] = "value";
		/*******************************************************/
		$button = t3blog_div::getSingle(array('buttonTitle'=>$this->pi_getLL('buttonTitle')),'searchButton');
		
		$data = array(
			'searchBoxLabel'		=>	$this->pi_getLL('searchBoxLabel'),
			'searchBoxDefault'		=>	$this->pi_getLL('searchBoxDefault'),
			'time'					=>	time(),
			'searchButton'			=>	$button
		);
		$content = t3blog_div::getSingle($data,'form');
		return $content;
	}

	/**
	 * Initial Method
	 */
	function init(){
		$this->pi_loadLL();
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/searchBox/class.searchBox.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/searchBox/class.searchBox.php']);
}

?>
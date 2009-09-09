<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 snowflake productions gmbH <info@snowflake.ch>
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
 * Plugin 'Blog Widget Selector' for the 't3blog' extension.
 *
 * @author		snowflake productions gmbH <info@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_pi2 extends tslib_pibase {
	var $prefixId      = 'tx_t3blog_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_t3blog_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = true;
	var $widgetParams = array();	// to contain ass. array of widget infos

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->init();	//initial method
		t3blog_div::setAlternativeBlogPid($this->internal['storagePid']);
		require_once (realpath(PATH_typo3conf. '/ext/t3blog/pi1/class.tx_t3blog_pi1.php'));
		$t3blog_pi1 = t3lib_div::makeInstance('tx_t3blog_pi1');

		// @note: the widgets conf TS is declared inside: ext/t3blog/static/setup.txt under plugin.tx_t3blog_pi2 {...
		$widgetconf = $conf[$this->widgetParams['folder'].'.'];
		$content = $t3blog_pi1->callWidget($this->widgetParams['folder'], $widgetconf);

		return $this->pi_wrapInBaseClass($content);
	}


	/**
	 * Basic initalization
	 */
	function init() {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');

		$this->pi_initPIflexForm();	// init flexform

		$this->internal['code'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'display', 'sDEF');
		$this->internal['storagePid'] =  $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'storagePid', 'sDEF');

		$widgetArr = $this->fetchWidgetKeys();	// have the flexform initializer fetch the available widgets titles+ descriptions
		$this->widgetParams = $widgetArr[$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'widget', 'sDEF')];	// widgets are stored numeric, need to be retranslated to widget names
		
		// load blog lib classes:
		
		//include classes
		include_once(t3lib_extMgm::extPath('t3blog').'pi1/lib/class.t3blog_div.php');
		include_once(t3lib_extMgm::extPath('t3blog').'pi1/lib/class.t3blog_db.php');
		
	}


	/**
	 * fetches the available widgets' names to their resp. keys
	 *
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 * @return array
	 */
	function fetchWidgetKeys() {
		require_once(realpath(PATH_typo3conf.'/ext/t3blog/pi2/class.tx_t3blog_pi2_addFieldsToFlexForm.php'));
		$widgetsFlexformPrep = new tx_t3blog_pi2_addFieldsToFlexForm;
		$widgetsArr = array();
		$widgetsArr = $widgetsFlexformPrep->getWidgets($widgetsArr, true);

		return $widgetsArr;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi2/class.tx_t3blog_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi2/class.tx_t3blog_pi2.php']);
}

?>
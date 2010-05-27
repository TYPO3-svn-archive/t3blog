<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 snowflake <typo3@snowflake.ch>
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
require_once(t3lib_extMgm::extPath('t3blog') . 'pi1/lib/class.t3blog_div.php');
require_once(t3lib_extMgm::extPath('t3blog') . 'pi1/lib/class.t3blog_db.php');


/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author	snowflake <typo3@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class tx_t3blog_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_t3blog_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_t3blog_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = true;
	var $widgetFolder;

	/**
	 * The cObj
	 *
	 * @var tslib_cObj
	 */
	var $localcObj = '';


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @param	bool		$exclusiveWidget: render just one widget exclusively? (evoked from pi2 than)
	 * @param	string		$widgetname: name of widget to be rendered exclusively
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf;
		$this->init();
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$data = array();
		$js = '';

		if (is_array($conf['widget.'])) {	// get widgets from TS:
			foreach ($conf['widget.'] as $widgetname => $widgetconf){
				if(strpos($widgetname, '.')){
					$widgetname = trim($widgetname, '.');
					$content = '';
					$content = $this->callWidget($widgetname, $widgetconf);
					if($content){
						$data = array_merge($data, $this->addPlaceHolder($widgetname, $content));
					}
					if($widgetconf['jsFiles.']){	//get js files
						foreach ($widgetconf['jsFiles.'] as $file){
							$js .= $this->includeJavaScript($this->widgetFolder . $widgetname. '/', $file);
						}
					}
				}
			}
		}
		if($js){
			$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = $js;
		}

		$GLOBALS['TSFE']->additionalHeaderData['t3b_pingback'] = '<link rel="pingback" href="' . htmlspecialchars($this->getPingbackUrl()) . '" />';
		$content = t3blog_div::getSingle($data, 'template', $this->conf);

		return $content;
	}


	/**
	 * Initial Method
	 *
	 */
	function init(){
		$this->widgetFolder = t3lib_extMgm::siteRelPath('t3blog') . 'pi1/widgets/';
		$this->localcObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * gets the widget content. uses the widgetname from the ts.
	 *
	 * @param 	string 	$widgetname
	 * @param 	array 	$widgetconf
	 * @return 	string 	html content
	 */
	function callWidget($widgetName, array $widgetConf){
		$content = '';

		$widgetPath = $this->getWidgetPath($widgetName, $widgetConf);
		if (is_file($widgetPath)) {
			t3lib_div::requireOnce($widgetPath);
			$widget = t3lib_div::makeInstance($widgetName);
			$content = $widget->main('', $widgetConf, $this->piVars, $this->localcObj);
		}
		return $content;
	}

	/**
	 * Obtains a path to the widget
	 *
	 * @param string $widgetKey
	 * @param array $widgetConf
	 * @return A path to the widget
	 */
	protected function getWidgetPath($widgetKey, array $widgetConf) {
		if (isset($widgetConf['includeLibs'])) {
			$widgetPath = $GLOBALS['TSFE']->tmpl->getFileName($widgetConf['includeLibs']);
		}
		else {
			$widgetPath = $this->widgetFolder . $widgetKey . '/class.' . $widgetKey . '.php';
		}
		return $widgetPath;
	}

	/**
	 * add placeholder
	 *
	 * @param string $widgetname
	 * @param string $content
	 * @return array
	 */
	function addPlaceHolder($widgetname, $content) {

		return array($widgetname=>$content);
	}

	/**
	 * render js include tag to embed an external js file via src-param.
	 *
	 * @param string $path
	 * @param string $file
	 * @return string
	 */
	function includeJavaScript($path, $file)	{
		$rc =
			'<script src="'.	/*t3lib_extMgm::siteRelPath($this->extKey).dirname($this->scriptRelPath).*/
			$path.$file. '" type="text/javascript"></script>';

		return $rc;
	}

	/**
	 * Parses data through typoscript.
	 *
	 * @param	String[]	$data: Data which will be passed to the typoscript.
	 * @param	String		$ts: The typoscript which will be called.
	 */
	function getSingle($data, $ts) {
		//If debug is enabled ($this->debug) and the debug param is set (t3lib_div::_GP('debug')),
		// display the data array and which ts will be invoked.
		if ($this->debug && t3lib_div::_GP('debug')) {
			t3lib_div::debug($data, $ts.' '. __FILE__. '@'. __LINE__);
		}

		//Set the data array in the local cObj. This data will be available in the ts. E.G. {field:[fieldName]} or field = [fieldName]
		$this->localCobj->data = $data;

		//Parse and return the result.
		return $this->localCobj->cObjGetSingle($this->conf[$ts], $this->conf[$ts.'.']);
	}

	/**
	 * Generates a pingback URL
	 *
	 * @return string
	 */
	protected function getPingbackUrl() {
		return t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => '&eID=t3b_pingback'
		)));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/class.tx_t3blog_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/class.tx_t3blog_pi1.php']);
}
?>
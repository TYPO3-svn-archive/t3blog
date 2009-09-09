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
class archive extends tslib_pibase {
	var $prefixId      = 'archive';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/archive/class.archive.php';	// Path to this script relative to the extension dir.
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
	function main($content, $conf, $piVars){
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prefixId];
		$this->conf = $conf;
		$this->init();

		/*******************************************************/
		//example pivar for communication interface
		//$this->piVars['widgetname']['action'] = "value";
		/*******************************************************/

		$content = '';
		$list = t3blog_db::getPostByWhere('deleted = 0 AND hidden = 0 ', 'date DESC', '0,1');
		if ($list) {
			$firstYear = null;
			if(!$firstYear){
				$firstdate = $list[0];
				$firstYear = date('Y', $firstdate['date']);
			}
			
			$listlast = t3blog_db::getPostByWhere('deleted = 0 AND hidden = 0 ', 'date ASC', '0,1');
			if($listlast){
				$lastdate = $listlast[0];
				$lastYear = date('Y',$lastdate['date']);	
			}else{
				$lastYear = $firstYear;
			}
			
			$years = array();
			for($actYear = $firstYear; $actYear >= $lastYear; $actYear--){
				$monthsInYear = '';
				$entriesInMonth = 0;
				for ($month = 12; $month > 0; $month--) {
					
					$table = 'tx_t3blog_post';
					$from = strtotime($actYear. '-'. $month. '-01');
					$to = strtotime($actYear. '-'. $month. '-31');
					$where = 'pid = '. t3blog_div::getBlogPid(). ' AND date <= '. $GLOBALS['TYPO3_DB']->fullQuoteStr($to, $table). ' AND date >= '. $GLOBALS['TYPO3_DB']->fullQuoteStr($from, $table);
					$list = t3blog_db::getPostByWhere($where, 'date ASC');

					$blogInMonth = '';
					//go throu posts from this month
					if ($list) {
						foreach ($list as $row) {
							//post link
							$dataTitle = array(
								'uid'		=> $row['uid'],
								'date'		=> $row['date'],
								'title'		=> $row['title'],
								'blogUid'	=> t3blog_div::getBlogPid()
							);
							//post <li>
							$dataLi = array(
								'class'	=> 'blogentry',
								'text'	=> t3blog_div::getSingle($dataTitle,'titleLink')
							);
							$blogInMonth .= t3blog_div::getSingle($dataLi, 'itemWrap');
							$entriesInMonth++;
						}
						//wrap ul for the entries in this month
						$id = $actYear. $month;
						$js = '/*<![CDATA[*/
								var mySlide'. $id. ' = new Fx.Slide($(\'archive_'. $id. '\'));
								if(Cookie.get("mySlide'. $id. '")==1){
									mySlide'. $id. '.toggle();
									if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
										$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
									} else {
										$(\'toggle'.$id.'\').firstChild.nodeValue = "[+]";
									}
								}

								$(\'toggle'. $id. '\').addEvent(\'click\', function(e) {
									e = new Event(e);
									mySlide'. $id. '.toggle();
									if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
										Cookie.remove("mySlide'. $id. '");
										Cookie.set("mySlide'. $id. '","0",{path:"/"});
										$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
									} else {
										Cookie.set("mySlide'. $id. '","1",{path:"/"});
										$(\'toggle'. $id. '\').firstChild.nodeValue = "[+]";
									}
									e.stop();
								}

								);
							/*]]>*/';

						$dataUlEntries = array(
							'class'		=> 'entries',
							'text'		=> $blogInMonth,
							'id'		=> $id,
							'js'		=> $js

						);
						$ulEntries = t3blog_div::getSingle($dataUlEntries, 'listWrap');

						$dataCatLinkMonth = array(	//wrap month link
							'text'		=> $this->pi_getLL('month_'.$month),
							'datefrom'	=> $from,
							'dateto'	=> $to,
							'entries'	=> count($list),
							'id'		=> ($actYear).($month),
							'blogUid'	=> t3blog_div::getBlogPid()
						);
						$dataMonthLi = array(
							'class'	=> 'month',
							'text'	=> t3blog_div::getSingle($dataCatLinkMonth, 'catLink'). $ulEntries
						);
						$monthsInYear = $monthsInYear.t3blog_div::getSingle($dataMonthLi, 'itemWrap');
					}
				}
				//wrap months in this year <ul>
				$id = $actYear;
				$js = 'var mySlide'. $id. ' = new Fx.Slide($(\'archive_'. $id. '\'));
						if(Cookie.get("mySlide'. $id. '")==1){
							mySlide'. $id. '.toggle();
							if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
								$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
							} else {
								$(\'toggle'. $id. '\').firstChild.nodeValue = "[+]";
							}
						}

						$(\'toggle'. $id. '\').addEvent(\'click\', function(e) {
							e = new Event(e);
							mySlide'. $id. '.toggle();
							if($(\'toggle'. $id. '\').firstChild.nodeValue == "[+]")	{
								Cookie.remove("mySlide'. $id. '");
								Cookie.set("mySlide'. $id. '","0",{path:"/"});
								$(\'toggle'. $id. '\').firstChild.nodeValue = "[-]";
							} else {
								Cookie.set("mySlide'. $id. '","1",{path:"/"});
								$(\'toggle'. $id. '\').firstChild.nodeValue = "[+]";
							}
							e.stop();
						}
					);
					';

				$dataUlMonths = array(
					'class'	=> 'months',
					'text'	=> $monthsInYear,
					'id'	=> $actYear,
					'js'	=> $js
				);
				$years[$actYear]['data'] = t3blog_div::getSingle($dataUlMonths, 'listWrap');
				$years[$actYear]['entries'] = $entriesInMonth;
			}

			if($years){	//go throu years
				foreach ($years as $year => $row) {	//wrap year li
					$dataCatLinkYear = array(
						'text'		=> $year,
						'datefrom'	=> strtotime($year.'-01-01'),
						'dateto'	=> strtotime($year.'-12-31'),
						'entries'	=> $row['entries'],
						'id'		=> $year
					);
					$dataYearLi = array(
						'class'	=> 'year',
						'text'	=> t3blog_div::getSingle($dataCatLinkYear, 'catLink'). $row['data']
					);
					$yearsInArchive .= t3blog_div::getSingle($dataYearLi, 'itemWrap');
				}

				$dataYearUl = array(	//wrap global ul
					'class'	=> 'archive',
					'text'	=> $yearsInArchive
				);
				$content = t3blog_div::getSingle($dataYearUl, 'listWrap');
				$content = t3blog_div::getSingle(
					array(
						'categoryTree'	=> $content,
						'title'	=> $this->pi_getLL('title')),
						'globalWrap'
					);
			}
		}

		return $content;
	}

	/**
	 * Initial Method
	 *
	 */
	function init(){
		$this->pi_loadLL();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/archive/class.archive.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/archive/class.archive.php']);
}
?>
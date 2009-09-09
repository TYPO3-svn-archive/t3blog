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
class rss extends tslib_pibase {
	var $prefixId      = 'rss';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/rss/class.rss.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $rss_091_IconName = 'new_rss091.png';
	var $rss_020_IconName = 'new_rss20.png';
	var $specialContent = 'xmlns:content="http://purl.org/rss/1.0/modules/content/"';
	var $localPiVars;
	var $globalPiVars;
	var $conf;
	var $cObj;
	
	
	/**
	 * The main method of the PlugIn
	 * @author Meile Simon <smeile@snowflake.ch>
	 * 
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars) {
		
		$this->globalPiVars = $piVars;
		$this->localPiVars 	= $piVars[$this->prefixId];
		
		// needed to differ between posts and comments
		define('TYPE', $this->localPiVars['value']);
		
		$this->conf 		= $conf;
		$this->init();
		
		// frontend output of RSS-links for the blog
		if(!t3lib_div::_GP('type') or t3lib_div::_GP('type') == 0) {
			
			$data = array(
				'title'				=>	$this->pi_getLL('rss_click_here'),
				'src091'    		=>	'typo3conf/ext/t3blog/icons/new_rss091.png',
				'postLinkTitle'		=>	$this->pi_getLL('rss_click_post'),
				'pid'				=> 	t3blog_div::getBlogPid(),
				'valuePost091'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments091'	=>	$this->pi_getLL('rss_click_comment'),
				'feed091'			=>	'0.91',
				'commentLinkTitle'	=>	$this->pi_getLL('rss_click_comment'),
				'src20'        		=>  'typo3conf/ext/t3blog/icons/new_rss20.png',
				'feed20'			=>	'2.0',
				'valuePost20'		=>	$this->pi_getLL('rss_click_post'),
				'valueComments20'	=>	$this->pi_getLL('rss_click_comment'),
			);
			
			// typoscript function
			$content .= t3blog_div::getSingle($data,'list');
			
			// the navigation content
			return $this->pi_wrapInBaseClass($content);
		
		} else {
			
			// the xml content
			$content = $this->make_xml($content,$conf);
			return $content;
		}		
	}
	
	
	 /**
	 * Create XML for RSS-Feed
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * 
	 * @return	xml-rss feed
	 */ 
	function make_xml($content,$conf) { 
		
		$this->conf		=$conf; 
		$this->feed_id 	= $this->localPiVars['feed_id'];
			
			// default feed is 2.0
			if (empty($this->feed_id)) {
				$this->feed_id = '2.0';
			}
			
			$className 	= t3lib_div::makeInstanceClassName('rss'); 
			$xmlObj 	= new $className('rss_export');
			
			$xmlObj->init();
			$xmlObj->XMLdebug	=0; 
			$xmlObj->conf 		= $conf;
			
			// get RSS version 
			$xmlObj->rssversion = $this->feed_id;
			
			// Creating header object 
			$xmlObj->renderHeader();			
			
			// get the posts
			if ($this->localPiVars['value'] == $this->pi_getLL('rss_click_post') || empty($this->localPiVars['value'])) {
					
				$xmlObj->setRecFields('tx_t3blog_post','title,author,uid,cat,date');
 	
				// Add page content information 
				$xmlObj->renderRecords('tx_t3blog_post',$this->getContentResult('tx_t3blog_post',$xmlObj->rssversion));
			
			} else {
				
				// get the comments
				if ($this->localPiVars['value'] == $this->pi_getLL('rss_click_comment')) {
					
					$xmlObj->setRecFields('tx_t3blog_com','title,author,uid,fk_post,date,text');
					
					// Add page content information 					
					$xmlObj->renderRecords('tx_t3blog_com',$this->getContentResult('tx_t3blog_com',$xmlObj->rssversion));
				}
			}
			
			// Add footer information
			$xmlObj->renderFooter(); 
			
			return $xmlObj->getResult();	
	}
	
	
	
	/**
	 * Gets content of requested table
	 *
	 * @param	string		$table: Name of the table
	 * @param	string		$rssversion: Version of the rss-feed
	 * 
	 * @return	xml-rss feed
	 */ 
	function getContentResult($table,$rssversion) { 
		
		global $TCA; 
		
		if ($TCA[$table]) { 
		
			$select = $table.'.* ';
			
			if ($this->conf['postItemOrderBy'] == '') { 
				$orderBy = ''; 
			} else { 
				$orderBy = ' ORDER BY '.$table.'.'.$this->conf['postItemOrderBy']; 
			}
			
			// only 15 items in RSS 0.91 
			$limit = $this->conf['postItemCount'];

			if ($this->conf['postItemCount'] == '') {  
				$limit = '';
			}
			else { 
				
				if ($limit > '15' and $rssversion=='0.91'){
					$limit = '15';
				}
				
				$limit 		= ' LIMIT 0,'.$limit; 
			}
			
			if ($limit == '' && $rssversion=='0.91'){
				$limit 		= ' LIMIT 0,15';
			}
			
			$groupBy 		= '';
			$orderBy_limit 	= '  '.$orderBy.$limit;
			
			$where = ' WHERE '.$table.'.deleted = 0 AND '.$table.'.hidden = 0';
				
			// checks for posts only available on specific fe-users
			$fe_groupCheck	= $GLOBALS['TSFE']->fe_user->groupData[uid];
			
			if(empty($fe_groupCheck)) {
				
				$where.= ' AND '.$table.'.fe_group = "0"';
			} else {
				$where.= ' AND ('.$table.'.fe_group = 0 OR '.$table.'.fe_group = '.$fe_groupCheck['1'].')';
			}
			
						
			if($table == 'tx_t3blog_com') {
				$where .= ' AND '.$table.'.spam = 0 AND '.$table.'.approved = 1 ';
			}
			
			if($table == 'tx_t3blog_post'){
				$select 	.= ', CONCAT(tt_content.header, \' \', tt_content.bodytext) AS text ';
				$table 		.= ' JOIN tt_content ON ( tt_content.irre_parentid = tx_t3blog_post.uid AND tt_content.irre_parenttable = \'tx_t3blog_post\' )';
				$where 		.= ' AND tt_content.hidden = 0 AND tt_content.deleted = 0';
				$groupBy 	.= ' GROUP BY tx_t3blog_post.uid ';
			}	
			
			
			$query 	= 'SELECT '.$select.' FROM '.$table.$where.$groupBy.$orderBy_limit;					
			$res 	= mysql(TYPO3_db,$query);
			return $res; 
		} 
	} 
	
	
	
	
	function setRecFields($table,$list)	{
		$this->XML_recFields[$table]=$list;
	}
	
	/**
	 * XML File with XML-Tags
	 *
	 * @return the content
	 */
	function getResult()	{
		$this->content = implode(chr(10),$this->lines);
		$this->content .= $this->footer;
		
		return $this->output($this->content);
	}
	
	/**
	 * The XML-Header for RSS.091 / RSS 2.0
	 */
	function renderHeader()	{
		if(strlen($this->conf['feedImage']) < 10){
			$feedimage = $this->conf['feedLink'].'/typo3conf/ext/t3blog/icons/rss.png';
		}else{
			$feedimage = $this->conf['feedImage'];
		}
		//echo 'this is the image'.$feedimage;
		// the XML structure
		$this->lines[]='<?xml version="1.0" encoding="UTF-8"?>';
		
		if($this->rssversion == '2.0') {
			$this->lines[].='<rss version="'.$this->rssversion.'" '.$this->specialContent.'>';
		} else {
			$this->lines[].='<rss version="'.$this->rssversion.'">';
		}
		$this->lines[].='<channel>
		 <title>'.htmlspecialchars(substr($this->conf['feedTitle'],0,100)).'</title>
		 <link>'.substr($this->conf['feedLink'],0,500).'</link>
		 <description>'.substr(htmlspecialchars($this->conf['feedDescription']),0,$this->conf['feedItemDescLength']).'</description>
		 <language>'.($GLOBALS['TSFE']->config['config']['language']).'</language>
		 <generator>'.$this->conf['generator'].' '.$GLOBALS['TYPO_VERSION'].'</generator>
		 ';
				if ($this->rssversion=='2.0') {
					$this->lines[].='<docs>http://blogs.law.harvard.edu/tech/rss</docs>';
				} else {
					$this->lines[].='<docs>http://backend.userland.com/rss091</docs>';
				}
		
		 $this->lines[].='
		 <copyright>'.$this->conf['feedCopyright'].'</copyright>
		 <managingEditor>'.$this->conf['feedManagingEditor'].'</managingEditor>
		 <webMaster>'.$this->conf['feedWebMaster'].'</webMaster>		
		 <image>
			<title>'.substr($this->conf['feedTitle'],0,100).'</title> 
			<url>'.$feedimage.'</url> 
			<link>'.substr($this->conf['feedLink'],0,500).'</link>
			<description>'.substr($this->conf['feedDescription'],0,$this->conf['feedItemDescLength']).'</description>
		 </image>
		';			
		
	}


	/**
	 * Renders the XML footer
	 */
	function renderFooter()	{
		$this->footer='
		</channel>
		</rss>';
	}
	
	/**
	 * Outputs the xml
	 * 
	 * @return returns the xml output
	 */
	function output()	{
        
		if ($this->XMLdebug)	{
			return '<pre>'.$this->content.'</pre>
			<hr /><div style="color:red;">size: '.strlen($this->content).'</div>';
		} else {
			return $this->content;
		}
	}

	
	/**
	 * a XML stream reformatter written in ANSI
	 * 
	 * @param 	boolean	$b 
	 * @return	string	reformatted xml
	 */
	function indent($b)	{
		if ($b)	$this->XMLIndent++; else $this->XMLIndent--;
		$this->Icode='';
		for ($a=0;$a<$this->XMLIndent;$a++)	{
			$this->Icode.=chr(9);
		}
		return $this->Icode;
	}


	/**
	 * Renders records
	 * 
	 * @param 	string	$table: table with records
	 * @param	array	$res: ressource
	 */
	function renderRecords($table,$res) {
		while($row = mysql_fetch_assoc($res))	{
			$this->addRecord($table,$row);
		}
	}


	/**
	 * Adds records
	 * 
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function addRecord($table,$row)	{
		$this->lines[]='<item>';
			$this->indent(1);
			$this->getRowInXML($table,$row);
			$this->indent(0);
		$this->lines[]='</item>';
	}


	/**
	 * Gets row in xml
	 * 
	 * @param 	string	$table: table with records
	 * @param	string	$row: row to save records
	 */
	function getRowInXML($table,$row)	{
		$fields = t3lib_div::trimExplode(',',$this->XML_recFields[$table],1);
		reset($fields);
		unset($this->item);
		
		while(list(,$field)=each($fields))	{
			
			$this->lines[]=$this->Icode.$this->fieldWrap($field,$this->substNewline($row[$field]),$row['date']);			
		}
	}


	/**
	 * Substitutes new line with character
	 * 
	 * @param 	string	$string: string to be substituted
	 * @return	substituted string
	 */
	function substNewline($string)	{
		return ereg_replace(chr(10),'',$string);
	}
	
	
	/**
	 * Gets posted categories
	 * 
	 * @param 	int		$value: uid of the category
	 * @return	categories
	 */
	function getPostCategories($value) {
		
		$fields	= 'catname';
		$table	= 'tx_t3blog_cat';
		$where	=  'deleted = 0 and hidden = 0 and uid = '.$value	;
		
		// checks for posts only available on specific fe-users
		$fe_groupCheck	= $GLOBALS['TSFE']->fe_user->groupData[uid];
			
		if(empty($fe_groupCheck)) {
				
			$where.= ' AND '.$table.'.fe_group = "0"';
		} else {
			$where.= ' AND ('.$table.'.fe_group = 0 OR '.$table.'.fe_group = '.$fe_groupCheck['1'].')';
		}		

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$catlist = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			 
			$data = $row['catname'];	
		}
		return $data;
	} 
	
	
	/**
	 * Takes the Date from the Database
	 *
	 * @param 	int		$value: uid of the post
	 * @return 	the date string
	 */
	function getDate($value)	{
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('date','tx_t3blog_post', 'deleted = 0 and hidden = 0 and uid = '.intval($value));
		while ($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$data = $row['date'];
		}
		// format the timestamp
		$formatedDate = date('Y/m/d',$data);
		
		// the new date string
		return $formatedDate;
	}
	
	
	/**
	 * Takes the author from the Database
	 *
	 * @param 	int $value: uid of the be user
	 * @return 	the realName
	 */
	function getAuthor($value) {

		if(is_int(intval($value))){

            
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('email','be_users', 'uid = "'.$value.'"','');
            
			while ($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$data = $row['email'];
			}
		}else{
			$data = $value;
		}
		return $data;
	}
	
	
	/**
	 * Gets the post title
	 *
	 * @param 	int 	$value: uid of the post
	 * @return 	the realName of the post
	 */
	function getFkPost($value) {

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title','tx_t3blog_post', 'deleted = 0 and hidden = 0 and uid ='.intval($value).'','');
		while ($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$data = $row['title'];
		}
		return $data;
	}
	
	
	/**
	 * Gets the post uid
	 *
	 * @param 	int 	$value: uid of the comment
	 * @return 	the uid of the post
	 */
	function getFkPostID($value) {

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('fk_post','tx_t3blog_com', 'deleted = 0 and hidden = 0 and uid ='.intval($value).'','');
		while ($row =  $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$data = $row['fk_post'];
		}
		return $data;
	}
	
	
	/**
	 * Wraps the fields given
	 *
	 * @param	string	$field: given field
	 * @param 	int 	$value: uid
	 * @param	int		$date: date in timestamp
	 * 
	 * @return Wraps the fields
	 */
	function fieldWrap($field,$value,$date)	{
				
		switch($field) {
				
			case 'author':				
				if($value != '') {
					if($this->localPiVars['value'] == $this->pi_getLL('rss_click_comment')) {
						return '<author>'.$value.'</author>';
					} else {
						$author = $this->getAuthor($value);
	          			return '<author>'.$author.'</author>';
					}
				}
				break;
			
			case 'cat':
				$category = $this->getPostCategories($value);
				return '<category>'.$category.'</category>';
				break;
			
			case 'fk_post':
				if($value != '')
				$fkpost = $this->getFkPost($value);
				return '<fk_post>'.$fkpost.'</fk_post>';
				break;
			
			case 'uid':
				$newDate = $this->getDate($value);
				$postid		= TYPE == 'Comments' ? $this->getFkPostID($value) : $value;
				$day 	= strftime('%d', $date);
				$month 	= strftime('%m', $date);
				$year	= strftime('%Y', $date);
													
				return '<link>'.(stripos('http://',t3lib_div::getIndpEnv('HTTP_HOST'))?'':'http://').t3lib_div::getIndpEnv('HTTP_HOST').'/'.tslib_pibase::pi_getPageLink(t3blog_div::getBlogPid(), '', array('tx_t3blog_pi1[blogList][year]' => $year, 'tx_t3blog_pi1[blogList][month]' => $month, 'tx_t3blog_pi1[blogList][day]' => $day, 'tx_t3blog_pi1[blogList][showUid]' => $this->conf['feedItemLinkPrefix'].$postid)).'</link>
	<guid>'.(stripos('http://',t3lib_div::getIndpEnv('HTTP_HOST'))?'':'http://').t3lib_div::getIndpEnv('HTTP_HOST').'/'.tslib_pibase::pi_getPageLink(t3blog_div::getBlogPid(), '', array('tx_t3blog_pi1[blogList][year]' => $year, 'tx_t3blog_pi1[blogList][month]' => $month, 'tx_t3blog_pi1[blogList][day]' => $day, 'tx_t3blog_pi1[blogList][showUid]' => $this->conf['feedItemLinkPrefix'].$postid)).'</guid>
	<description></description>';
                break;
			
			case 'text':
				$this->item.=' '.str_replace('###MORE###','',strip_tags($value));
				
				
				$descr_length = 500;
				if (strlen($this->item) > $descr_length)
				  $points='...';
				if($this->rssversion !='2.0')
				{					
					return '<description>'.substr(htmlspecialchars(substr($this->item,0,$descr_length).$points),0,$this->conf['feedItemDescLength']).'</description>';
				} else {
					return '<description>'.substr(htmlspecialchars(substr($this->item,0,50).$points),0,$this->conf['feedItemDescLength']).'</description>
						<content:encoded><![CDATA['.$this->item.']]></content:encoded>';
		
				}
				break;
			
			case 'date':
				setlocale (LC_TIME, 'de_CH.ISO8859-15'); 
				return '<pubDate>'.strftime('%a, %d %b %Y %H:%M:%S %Z', $value).'</pubDate>';
				break;
			
			default:
				return '<'.$field.'>'.htmlspecialchars($value).'</'.$field.'>';
		}
			
	}
	
	
	/**
	 * Initial Method
	 */
	function init(){
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pi_loadLL();
		
		return true;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/rss/class.rss.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/rss/class.rss.php']);
}

?>
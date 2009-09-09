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
 * Plugin 'T3BLOG' for the 't3blog' extension. Listing the Blog entries.
 * Includes a Single and a listview, this class switches them depending on the showUid pivar.
 *
 * @author	snowflake <info@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_t3blog
 */
class blogList extends tslib_pibase {
	var $prefixId      = 'blogList';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.blogList.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = true;
	var $localPiVars;
	var $globalPiVars;
	var $conf;

	/**
	 * The main method of the PlugIn
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @param	array		$pivars: The piVars of the pi1class
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;	// global pivars
		$this->localPiVars = $piVars[$this->prefixId];	// pivars of this widget
		$this->conf = $conf;
		$this->init();
		$this->pi_USER_INT_obj=0;


		/*******************************************************/
		//example pivar for communication interface
		//$this->piVars['widgetname']['action'] = "value";
		/*******************************************************/
		
		// bid = showuid
		/* TODO: not needed anymore?
		if(t3lib_div::_GP('bid')) {	
			$this->localPiVars['showUid'] 		= t3lib_div::_GP('bid');
			$piVars[$this->prefixId]['showUid'] = t3lib_div::_GP('bid');
		}
		*/
		// show list or single functions.
		if($this->localPiVars['showUid'] || $this->localPiVars['showUidPerma']){
						
			//show single view
			require_once('class.singleFunctions.php');
			$singleFunctions = t3lib_div::makeInstance('singleFunctions');
			$content = $singleFunctions->main($content, $this->conf, $piVars);
			
		}else{
			// showlist view's
			require_once('class.listFunctions.php');
			$listFunctions = t3lib_div::makeInstance('listFunctions');
			$content = $listFunctions->main($content, $this->conf, $piVars);
		}

		return $content;
	}


	/**
	 * Initial Method
	 *
	 */
	function init(){
		$this->pi_loadLL();
		$this->localcObj = t3lib_div::makeInstance('tslib_cObj');
	}


	/**
	 * Styles the title link with the typoscript titleLink
	 *
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param string $title
	 * @param uid of the post.
	 *
	 * @return string
	 */
	function getTitleLinked($title, $uid = 0, $date = '', $wrap = 'titleLink', $longTitle = ''){
		$data = array(
			'title'	=> $title,
			'uid'	=> $uid,
			'date'	=> $date,
			'longTitle' => $longTitle
		);
		
		return t3blog_div::getSingle($data, $wrap);
	}


	/**
	 * returns the text formated in the typoscript textRow
	 * available uid in typoscript
	 *
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param text $text
	 * @param uid of the post $uid
	 * @return string (html)
	 */
	function getText($text){
		$text = str_replace('###MORE###', '', $text);
		$data = array(
			'text'	=> $text,
		);

		return t3blog_div::getSingle($data, 'textFormat');
	}


	/**
	 * returns the date formatet with the config timeformat or G:i:s a'
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param date $date
	 * @return string
	 */
	function getTime($date){
		$format = ($this->conf['timeformat']) ? $this->conf['timeformat'] : 'G:i:s a';
		$data 	= array(
			'time'	=> $date
		);
		return t3blog_div::getSingle($data, 'time');
	}


	/**
	 * returns the date formatet with the config dateformat or d.m.Y
	 * also wrapped in the typoscript date
	 *
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param date $date
	 * @return string
	 */
	function getDate($date){
		$format 		= $this->conf['dateformat'] ? $this->conf['dateformat'] : 'd.m.Y';
		$formatedDate 	= date($format, $date);
		$data = array(
			'date'	=> $date
		);

		return t3blog_div::getSingle($data, 'date');
	}


	/**
	 * returns the comments link with the numbers of comments in front of it.
	 * Link generated in typoscript commentsLink
	 *
	 * @author Manu Oehler <moehler@snowflake.ch>
	 * @param  $uid
	 * @param  $date
	 * @return string
	 */
	function getCommentsLink($uid,$date=''){
		$data = array(
				'uid' 			=> $uid,
				'commentsNr'	=> t3blog_db::getNumberOfCommentsByPostUid($uid),
				'commentText' 	=> $this->pi_getLL('comments'),
				'date'			=> $date
		);

		return t3blog_div::getSingle($data, 'commentsLink');
	}


	/**
	 * returns the categories with as link used the catLink template.
	 * @author Manu Oehler <moehler@snowflake.ch>
	 * @param  uid of the blog post entry $uid
	 * @return string
	 */
	function getCategoriesLinked($uid){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_t3blog_cat.uid as uid, tx_t3blog_cat.catname as catname',								// SELECT ...
			'tx_t3blog_post',																			// LOCAL TABLE ...
			'tx_t3blog_post_cat_mm',																	// MM TABLE ...
			'tx_t3blog_cat',																			// FOREIGN TABLE ...
			' AND uid_local  = '.$uid.' AND tx_t3blog_cat.hidden = 0 AND tx_t3blog_cat.deleted = 0',	// WHERE ...
			'tx_t3blog_cat.uid',																		// GROUP BY ...
			'tx_t3blog_cat.catname ASC'																	// ORDER BY ...
		);
		
		$catlist = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			$data = array(
				'categories'	=> $row['uid'],
				'text'			=> $row['catname']
			);
			
			$catlist .= t3blog_div::getSingle($data, 'catLink'). ' / ';
		}
		
		if($catlist && strpos($catlist, ' / ')) {
			$catlist = substr($catlist, 0, strlen($catlist)-2);
		}

		$data['catLink'] = $catlist;
		return t3blog_div::getSingle($data, 'catList');
	}


	/**
	 * get the author formated
	 *
	 * @param string $author
	 * @return html string of the author
	 */
	function getAuthor($author){
		$data = array(
			'name' => $author
		);

		return t3blog_div::getSingle($data,'author');
	}


	/**
	 * Get Avatar pic, if local avatar is set then display that, else display the global gravatar (@ http://site.gravatar.com)
	 * Method is used for the avatars@posts as well as for the avatars@comments
	 *
	 * @param unknown_type $localPicName
	 * @param unknown_type $email
	 * @param unknown_type $username
	 * @return string (wrapped img)
	 */
	function getGravatar($userUid='', $email, $username){
		
		// userUid only specified when BE User
		if ($userUid){
			$this->localcObj->data['uid'] 	= $userUid;
			$avatar 						= $this->localcObj->cObjGetSingle($this->conf['avatarImg'], $this->conf['avatarImg.']);
		}
		if(!$avatar){
			
			// Default needed if user don't have a gravatar and don't have a local pic, but email is stated
			$default 	= 'http://'. $_SERVER['HTTP_HOST']. '/'. t3lib_extMgm::siteRelPath($this->extKey). 'icons/nopic_50_f.jpg';
			$size 		= $this->conf['gravatarsize']?$this->conf['gravatarsize']:50;
			$grav_url 	= 'http://www.gravatar.com/avatar/'. md5($email).	'?d='. urlencode($default).'&amp;s='.intval($size).'&amp;r='.$this->conf['gravatarRating'];
			$avatar 	= '<img src="'. $grav_url. '" alt="Gravatar: '. $username. '" title="Gravatar: '. $username. '" />';
		}
		
		// if local avatar is set then display that, else display the global gravatar @ site.gravatar.com
		return ($avatar); 
	}


	/**
    * Instantiates an IMAGE object (see TSREF for more info on that) an returns the according string
    * ready for use in your HTML.
    *
    * @param    string        $imagePath: The image's path. Typically uploads/tx_pluginname/filename
    * @param    array        $conf: Configuration for the image. See TSREF IMAGE for more info.
    * @return    An image string.
    */
   function getImage($imagePath, $title = '', $conf = array(), $icon = false)    {
		$image = $conf;
		
		if ($icon) {
		   $image['file'] = $this->extensionPath. $imagePath;
		} else {
		   $image['file'] = $this->uploadPath. $imagePath;
		}
		
		$image['titleText'] = $title;
		$image['altText'] = $title;
		$imagestring = $this->cObj->IMAGE($image);

       return $imagestring;
   }
   
   
   /**
    * returns a link to the blog entry. or only the url.
    *
    * @param blogEntryUid $uid
    * @param blogEntryDate $date
    * @param unknown_type $onlyUrl
    * @return unknown
    */
   function getPermalink($uid,$date,$onlyUrl = false){
		
		// generate permalink               
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$tmpDate = date('d.m.Y',$date);
		$tmpDateArray = split('\.',$tmpDate);
                
		$conf = array(
			'parameter' => $GLOBALS['TSFE']->id,
			
			//'additionalParams' => '&tx_t3blog_pi1[blogList][year]='.$tmpDateArray[2].'&tx_t3blog_pi1[blogList][day]='.$tmpDateArray[0].'&tx_t3blog_pi1[blogList][month]='.$tmpDateArray[1].'&tx_t3blog_pi1[blogList][showUid]='.$uid,
            'additionalParams' => '&tx_t3blog_pi1[blogList][showUidPerma]='.$uid,
			'useCacheHash' => 1,
			'title'	=>	$this->pi_getLL('permalinkDesc')
   		);
		
		if ($onlyUrl === true) {
			$permaLink = $cObj->typoLink_URL($conf);	
		} else {
			$permaLink = $cObj->typoLink($this->pi_getLL('permalinkTitle'),$conf);	
		}
		
   		return $permaLink;
   }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.blogList.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.blogList.php']);
}
?>
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
class socialBookmarks extends tslib_pibase {
	var $prefixId      = 'socialBookmarks';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/socialBookmarks/class.socialBookmarks.php';	// Path to this script relative to the extension dir.
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
		$title = $this->pi_getLL('socialbookmarkingTitle');
		$content = '
		<!--
		* Social Bookmark Script
		* @ Version 1.8
		* @ Copyright (C) 2006-2007 by Alexander Hadj Hassine - All rights reserved
		* @ Website http://www.social-bookmark-script.de/
		-->		
			<script language="JavaScript" type="text/JavaScript">
				<!--
					function Social_Load() { 
					var d=document; if(d.images){ if(!d.Social) d.Social=new Array();
					var i,j=d.Social.length,a=Social_Load.arguments; for(i=0; i<a.length; i++)
					if (a[i].indexOf("#")!=0){ d.Social[j]=new Image; d.Social[j++].src=a[i];}}
					}
					Social_Load(\'http://www.social-bookmark-script.de/img/bookmarks/wong_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/webnews_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/icio_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/oneview_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/folkd_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/yigg_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/linkarena_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/newskick_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/linksilo_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/readster_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/seekxl_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/favit_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/sbdk_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/power_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/favoriten_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/bookmarkscc_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/newsider_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/digg_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/del_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/reddit_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/simpy_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/stumbleupon_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/slashdot_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/netscape_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/furl_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/yahoo_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/spurl_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/google_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/blinklist_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/blogmarks_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/diigo_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/technorati_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/newsvine_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/blinkbits_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/ma.gnolia_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/smarking_trans_ani.gif\',\'http://www.social-bookmark-script.de/img/bookmarks/netvouz_trans_ani.gif\',\'http://www.social-bookmark-script.de/load.gif\')
					function schnipp() { 
					var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
					}
					function schnupp(n, d) { 
					  var p,i,x; if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
					  d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
					  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
					  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=schnupp(n,d.layers[i].document);
					  if(!x && d.getElementById) x=d.getElementById(n); return x;
					  }
					function schnapp() { 
					  var i,j=0,x,a=schnapp.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
					  if ((x=schnupp(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
					  }
				//-->
			</script>
		'.$this->conf['bookmarks'];

		return t3blog_div::getSingle(array('data'=>$content,'title'=>$title),'globalWrap');
	}


	/**
	 * Initial Method
	 */
	function init(){
		$this->pi_loadLL();
		
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/socialBookmarks/class.socialBookmarks.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/socialBookmarks/class.socialBookmarks.php']);
}

?>
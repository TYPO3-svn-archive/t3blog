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
class singleFunctions extends blogList {
	var $prefixId      = 'tx_t3blog_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.singleFunctions.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = false;
	var $prevPrefixId = 'blogList';
    var $uid;            

	/**
	 * The main method of the widget
	 * Retuns a single Blogpost
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf,$piVars){
        
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId]; //blogList pvars
        $this->uid  = isset($this->localPiVars['showUid']) ? $this->localPiVars['showUid'] : $this->localPiVars['showUidPerma'];
		$this->conf = $conf;
		$this->init();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$message = '';
             
		
		//  saves the trackback if one is incoming from outside with the right parameters
		if(intval($this->globalPiVars['trackback'])==1) {
					
			if(t3lib_div::_GP('title') && t3lib_div::_GP('blog_name') && t3lib_div::_GP('excerpt')){
				$this->trackback();
			}
		}
		
		
		// returns the comment form if it is called by ajax.
		if($this->localPiVars['isAjax'] == 1)	{
			if($this->localPiVars['createCommentForm'] == 1)	{
				$this->showCommentForm();
			}
		}

		// inserts a comment (+send notification email to admin)
		if($this->localPiVars['insert']==1){
			if ($this->insertComment()) {
				if ($this->conf['mailReceivedCommentsToAdmin'] == 1) {
					$this->adminMailComment();
				}
				// if it first has to be approved, contact the writer
				if($this->conf['approved'] == 0 ){
					$message = $this->pi_getLL('toBeApproved');
				}
				
			}
		}


		// shows the blog entry if a "showUid" is set.
		if($this->uid){
			$text = '';
			$row = t3blog_db::getRecFromDbJoinTables(
				'tx_t3blog_post, be_users',  //  TABLES
				'tx_t3blog_post.uid as postuid, tx_t3blog_post.title, tx_t3blog_post.author, tx_t3blog_post.date, tx_t3blog_post.cat, tx_t3blog_post.allow_comments, be_users.uid, be_users.username, be_users.email, be_users.admin, be_users.admin, be_users.realName, be_users.uid AS useruid, be_users.lastlogin , be_users.tx_t3blog_avatar',	// SELECT
				'tx_t3blog_post.uid = '.t3lib_div::intval_positive($this->uid).' AND (be_users.uid = tx_t3blog_post.author)',	// WHERE
				'',	// ORDER BY
				'0,1'	// LIMIT
			);
			
			
		
			if($row){	
				$row = $row[0];
				if($this->conf['gravatar']){	// set Gravatar
					$gravatar = $this->getGravatar($row['useruid'], $row['email'], $row['realName']);
				}else{
					$gravatar = '';
				}
				
					// set the title of the single view page to the title of the blog post
				if ($this->conf['substitutePagetitle']) {
					$GLOBALS['TSFE']->page['title'] = $row['title'];
					// set pagetitle for indexed search to news title
					$GLOBALS['TSFE']->indexedDocTitle = $row['title'];
				}
				
				// generate TrackbackLink
				$cObj = t3lib_div::makeInstance('tslib_cObj');
				
				$tmpDate = date('d.m.Y',$row['date']);
				$tmpDateArray = split('\.',$tmpDate);
				$linkConf = array(
					'parameter'	=> $GLOBALS['TSFE']->id,
					//'additionalParams'	=> '&tx_t3blog_pi1[trackback]=1&tx_t3blog_pi1[blogList][year]='.$tmpDateArray[2].'&tx_t3blog_pi1[blogList][day]='.$tmpDateArray[0].'&tx_t3blog_pi1[blogList][month]='.$tmpDateArray[1].'&&tx_t3blog_pi1[blogList][trackback]=1&tx_t3blog_pi1[blogList][showUid]='.$this->uid,
					'additionalParams'	=> '&tx_t3blog_pi1[trackback]=1&tx_t3blog_pi1[blogList][year]='.$tmpDateArray[2].'&tx_t3blog_pi1[blogList][day]='.$tmpDateArray[0].'&tx_t3blog_pi1[blogList][month]='.$tmpDateArray[1].'&tx_t3blog_pi1[blogList][showUid]='.$this->uid,
					'title'	=>	$this->pi_getLL('trackbackLinkDesc')
				);
				$trackbackLink = $cObj->typoLink($this->pi_getLL('trackbackLink'), $linkConf);

				$data = array(
					'uid'			=>	$row['postuid'],
					'title'			=>	$this->getTitleLinked($row['title'], $this->uid, $row['date']),
					'date'			=>	$this->getDate($row['date']),
					'time'			=>	$this->getTime($row['date']),
					'author'		=>	$this->getAuthor($row['realName']),
					'authorId'		=>	$row['author'],
					'gravatar'		=>	$gravatar,
					'email' 		=>	$row['email'],
					'category'		=>	$this->getCategoriesLinked($row['postuid']),
					'back'			=>	$this->pi_getLL('back'),
					'trackbackLink'	=>	$trackbackLink,
					'comments'		=>	$this->listComments($row['date']),
					'message'		=> 	$message,
					'trackbacks'	=>	$this->listTrackbacks(),
					'tipafriendlinkText'=>	($this->conf['useTipAFriend']?$this->pi_getLL('tipafriendlinkText'):''),
					'blogUrl'		=>	'http://'.t3lib_div::getIndpEnv('HTTP_HOST').'/'.rawurlencode($this->getPermalink($this->uid, $row['date'], true)),
					'permalink'		=> 	$this->getPermalink($this->uid,$row['date']),
					'addcomment'	=> (!$this->localPiVars['isAjax']) ? $this->showCommentForm($row['allow_comments']) : $this->addCommentToPost($this->uid),
				);
		
				$content = t3blog_div::getSingle($data, 'single');
				$content = ereg_replace('###MORE###', '', $content);
				
			} else {
				$content = '';
			}

		} else {
			$content = '';
		}

		if($content)	{
			$content = $this->getSingleNavigation($this->uid). $content;
		}

		return $content;

	}


	/**
	 * Function to add a comment to blog-post
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 *
	 * @param int $postUid
	 */
	function addCommentToPost($postUid = 1)	{
		$data = array(
			'name'	  		=> 'add Comment',
			'url'			=> '\''.$this->pi_linkTP_keepPIvars_url(array($this->prevPrefixId => array_merge(array('isAjax' => 1, 'createCommentForm' => 1),$this->piVars[$this->prevPrefixId])),1).'\'',
			'urlforlink'	=> $this->pi_linkTP_keepPIvars_url(array($this->prevPrefixId => array_merge(array('createCommentForm' => 1),$this->piVars[$this->prevPrefixId])),1),
		);

		return t3blog_div::getSingle($data, 'addcommentlink');
	}


	/**
	 * shows the Comment Form
	 *
	 * @author Nicolas Karrer <nkarrer@snowflake.ch>
	 * @param $allowComments status 0,1,2 {0 = all, 1 = none, 2 = only registered users}
	 *
	 */
	function showCommentForm($allowComments)	{
		if($allowComments == 0 || ($allowComments == 2 &&  $GLOBALS['TSFE']->fe_user->user['uid']) ){
			$commentFormFields = array('commentauthor', 'commenttext','commentauthoremail', 'commentauthorwebsite', 'commenttitle', 'submit');
			//check if i'ts editing a comment
			$editUid = intval($this->localPiVars['editCommentUid']);
			if($editUid) {
				unset($this->localPiVars['editCommentUid']);
				unset($this->piVars[$this->prevPrefixId]['editCommentUid']);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',		// SELECT ...
					'tx_t3blog_com',		// FROM ...
					'uid = '.$editUid		// WHERE ...					
				);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				// check if the comment is yours (yes the second time. sure is sure)				
				if ( $this->allowedToEditComment($editUid)){
					// load the previous comment infos
					$this->localPiVars['commenttext'] = $row['text'];
					$this->localPiVars['commenttitle'] = $row['title'];
					$this->localPiVars['commentauthoremail'] = $row['email'];
					$this->localPiVars['commentauthorwebsite'] = $row['website'];
				} else {
					$editUid = 0;
				}
			}
			// load the commentdata from the cookie or fe_user
			foreach($commentFormFields as $fieldName)	{
				if(!isset($this->localPiVars[$fieldName])){
					//check if there is a cookie value set.
					switch ($fieldName){
						case 'commentauthor':
							if(isset($_COOKIE['currentCommentAuthor'])){
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentAuthor'];
							} else if($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = ($GLOBALS['TSFE']->fe_user->user['name']?$GLOBALS['TSFE']->fe_user->user['name']:$GLOBALS['TSFE']->fe_user->user['username']);
							}
						break;
						case 'commentauthoremail':
							if(isset($_COOKIE['currentCommentEmail'])){
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentEmail'];
							} else if($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = $GLOBALS['TSFE']->fe_user->user['email'];
							}
							break;
						case 'commentauthorwebsite':
							if(isset($_COOKIE['currentCommentWebsite'])){
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentWebsite'];
							} else if($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = $GLOBALS['TSFE']->fe_user->user['www'];
							}
						break;
					}
				}
				//set * if required.
				$requiredFieldsarr = explode(',',strtolower($this->conf['requiredFields']));
				$requiredFieldsarr = str_replace(' ','',$requiredFieldsarr);
				$requiredMarker = '';
				if(in_array(strtolower($fieldName),$requiredFieldsarr)){
					$requiredMarker = ' '.t3blog_div::getSingle(array('marker'=>'*'),'requiredFieldMarkerWrap');
				}
				//set the pi value as default value
				$data[$fieldName] = $this->localPiVars[$fieldName];
				$data[$fieldName.'_label'] = $this->pi_getLL($fieldName).$requiredMarker;
			}
			$data['commentTitle'] = $this->pi_getLL('commentFormTitle');
			$data['closeicon'] = '<img src="'.t3lib_extMgm::extRelPath('t3blog').'icons/window_close.png" />';
			$data['closelink'] = '';
			unset($this->piVars[$this->prevPrefixId]['createCommentForm']);
			$data['action'] =
			$this->pi_linkTP_keepPIvars_url(
				array (
						$this->prevPrefixId => (
						is_array($this->piVars[$this->prevPrefixId])?
							array_merge(
								array('isAjax' => $this->localPiVars['isAjax'],'insert'=>1,'uid'=>t3lib_div::intval_positive($this->uid)),
								$this->piVars[$this->prevPrefixId]
							)
						:
							array ('isAjax' => $this->localPiVars['isAjax'],'insert'=>1,'uid'=>t3lib_div::intval_positive($this->uid))
				)),
				1,
				0,
				0
			);
			// display error msg
			if($this->localPiVars['errorMsg']){
				$data['errorMsg'] = $this->localPiVars['errorMsg'];
				$data['errorTitle'] = $this->pi_getLL('errorTitle');
				unset($this->localPiVars['errorMsg']);
			}
			// set the comment editUid
			$data['editUid'] = $editUid;
			$content = t3blog_div::getSingle($data, 'commentForm');

			if($this->localPiVars['isAjax'] != 1)	{
				return '<div id="commentFormNonAjax" class="commentFormStyle">'.$content.'</div>';
			}

			die($content);
		}else{
			// return login status message
			if($allowComments == 1){
				// no comments allowed at all
				return t3blog_div::getSingle(array('text'=>$this->pi_getLL('notAllowedToComment')),'noCommentAllowedWrap');
			}else{
				// not logged in message				
				
				$returnLink = $this->pi_linkTP_keepPIvars_url(array(),1,0,$GLOBALS['TSFE']->id);
				return t3blog_div::getSingle(
					array(
						'text'=>$this->pi_getLL('notAllowedToComment'),
						'loginPid'=>$this->conf['loginPid'],
						'loginLinkText'=>$this->pi_getLL('loginLinkText'),
						'redirect_url'=>'http://' . t3lib_div::getIndpEnv('HTTP_HOST') . '/'.$returnLink
					),'noCommentAllowedWrap');
			}
			
		}
	}


	/**
	 * Lists the incoming trackbacks
	 *
	 * @return html listing of the trackbacks
	 */
	function listTrackbacks(){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,crdate,fromurl,text,title,blogname',																										// SELECT ...
			'tx_t3blog_trackback',																															// FROM ...
			'pid = '.$GLOBALS['TSFE']->id.' AND postid = '.t3lib_div::intval_positive($this->uid).' '.$this->cObj->enableFields('tx_t3blog_trackback'),		// WHERE ...
			'uid',																																			// GROUP BY ...
			'crdate'																																		// ORDER BY ...
			//''																																			// LIMIT ...
		);
		
				
		$trackbacks = '';
		for($i = 0; $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); $i++) {
			
			$permaid 	= strstr($row['fromurl'], 'bid=');
			$permaid 	= str_replace('bid=','',$permaid );
				
			$haystack 	= $row['fromurl'];
			$needle 	= '?id';
			$permaLink 	= substr($haystack, 0, strpos($haystack, $needle));
			
			
			$link		= 	$this->getPermalink($permaid,$row['date'],true);
			//$link		=  $permaLink.'?tx_t3blog_pi1[blogList][showUid]='.$permaid;
			
			
			$dataTrb = array(
				'uid'		=> $row['uid'],
				'odd'		=> $i%2==0?'odd':'even',
				'title'		=> $row['title'],
				'author'	=> $row['blogname'],
				'date'		=> $this->getDate($row['crdate']),
				'url'		=> $link,
				'text'		=> $row['text'].'...'
			);
			$trackbacks .= t3blog_div::getSingle($dataTrb, 'trackback');

		}
		
		$data = array(
			'pageBrowser' 	=> '',
			'trackbacks' 	=> $trackbacks,
			'title' 		=> $this->pi_getLL('trackbacksTitle'),
		);
		
		$content = t3blog_div::getSingle($data,'trackbackList');
		
		return $content;
	}


	/**
	 * lists all the comments. needed the showUid from the localpivars.
	 * @author manu oehler <moehler@snowflake.ch>
	 * @param date, send the date of the blogentry
	 * @return comment listing
	 * 
	 */
	function listComments($date = ''){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,title,author,email,website,date,text',		// SELECT ...
			'tx_t3blog_com',							// FROM ...
			'fk_post = '.t3lib_div::intval_positive($this->uid). ' AND pid = '. $GLOBALS['TSFE']->id. ' AND approved=1 AND spam=0 '. $this->cObj->enableFields('tx_t3blog_com'),		// WHERE ...
			'uid',		// GROUP BY ...
			'date'//,		// ORDER BY ...
			//''		// LIMIT ...
		);
		$comments = '';
		$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$editable = 0;
		for($i = 0; $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); $i++) {
			if($this->conf['gravatar'] && $this->conf['gravatarAtComments']){
				$gravatar = $this->getGravatar('', $row['email'], $row['author']);
			}else{
				$gravatar = '';
			}
			// sets the last comment editable if the user is logged in.
			if ($numRows == ($i + 1)) {
				if( $this->allowedToEditComment($row['uid']) ) {
					$editable = 1;
				}
			}
			$dataCom = array(
				'uid'		=> $row['uid'],
				'odd'		=> $i%2==0 ? 'odd' : 'even',
				'title'		=> $row['title'],
				'author'	=> $this->getAuthor($row['author']),
				'gravatar'	=> $gravatar,
				'date'		=> $this->getDate($row['date']),
				'email'		=> $row['email'],
				'website'	=> $row['website'],
				'text'		=> $row['text'],
				'blogUid'	=> $this->uid,
				'entrydate'	=> $date,
				'edit'		=> ($editable?$this->pi_getLL('editLink'):'')
			);
			$comments .= t3blog_div::getSingle($dataCom,'comment');
		}

		$data = array(
			'pageBrowser' 	=> '',
			'comments' 		=> $comments,
			'nrComments'	=> t3blog_db::getNumberOfCommentsByPostUid(t3lib_div::intval_positive($this->uid)),
			'title' 		=> $this->pi_getLL('commentsTitle'),
		);
		$content = t3blog_div::getSingle($data,'commentList');

		return $content;
	}


	/**
	 * checks if the field is required
	 *
	 * @param fieldvalue $value
	 * @param fieldname $fieldname
	 * @return true if it is all okay. false if the field value needs content
	 */
	function checkRequired($value,$fieldname){
		$requiredFieldsarr = explode(',', strtolower($this->conf['requiredFields']));
		$requiredFieldsarr = str_replace(' ', '', $requiredFieldsarr);
		if(in_array(strtolower($fieldname), $requiredFieldsarr)){
			return ($value) ? true : false;
		}

		return true;
	}


	/**
	 * inserts a comment to the blog entry
	 * @author manu Oehler <moehler@snowflake.ch>
	 *
	 * @return bool (success?)
	 */
	function insertComment(){
		$table ='tx_t3blog_com';
		
		// get allowed fe_group of the post
		$res 		= $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'fe_group',				
					'tx_t3blog_post',		
					'uid = '.t3lib_div::intval_positive($this->localPiVars['uid'])					
					);
		$rowpost 	= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		
				
		//get all parameters
		$uid 		= t3lib_div::intval_positive($this->localPiVars['uid']);
		$author 	= strip_tags($this->localPiVars['commentauthor']);
		$title 		= strip_tags($this->localPiVars['commenttitle']);
		$email 		= $this->localPiVars['commentauthoremail'];
		$website 	= htmlspecialchars($this->localPiVars['commentauthorwebsite']);
		// keep the text as it is for spam checking. stripping will be done later on
		//$text 		= nl2br(strip_tags($this->localPiVars['commenttext']));
		$text 		= $this->localPiVars['commenttext'];
		$editUid 	= intval($this->localPiVars['editUid']);
		$fe_group	= $rowpost['fe_group'];
		
		if(isset($author)) {	// set cookie
		    setcookie("currentCommentAuthor", $author, time()+36000, "/");
		    setcookie("currentCommentEmail", $email, time()+36000, "/");
		    setcookie("currentCommentWebsite", $website, time()+36000, "/");
	    }

	    list($error, $errorMsg) = array(false, '');	//check entry
	    if(!$this->checkRequired($author, 'commentauthor')){	//check author
    		$error = true;
    		$errorMsg .= t3blog_div::getSingle(array('value' => $this->pi_getLL('error_commentauthor')), 'errorWrap');
	   	}

		if(!$this->checkRequired($title,'commenttitle')){	//check title
    		$error = true;
    		$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commenttitle')),'errorWrap');
	   	}

		if(!$this->checkRequired($text,'commenttext')){	//check text
    		$error = true;
    		$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commenttext')),'errorWrap');
	   	}

		if(!$this->checkRequired($email,'commentauthoremail') 	//check email
			|| ($email && t3blog_div::checkEmail($email))){
    		$error = true;
    		$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commentauthoremail')),'errorWrap');
	   	}

		if(!$this->checkRequired($website,'commentauthorwebsite') 	//check website
			|| ($website && t3blog_div::checkExternalUrl($website))){
    		$error = true;
    		$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commentauthorwebsite')),'errorWrap');
	   	}

	    if($error){
	    	$this->localPiVars['errorMsg']=$errorMsg;
	    }else{
	    	// unset the comment form values
	    	unset($this->piVars[$this->prevPrefixId]['commenttitle']);
			unset($this->piVars[$this->prevPrefixId]['commenttext']);
			unset($this->piVars[$this->prevPrefixId]['uid']);
			unset($this->piVars[$this->prevPrefixId]['editUid']);
			unset($this->localPiVars['commenttext']);
			unset($this->localPiVars['commenttitle']);
			unset($this->localPiVars['editUid']);
			unset($this->localPiVars['uid']);

			
			$data = array(
					'fk_post'	=> $uid,
					'title'		=> $title,
					'author'	=> $author,
					'date'		=> time(),
					'fe_group'	=> $fe_group,
					'email'		=> $email,
					'website'	=> $website,
					'text'		=> $this->splitLongWordsInText(nl2br(strip_tags($text))),
					'approved'	=> ($this->conf['approved']==0 ?0:1)
			);
			
			//spamprotection
			$sfpantispam = new tx_sfpantispam_tslibfepreproc();
			
			if (!$sfpantispam->sendFormmail_preProcessVariables(array($author,$title,$website,$email,$text), $this))	{
				$data['spam'] = 1;
			}
			
				
			// Update or insert the comment	
			if ( $this->allowedToEditComment($editUid) ) {
				t3blog_db::insertViaTce(
					$table,
					$data,
					$GLOBALS['TSFE']->id,
					$editUid					
				);
			} else {
				t3blog_db::insertViaTce(
					$table,
					$data,
					$GLOBALS['TSFE']->id
				);
			}
			
	    }

	    return ! $error;
	}
	
	function splitLongWordsInText($text){
		$words = split(' ',$text);
		$return = '';
		foreach ($words AS $singleWord){
			if(strlen($singleWord)>64){
				//t3lib_div::debug($singleWord,'cut');
				$return .= chunk_split($singleWord,64,' ').' ';
			}else{
				$return .= $singleWord.' ';
			}
		}
		return $return;
		
	}


	/**
	 * Send a received Comment per email to the given admin's email address
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 */
	function adminMailComment()	{
		$pObjPiVars = t3lib_div::_POST('tx_t3blog_pi1');	// pObj piVars array
		$messageText = $this->cObj->fileResource($this->conf['adminsCommentMailTemplate']);
		$markerArray = array(
			'###TITLE###'		=> strip_tags($pObjPiVars['blogList']['commenttitle']),
			'###TEXT###'		=> nl2br(strip_tags($pObjPiVars['blogList']['commenttext'])),
			'###AUTHOR###'		=> strip_tags($this->localPiVars['commentauthor']),
			'###EMAIL###'		=> $this->localPiVars['commentauthoremail'],
			'###WEBSITE###'		=> htmlspecialchars($this->localPiVars['commentauthorwebsite']),
			'###IP###'			=> $_SERVER['REMOTE_ADDR'],
			'###TSFE###'		=> 'http://'. $_SERVER['HTTP_HOST'],
		);
		foreach($markerArray as $key => $val)	{
			if (strlen(trim($val)) < 1) {
				$markerArray[$key] = '-';
			}
		}
		$messageText = $this->cObj->substituteMarkerArray($messageText, $markerArray);

		t3lib_div::plainMailEncoded(
			$this->conf['adminsCommentsEmail'],			//email (receiver)
			$this->pi_getLL('commentAdminMailSubject'),	//subject
			$messageText								//message
		);
	}

	/**
	 * Builds the Navigation for the Single view (next/previous entries).
	 *
	 * @param int $current
	 *
	 * @return string
	 */
	function getSingleNavigation($current)	{
		include_once('class.listFunctions.php');

		$items = listFunctions::getListItems(false, true);
		$data = array();

		foreach($items as $key => $item)	{
			if($item['uid'] == $current)	{
				if($items[$key+1])	{
					$title = $items[$key+1]['title'];
					if(strlen($title)>28){
						$title = substr($title,0,25).'...';
					}
					$data['next'] = $this->getTitleLinked($title, $items[$key+1]['uid'], $items[$key+1]['crdate'], 'singleNavTitleLink',$items[$key+1]['title']);
				}
				$data['backId'] = t3blog_div::getBlogPid();
				$data['backText'] = $this->pi_getLL('backText');
				if($items[$key-1])	{
					$title = $items[$key-1]['title'];
					if(strlen($title)>28){
						$title = substr($title,0,25).'...';
					}
					$data['previous'] = $this->getTitleLinked($title, $items[$key-1]['uid'], $items[$key-1]['crdate'], 'singleNavTitleLink',$items[$key+1]['title']);
				}

				return t3blog_div::getSingle($data, 'singleNavigation');
			}
		}
	}
	
	
	/**
	 * checks if the comment is one of the currently logged in user
	 *
	 * @param int of the comment id $editUid
	 * @return true or false 
	 */
	function allowedToEditComment($editUid){
		if($editUid){
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'email, author',		// SELECT ...
				'tx_t3blog_com',		// FROM ...
				'uid = '.$editUid		// WHERE ...					
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ( $GLOBALS['TSFE']->fe_user->user['email'] == $row['email'] && $GLOBALS['TSFE']->fe_user->user['name'] == $row['author']){
				return true;
			}
		}
		return false;
	}


	/**
	 * Gets the trackback data and saves it, if necessary
	 *
	 */
	function trackback(){
		
		// Respond the answer as a xml
		header('Content-Type: text/xml');

		// include the trackback class
		include_once(t3lib_extMgm::extPath('t3blog').'pi1/lib/trackback_cls.php');

		// gets blog name
		$resPage = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title',							// SELECT ...
			'pages',							// FROM ...
			'uid = '.$GLOBALS['TSFE']->id,		// WHERE ...
			'uid',								// GROUP BY ...
			'uid',								// ORDER BY ...
			'0,1'								// LIMIT ...
		);
		
		$rowPage 		= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resPage);
		$t3blog_name 	= $rowPage['title'];
			
		$trackback 		= new Trackback($t3blog_name,'Admin','UTF-8');

		// get the trackback parameters
		$tb_id 			= t3lib_div::_GP('id');
		$tb_url 		= t3lib_div::_GP('url');
		$tb_title 		= t3lib_div::_GP('title');
		$tb_expert 		= t3lib_div::_GP('excerpt');
		$tb_blogname 	= t3lib_div::_GP('blog_name');


		// save trackback or update, into array first
		$table = 'tx_t3blog_trackback';
		$data = array(
			'fromurl'	=>	$GLOBALS['TYPO3_DB']->quoteStr($tb_url, $table),
			'title'		=>	$GLOBALS['TYPO3_DB']->quoteStr($tb_title, $table),
			'postid'	=>	intval($this->uid),
			'blogname'	=>	$GLOBALS['TYPO3_DB']->quoteStr($tb_blogname, $table),
			'text'		=>	$GLOBALS['TYPO3_DB']->quoteStr(strip_tags($tb_expert), $table),
		);

		// get a similar trackback (same blog with same title from same url)
		$resTrackback = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',																																								// SELECT ...
			$table,																																								// FROM ...
			'fromurl = \''.$tb_url.'\' AND title = \''. $tb_title. '\' AND blogname = \''. $tb_blogname.'\' AND postid = '.$this->uid. ' AND deleted = 0 AND hidden = 0',		// WHERE ...
			'uid',																																								// GROUP BY ...
			'uid',																																								// ORDER BY ...
			'0,1'																																								// LIMIT ...
		);
		
		$rowTrackback = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resTrackback);
		
		// New if there is no data found, else an update
		if ($rowTrackback['uid']){
			$uid = $rowTrackback['uid'];
		} else {
			$uid = 'NEW';
		}
		
		// call insert/update via tce
		t3blog_db::insertViaTce($table,$data,$GLOBALS['TSFE']->id,$uid);

		//XML respond to the sender.
		echo $trackback->recieve(true);
		exit; // Exit, that only the xml is the respond
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php']);
}
?>
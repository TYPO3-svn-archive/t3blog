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

/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 *
 * @author		snowflake <typo3@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */
class singleFunctions extends blogList {
	var $prefixId      = 'tx_t3blog_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/blogList/class.singleFunctions.php';	// Path to this script relative to the extension dir.
	var $pi_checkCHash = false;
	var $prevPrefixId = 'blogList';
	protected $uid = 0;

	/**
	 * Initializes the widget.
	 *
	 * @param array $conf
	 * @param array $piVars
	 * @return void
	 */
	function init(array $conf, array $piVars) {
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prevPrefixId];
		$this->conf = $conf;

		parent::init();

		$this->setPostUid();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * The main method of the widget
	 * Retuns a single Blogpost
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, array $conf, array $piVars) {
		$this->init($conf, $piVars);

		$message = $this->unsubscribeFromComments();
		$this->checkForTrackbacks();

		// inserts a comment (+send notification email to admin)
		if($this->localPiVars['insert']==1){
			if ($this->insertComment()) {
				// Todo: and not spam!
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
		$content = '';
		if ($this->uid) {
			// Rise the number of views
			if ($this->checkRiseViewNumber() === true) {
				$this->riseViewNumber($this->uid);
			}


			$text = '';
			$row = t3blog_db::getRecFromDbJoinTables(
				'tx_t3blog_post, be_users',  //  TABLES
				'tx_t3blog_post.uid as postuid, tx_t3blog_post.title, tx_t3blog_post.tagClouds,tx_t3blog_post.author, tx_t3blog_post.date, tx_t3blog_post.cat, tx_t3blog_post.allow_comments,tx_t3blog_post.number_views, be_users.uid, be_users.username, be_users.email, be_users.admin, be_users.admin, be_users.realName, be_users.uid AS useruid, be_users.lastlogin , be_users.tx_t3blog_avatar',	// SELECT
				'tx_t3blog_post.uid = '.t3lib_div::intval_positive($this->uid).' AND (be_users.uid = tx_t3blog_post.author)',	// WHERE
				'',	// ORDER BY
				'0,1'	// LIMIT
			);


			if($row) {
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

				$dateInfo = getdate($row['date']);
				$trackBackParameters = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
					'trackback' => 1,
					'blogList' => array(
						'day' => sprintf('%02d', $dateInfo['mday']),
						'month' => sprintf('%02d', $dateInfo['mon']),
						'year' => $dateInfo['year'],
						'showUid' => $this->uid
					)
				));
				$linkConf = array(
					'additionalParams'	=> $trackBackParameters,
					'parameter'	=> t3blog_div::getBlogPid(),
					'title'	=>	$this->pi_getLL('trackbackLinkDesc'),
					'useCacheHash' => true
				);
				$trackbackLink = $cObj->typoLink($this->pi_getLL('trackbackLink'), $linkConf);

				if (!$this->localPiVars['year']) {
					$this->localPiVars['year'] = $dateInfo['year'];
					$this->localPiVars['month'] = $dateInfo['mon'];
					$this->localPiVars['day'] = $dateInfo['mday'];
				}
				if (!$this->localPiVars['showUid'] && $this->localPiVars['showUidPerma']) {
					// Dmitry: this is dirty :(
					// FIXME Need to refactor this whole function completely
					$this->localPiVars['showUid'] = $this->localPiVars['showUidPerma'];
				}

				$data = array(
					'uid'			=>	$row['postuid'],
					'blogPid'		=>	t3blog_div::getBlogPid(),
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
					'blogUrl'		=>	$this->getPermalink($this->uid, $row['date'], true),
					'permalink'		=> 	$this->getPermalink($this->uid,$row['date']),
					'addcomment'	=> $this->showCommentForm($row['allow_comments']),
					'tagClouds'		=>	$row['tagClouds'],
					'number_views'	=>	$this->getNumberOfViews($row['number_views']),
					'navigation'    => $this->getSingleNavigation($this->uid)
				);

				$content = t3blog_div::getSingle($data, 'single', $this->conf);
				$content = str_replace('###MORE###', '', $content);
			}
		}

		return $content;

	}

	/**
	 * Unsubscribes from comments and returns HTML code to display a corresponding
	 * message if necessary.
	 *
	 * @return string
	 */
	protected function unsubscribeFromComments() {
		$result = '';
		if ($this->localPiVars['unsubscribe']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_t3blog_com_nl',
				'code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->localPiVars['code'], 'tx_t3blog_com_nl'),
				array('deleted' => 1));
			$result = '<script>alert("'.$this->pi_getLL('subscribe.unsubscribe.succesfully').'");</script>';
		}
		return $result;
	}

	/**
	 * Sets the uid of the current post from the URL parameters.
	 *
	 * @return void
	 */
	protected function setPostUid() {
		if (isset($this->localPiVars['showUid'])) {
			$this->uid = intval($this->localPiVars['showUid']);
		}
		else if (isset($this->localPiVars['showUidPerma'])) {
			// showUidPerma is deprecated!
			$this->uid = intval($this->localPiVars['showUidPerma']);
		}
	}

	/**
	 * Checks if trackbacks has to be sent
	 *
	 * @return void
	 */
	protected function checkForTrackbacks() {
		if (intval($this->globalPiVars['trackback']) == 1) {
			if (t3lib_div::_GP('title') && t3lib_div::_GP('blog_name') && t3lib_div::_GP('excerpt') && t3lib_div::_GP('url')) {
				$this->trackback();
			}
		}
	}


	/**
	 * shows the Comment Form
	 *
	 * @author 	Nicolas Karrer <nkarrer@snowflake.ch>
	 * @param 	int		$allowComments: status 0,1,2 {0 = all, 1 = none, 2 = only registered users}
	 */
	function showCommentForm($allowComments)	{
		if ($allowComments == 0 || ($allowComments == 2 && $GLOBALS['TSFE']->fe_user->user['uid'])) {

			// comment comments
			if ($this->localPiVars['comParentId'] > 0) {
				$commentFormFields = array('comParentId','commentauthor', 'commenttext','commentauthoremail', 'commentauthorwebsite', 'commenttitle', 'submit');
			}
			else {
				$commentFormFields = array('commentauthor', 'commenttext','commentauthoremail', 'commentauthorwebsite', 'commenttitle', 'submit');
			}

			// captcha image
			if ($this->conf['useCaptcha'] == 1) {
				array_push($commentFormFields, 'captcha', 'captchaimage');
				$captchaHTMLoutput = '<img src="' . t3lib_extMgm::siteRelPath('t3blog') .
					'pi1/widgets/blogList/captcha/captcha.php?' .
					'font=' . htmlspecialchars($this->conf['captchaFont']) .
					'&amp;fontSize=' . htmlspecialchars($this->conf['captchaFontSize']) .
					'&amp;fontColor=' . htmlspecialchars($this->conf['captchaFontColor']) .
					'&amp;fontEreg=' . htmlspecialchars($this->conf['captchaEreg']) .
					'&amp;image=' . htmlspecialchars($this->conf['captchaBackgroundPNGImage']) .
					'&amp;showImage=' . htmlspecialchars($this->conf['captchaShowImage']) .
					'&amp;backgroundColor=' . htmlspecialchars($this->conf['captchaBackgroundColor']) .
					'&amp;lines=' . htmlspecialchars($this->conf['captchaLines']) .
					'" alt="" />';
			}

			// subscribe for comments
			if ($this->conf['subscribeForComments'] == 1) {
				array_push($commentFormFields, 'subscribe');
			}

			//check if i'ts editing a comment
			$editUid = intval($this->localPiVars['editCommentUid']);
			if ($editUid) {
				unset($this->localPiVars['editCommentUid']);
				unset($this->piVars[$this->prevPrefixId]['editCommentUid']);
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*', 'tx_t3blog_com', 'uid=' . $editUid
				);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				// check if the comment is yours (yes the second time. sure is sure)
				if ($this->allowedToEditComment($editUid)) {
					// load the previous comment infos
					$this->localPiVars['commenttext'] = $row['text'];
					$this->localPiVars['commenttitle'] = $row['title'];
					$this->localPiVars['commentauthoremail'] = $row['email'];
					$this->localPiVars['commentauthorwebsite'] = $row['website'];
				}
				else {
					$editUid = 0;
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			// load the commentdata from the cookie or fe_user
			foreach ($commentFormFields as $fieldName) {
				if (!isset($this->localPiVars[$fieldName])) {
					//check if there is a cookie value set.
					switch ($fieldName){
						case 'commentauthor':
							if(isset($_COOKIE['currentCommentAuthor'])) {
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentAuthor'];
							}
							else if ($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = strlen($GLOBALS['TSFE']->fe_user->user['name']) ? $GLOBALS['TSFE']->fe_user->user['name'] : $GLOBALS['TSFE']->fe_user->user['username'];
							}
						break;
						case 'commentauthoremail':
							if (isset($_COOKIE['currentCommentEmail'])) {
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentEmail'];
							}
							else if($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = $GLOBALS['TSFE']->fe_user->user['email'];
							}
							break;
						case 'commentauthorwebsite':
							if(isset($_COOKIE['currentCommentWebsite'])){
								$this->localPiVars[$fieldName] = $_COOKIE['currentCommentWebsite'];
							}
							else if($GLOBALS['TSFE']->loginUser) {
								$this->localPiVars[$fieldName] = $GLOBALS['TSFE']->fe_user->user['www'];
							}
						break;
					}
				}
				//set * if required.
				$requiredFieldsarr = explode(',',mb_strtolower($this->conf['requiredFields']));
				$requiredFieldsarr = str_replace(' ','',$requiredFieldsarr);
				$requiredMarker = '';
				if(in_array(strtolower($fieldName), $requiredFieldsarr)){
					$requiredMarker = ' ' . t3blog_div::getSingle(array('marker'=>'*'), 'requiredFieldMarkerWrap', $this->conf);
				}
				//set the pi value as default value
				$data[$fieldName] = $this->localPiVars[$fieldName];
				$data[$fieldName.'_label'] = $this->pi_getLL($fieldName).$requiredMarker;
			}
			// captcha
			if ($this->conf['useCaptcha'] == 1) {
				$data['captchaimage']	= $captchaHTMLoutput;
				$data['captcha']		= 'tx_t3blog_pi1[blogList][captcha]';
			}

			// subscribe for comments
			if ($this->conf['subscribeForComments'] == 1) {
				$postVars = t3lib_div::_POST('tx_t3blog_pi1');
				if ($postVars['blogList']['subscribe']) {
					$data['subscribe'] = 'checked="checked"';
				}
				else {
					$data['subscribe'] = ' ';
				}
				$data['subscribe_text']	= $this->pi_getLL('subscribe_text');
			}

			$data['readOnly']		= isset($GLOBALS['TSFE']->fe_user->user['uid']) && $this->conf['readOnly'] == 1 ? 'readonly="readonly"' : '';
			$data['parentTitle']    = $this->localPiVars['comParentTitle'];
			$data['commentTitle'] 	= $this->pi_getLL('commentFormTitle');
			$data['closeicon'] 		= '<img src="'.t3lib_extMgm::extRelPath('t3blog').'icons/window_close.png" alt="" />';
			$data['closelink'] 		= '';
			unset($this->piVars[$this->prevPrefixId]['createCommentForm']);

			$data['action'] = htmlspecialchars($this->getCommentFormAction());

			// display error msg
			if($this->localPiVars['errorMsg']){
				$data['errorMsg'] = $this->localPiVars['errorMsg'];
				$data['errorTitle'] = $this->pi_getLL('errorTitle');
				unset($this->localPiVars['errorMsg']);
			}
			// set the comment editUid
			$data['editUid'] = $editUid;
			$content = t3blog_div::getSingle($data, 'commentForm', $this->conf);

			return '<div id="commentFormNonAjax" class="commentFormStyle">' .
				$content .
				'</div>';
		}
		else {
			// return login status message
			if ($allowComments == 1) {
				// no comments allowed at all
				return t3blog_div::getSingle(array(
					'text' => $this->pi_getLL('notAllowedToComment')),
						'noCommentAllowedWrap', $this->conf);
			}
			else {
				// not logged in message
				$returnLink = $this->pi_linkTP_keepPIvars_url(array(),1,0,$GLOBALS['TSFE']->id);
				return t3blog_div::getSingle(
					array(
						'text'=>$this->pi_getLL('notAllowedToComment'),
						'loginPid'=>$this->conf['loginPid'],
						'loginLinkText'=>$this->pi_getLL('loginLinkText'),
						'redirect_url'=> t3lib_div::locationHeaderUrl($returnLink)
					), 'noCommentAllowedWrap', $this->conf);
			}
		}
	}

	/**
	 * Creates a comment form action URL.
	 *
	 * @return string
	 */
	protected function getCommentFormAction() {
		$actionParameters = t3lib_div::array_merge_recursive_overrule(
			$this->localPiVars,
			array(
				'insert' => 1,
				'uid' => $this->uid
			)
		);
		foreach ($actionParameters as $parameterName => $parameterValue) {
			if (substr($parameterName, 0, 7) == 'comment') {
				unset($actionParameters[$parameterName]);
			}
		}
		return $this->pi_linkTP_keepPIvars_url(
			array (
				$this->prevPrefixId => $actionParameters
			)
		);
	}


	/**
	 * Lists the incoming trackbacks
	 *
	 * @return html listing of the trackbacks
	 */
	function listTrackbacks() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,crdate,fromurl,text,title,blogname',																										// SELECT ...
			'tx_t3blog_trackback',																															// FROM ...
			'pid = '.$GLOBALS['TSFE']->id.' AND postid = '.t3lib_div::intval_positive($this->uid).' '.$this->cObj->enableFields('tx_t3blog_trackback'),		// WHERE ...
			'uid',																																			// GROUP BY ...
			'crdate'																																		// ORDER BY ...
			//''																																			// LIMIT ...
		);

		$trackbacks = '';
		for ($i = 0; false != ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {

			$link = '';
			$permaidPos = strpos($row['fromurl'], 'bid=');
			if ($permaidPos !== false) {
				$permaid = intval(substr($row['fromurl'], $permaidPos + 4));
				if ($permaid) {
					$urlParts = @parse_url($row['fromurl']);
					if (is_array($urlParts) && isset($urlParts['host']) && $urlParts['host'] == t3lib_div::getIndpEnv('HTTP_HOST')) {
						// Only if the same t3blog host. Is it necessary at all to calculate this URL again?
						// No htmlspecialchars here!
						$link = $this->getPermalink($permaid,$row['date'],true);
					}
				}
			}
			if (!$link) {
				$link = htmlspecialchars($row['fromurl']);
			}

			$dataTrb = array(
				'uid'		=> $row['uid'],
				'odd'		=> $i%2==0?'odd':'even',
				'title'		=> htmlspecialchars($row['title']),
				'author'	=> htmlspecialchars($row['blogname']),
				'date'		=> $this->getDate($row['crdate']),
				'time'		=> $this->getTime($row['crdate']),
				'url'		=> $link,
				'text'		=> htmlspecialchars(strip_tags($row['text']) . '...')
			);

			$trackbacks .= t3blog_div::getSingle($dataTrb, 'trackback', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$data = array(
			'pageBrowser' 	=> '',
			'trackbacks' 	=> $trackbacks,
			'title' 		=> $this->pi_getLL('trackbacksTitle'),
		);

		$content = t3blog_div::getSingle($data, 'trackbackList', $this->conf);

		return $content;
	}


	/**
	 * lists all the comments. needed the showUid from the localpivars.
	 *
	 * @author 	Manu Oehler <moehler@snowflake.ch>
	 * @param 	date	$date: send the date of the blogentry
	 *
	 * @return 	string	comment listing
	 *
	 */
	function listComments($date = '') {
		// FIXME pid is not necessary???
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,title,author,email,website,date,text,parent_id',
			'tx_t3blog_com',
			'parent_id = 0 AND fk_post=' . intval($this->uid) .
				' AND pid=' . t3blog_div::getBlogPid() .
				' AND approved=1 AND spam=0 '.
				$this->cObj->enableFields('tx_t3blog_com'),
			'',
			'date'
		);
		$comments = '';
		$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$editable = 0;
		for ($i = 0; false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {
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
				'time'		=> $this->getTime($row['date']),
				'email'		=> $row['email'],
				'website'	=> $row['website'],
				'text'		=> $row['text'],
				'blogUid'	=> $this->uid,
				'entrydate'	=> $date,
				'parentcom' => $row['parent_id'] > 0 || $this->conf['commentComments'] == 0 ? '' : $this->pi_getLL('commentComment'),
				'blog_uid'  => t3blog_div::getBlogPid(),
				'blog_year' => $this->localPiVars['year'],
				'blog_month'=> $this->localPiVars['month'],
				'blog_day' 	=> $this->localPiVars['day'],
				'edit' 		=> ($editable?$this->pi_getLL('editLink'):''),
				'parent_id' => $row['parent_id'],
				'fk_post' 	=> $this->localPiVars['showUid'],

			);

			$comments .= t3blog_div::getSingle($dataCom, 'comment', $this->conf);
			$comments .= $this->listCommentedComments($row['uid']);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		$data = array(
			'pageBrowser' 	=> '',
			'comments' 		=> $comments,
			'nrComments'	=> t3blog_db::getNumberOfCommentsByPostUid(t3lib_div::intval_positive($this->uid)),
			'title' 		=> $this->pi_getLL('commentsTitle'),
		);
		$content = t3blog_div::getSingle($data, 'commentList', $this->conf);

		return $content;
	}


	/**
	* Lists all the comments referenced to a parent comment.
	 * @author Thomas Imboden <timboden@snowflake.ch>
	 *
	 * @param       int		$parentId: UID of the parent comment
	 * @return      			comment listing
	 */
	protected function listCommentedComments($parentId){
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,title,author,email,website,date,text,parent_id',                                                                                                                                                                                                                                                                                                                                           // SELECT ...
				'tx_t3blog_com',                                                                                                                                                                                                                                                                                                                                                                                                                        // FROM ...
				'parent_id=' . intval($parentId) .
					' AND fk_post=' . $this->uid .
					' AND pid=' . t3blog_div::getBlogPid() .
					' AND approved=1 AND spam=0 ' .
					$this->cObj->enableFields('tx_t3blog_com'), '', 'date'
		);

		$comments = '';
		for ($i = 0; false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)); $i++) {
			if ($this->conf['gravatar']) {
				$gravatar = $this->getGravatar('', $row['email'], $row['author']);
			}
			else {
				$gravatar = '';
			}

			$dataCom = array(
				'uid'                   => $row['uid'],
				'odd'                   => $i%2==0 ? 'odd' : 'even',
				'title'                 => $row['title'],
				'author'                => $this->getAuthor($row['author']),
				'gravatar'              => $gravatar,
				'date'                  => $this->getDate($row['date']),
				'time'                  => $this->getTime($row['date']),
				'email'                 => $row['email'],
				'website'               => $row['website'],
				'text'                  => $row['text'],
				'margin'                => '20px',
			);

			$comments .= t3blog_div::getSingle($dataCom, 'comment', $this->conf);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $comments;
	}


	/**
	 * checks if the field is required
	 *
	 * @param 	string	$value: 	fieldvalue
	 * @param 	string	$fieldname: fieldname
	 *
	 * @return true if it is all okay. false if the field value needs content
	 */
	function checkRequired($value,$fieldname){
		$requiredFieldsarr = explode(',', strtolower($this->conf['requiredFields']));
		$requiredFieldsarr = str_replace(' ', '', $requiredFieldsarr);
		if (in_array(strtolower($fieldname), $requiredFieldsarr)) {
			return ($value) ? true : false;
		}

		return true;
	}


	/**
	 * inserts a comment to the blog entry
	 *
	 * @author manu Oehler <moehler@snowflake.ch>
	 *
	 * @return bool true on success
	 */
	function insertComment(){
		$table ='tx_t3blog_com';

		// get allowed fe_group of the post
		$rowpost = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'fe_group', 'tx_t3blog_post', 'uid=' . intval($this->localPiVars['uid'])
		);

		//get all parameters
		$uid 		= intval($this->localPiVars['uid']);
		$author 	= strip_tags($this->localPiVars['commentauthor']);
		$title 		= strip_tags($this->localPiVars['commenttitle']);
		$email 		= $this->localPiVars['commentauthoremail'];
		$website 	= htmlspecialchars($this->localPiVars['commentauthorwebsite']);

		// keep the text as it is for spam checking. stripping will be done later on
		$text 		= $this->localPiVars['commenttext'];
		$editUid 	= intval($this->localPiVars['editUid']);
		$fe_group	= $rowpost['fe_group'];

		if (isset($author)) {	// set cookie
			setcookie('currentCommentAuthor', $author, time()+36000, '/');
			setcookie('currentCommentEmail', $email, time()+36000, '/');
			setcookie('currentCommentWebsite', $website, time()+36000, '/');
		}

		list($error, $errorMsg) = array(false, '');	//check entry
		if (!$this->checkRequired($author, 'commentauthor')) {	//check author
			$error = true;
			$errorMsg .= t3blog_div::getSingle(array('value' => $this->pi_getLL('error_commentauthor')), 'errorWrap', $this->conf);
		}

		if (!$this->checkRequired($title,'commenttitle')) {	//check title
			$error = true;
			$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commenttitle')), 'errorWrap', $this->conf);
		}

		if (!$this->checkRequired($text,'commenttext')) {	//check text
			$error = true;
			$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commenttext')), 'errorWrap', $this->conf);
		}

		if (!$this->checkRequired($email,'commentauthoremail') 	//check email
				|| ($email && t3blog_div::checkEmail($email))) {
			$error = true;
			$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commentauthoremail')), 'errorWrap', $this->conf);
		}

		if (!$this->checkRequired($website,'commentauthorwebsite') 	//check website
				|| ($website && t3blog_div::checkExternalUrl($website))) {
			$error = true;
			$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_commentauthorwebsite')), 'errorWrap', $this->conf);
		}

		// captcha
		if ($this->conf['useCaptcha'] == 1) {
			session_start();
			$captchaStr = $_SESSION['tx_captcha_string'];
			$_SESSION['tx_captcha_string'] = '';

			if (!strlen($captchaStr) || $this->localPiVars['captcha'] != $captchaStr) {
				$error = true;
				$errorMsg .= t3blog_div::getSingle(array('value'=>$this->pi_getLL('error_captcha')), 'errorWrap', $this->conf);
			}
		}

		if ($error) {
			$this->localPiVars['errorMsg']=$errorMsg;
		}
		else {
			// unset the comment form values
			unset($this->piVars[$this->prevPrefixId]['commenttitle']);
			unset($this->piVars[$this->prevPrefixId]['commenttext']);
			unset($this->piVars[$this->prevPrefixId]['uid']);
			unset($this->piVars[$this->prevPrefixId]['editUid']);
			unset($this->localPiVars['commenttext']);
			unset($this->localPiVars['commenttitle']);
			unset($this->localPiVars['editUid']);
			unset($this->localPiVars['uid']);

			$time = time();
			$data = array(
				'tstamp'	=> $time,
				'title'		=> $title,
				'author'	=> $author,
				'fe_group'	=> $fe_group,
				'email'		=> $email,
				'website'	=> $website,
				'text'		=> $this->splitLongWordsInText(nl2br(strip_tags($text))),
				'approved'	=> ($this->conf['approved'] == 0 ? 0:1),
				'parent_id' => intval($this->localPiVars['comParentId']),
			);

			//spamprotection
			$sfpantispam = t3lib_div::makeInstance('tx_sfpantispam_tslibfepreproc');

			if (!$sfpantispam->sendFormmail_preProcessVariables(array($author, $title, $website, $email, $text), $this)) {
				$data['spam'] = 1;
			}

			// Update or insert the comment
			if ($this->allowedToEditComment($editUid)) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . $editUid, $data);
				$this->updateRefIndex($table, $editUid);

				//Hook after comment update
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentupdate'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentupdate'] as $userFunc) {
				  $params = array(
						'data' => &$data,
						'table' => $table,
						'postUid' => $uid,
						'commentUid' => $editUid,
					);
					t3lib_div::callUserFunction($userFunc, $params, $this);
				}
			}
			}
			else {
				// Insert comment
				$data['pid'] = t3blog_div::getBlogPid();
				$data['crdate'] = $time;
				$data['fk_post'] = $uid;
				$data['date'] = $time;
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $data);
				$this->updateRefIndex($table, $GLOBALS['TYPO3_DB']->sql_insert_id());

				//Hook after comment insertion
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentinsertion'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['aftercommentinsertion'] as $userFunc) {
				  $params = array(
						'data' => &$data,
						'table' => $table,
						'postUid' => $uid,
						'commentUid' => $GLOBALS['TYPO3_DB']->sql_insert_id(),
					);
					t3lib_div::callUserFunction($userFunc, $params, $this);
				}
			}
			}

			// send emails if comments must not be approved
			if ($this->conf['approved'] == 1) {

				// get users that subscribed to this post
				$table_send	= 'tx_t3blog_com_nl';
				$field_send	= '*';
				$where_send	= 'post_uid = '.$uid .' AND hidden = 0 AND deleted = 0';
				$subscriber	= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_send, $table_send, $where_send);

				// get name of the post
				$table_post	= 'tx_t3blog_post';
				$field_post	= 'title';
				$where_post = 'uid ='.$uid.' AND hidden = 0 AND deleted = 0';
				$post		= $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_post, $table_post, $where_post);
				$posttitle	= $post['0']['title'];

				foreach ($subscriber as $key => $value) {
					$table_com	= 'tx_t3blog_com';
					$field_com	= '*';
					$where_com	= 'date>' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value['lastsent'], $table_com) .
						' AND hidden=0 AND deleted=0 AND spam=0 AND approved=1';
					$comments = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_com, $table_com, $where_com);
					$message = '';

					if (count($comments) > 0) {
						// assemble email
						$unsubscribe	= '<' . $this->getUnsubscribeLink($uid, $value['code']) . '>' ."\n";
						$text			= '"'.trim($comments['0']['title']). ': '. trim($comments['0']['text']).'"'. "\n";
						$address		= str_replace(array('\\n', '\\r'), '', $value['email']);
						$receiver   	= $address;
						$subject		= $this->pi_getLL('subscribe.newComment').': '.$posttitle;
						$from       	= $this->conf['senderEmail'];
						$headers    	= 'From: ' . $from;

						$message       .= $this->pi_getLL('subscribe.salutation') .' '.$value['name'].','. "\n";
						$message       .= $this->pi_getLL('subscribe.notification') . "\n\n";
						$message       .= $text . "\n";

						// unsubscribe
						$message       .= $this->pi_getLL('subscribe.unsubscribe') ."\n";
						$message	   .= $unsubscribe;

						// send
						t3lib_div::plainMailEncoded($receiver,$subject,$message,$headers);
					}

					// update lastsent to the last comment time
					$where_lastsent = 'uid=' . intval($value['uid']);
					$fields_lastsent = array('lastsent' => $time);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table_send, $where_lastsent, $fields_lastsent);
				}
			}

			// insert if subscribe was marked
			if (isset($_POST['tx_t3blog_pi1']['blogList']['subscribe'])) {

				$table_nl 	= 'tx_t3blog_com_nl';

				// check if subscriber is already listed for this post
				$fields_nl = 'uid';
				$where_nl = 'post_uid = '.$uid .' AND email LIKE "'.$email.'" AND hidden = 0 AND deleted = 0';
				$check = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields_nl, $table_nl, $where_nl);

				// if email is inexistent
				if (count($check) == 0) {
					$code = $email.time();
					$code = md5($code);

					$data_nl = array(
						'pid'		=> t3blog_div::getBlogPid(),
						'tstamp'	=> time(),
						'crdate'	=> time(),
						'email'		=> $email,
						'name'		=> $author,
						'post_uid'	=> $uid,
						'lastsent'	=> time(),
						'code'		=> $code,
					);

					$GLOBALS['TYPO3_DB']->exec_INSERTquery($table_nl, $data_nl);

					// assemble confirmation email

					// get name of the post
					$table_post	= 'tx_t3blog_post';
					$field_post	= 'title';
					$where_post = 'uid=' . $uid . ' AND hidden = 0 AND deleted = 0';
					$post = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($field_post, $table_post, $where_post);
					$posttitle = $post['0']['title'];

					$message = '';
					$unsubscribe = '<' . $this->getUnsubscribeLink($uid, $code) . '>'."\n";
					$address = str_replace(array('\\n', '\\r'), '', $email);
					$receiver = $address;
					$subject = $this->pi_getLL('subscribe.confirmation').': '.$posttitle;
					$from = $this->conf['senderEmail'];
					$headers = 'From: ' . $from;

					$message .= $this->pi_getLL('subscribe.confirmationHello') .
						"\n" . $this->pi_getLL('subscribe.confirmationtext') . "\n";

					// unsubscribe
					$message .= $unsubscribe;

					// send
					t3lib_div::plainMailEncoded($receiver,$subject,$message,$headers);
				}
			}
		}

		return !$error;
	}

	/**
	 * splits long words in text
	 *
	 * @param 	string	$text: 	text to be splitted
	 *
	 * @return 	string	splitted string
	 */
	function splitLongWordsInText($text) {
		$stringLength = t3lib_div::intval_positive($this->conf['comment.']['splitLongWordsInComment']);
		// if the value is set to 0 return lines unsplitted
		if (!$stringLength) {
			return $text;
		}

		$words = explode(' ', $text);
		$return = '';
		foreach ($words AS $singleWord) {
			if (strlen($singleWord)>$stringLength) {
				$return .= chunk_split($singleWord, $stringLength, ' ') . ' ';
			}
			else{
				$return .= $singleWord . ' ';
			}
		}
		return $return;
	}

	/**
	 * Sends a received comment per email to the given admin's email address
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 */
	function adminMailComment()	{
		$pObjPiVars = t3lib_div::_POST('tx_t3blog_pi1');	// pObj piVars array
		$messageText = $this->cObj->fileResource($this->conf['adminsCommentMailTemplate']);
		$markerArray = array(
			'###TITLE###'		=> strip_tags($pObjPiVars['blogList']['commenttitle']),
			'###TEXT###'		=> strip_tags($pObjPiVars['blogList']['commenttext']),
			'###AUTHOR###'		=> $this->localPiVars['commentauthor'],
			'###EMAIL###'		=> $this->localPiVars['commentauthoremail'],
			'###WEBSITE###'		=> $this->localPiVars['commentauthorwebsite'],
			'###IP###'			=> t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'###TSFE###'		=> t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST')
		);
		foreach ($markerArray as $key => $val) {
			if (strlen(trim($val)) < 1) {
				$markerArray[$key] = '-';
			}
		}
		$messageText = $this->cObj->substituteMarkerArray($messageText, $markerArray);

		t3lib_div::plainMailEncoded(
			$this->conf['adminsCommentsEmail'],			//email (receiver)
			$this->pi_getLL('commentAdminMailSubject'),	//subject
			$messageText,								//message
			'From: ' . $this->conf['adminsCommentsEmail']
		);
	}

	/**
	 * Builds the Navigation for the Single view (next/previous entries).
	 *
	 * @param 	int $current: current navigation point
	 *
	 * @return 	string
	 */
	function getSingleNavigation($current)	{
		include_once('class.listFunctions.php');

		$listFunctions = t3lib_div::makeInstance('listFunctions');
		$listFunctions->cObj = $listFunctions->localcObj = $this->cObj;

		$this->conf['numberOfRecords'] = $listFunctions->getListItems(true, false);
		$listFunctions->conf = $this->conf;
		$items = $listFunctions->getListItems(false, true);
		$data = array();

		foreach ($items as $key => $item) {
			if ($item['uid'] == $current) {
				if ($items[$key+1]) {
					$title = $items[$key+1]['title'];
					if (strlen($title) > 28) {
						$title = t3lib_div::fixed_lgd_cs($title, 25);
					}
					$data['next'] = $this->getTitleLinked($title, $items[$key+1]['uid'], $items[$key+1]['crdate'], 'singleNavTitleLink',$items[$key+1]['title']);
				}
				$data['backId'] = t3blog_div::getBlogPid();
				$data['backText'] = $this->pi_getLL('backText');
				if($items[$key-1])	{
					$title = $items[$key-1]['title'];
					if(strlen($title)>28){
						$title = t3lib_div::fixed_lgd_cs($title, 25);
					}
					$data['previous'] = $this->getTitleLinked($title, $items[$key-1]['uid'], $items[$key-1]['crdate'], 'singleNavTitleLink',$items[$key+1]['title']);
				}

				return t3blog_div::getSingle($data, 'singleNavigation', $this->conf);
			}
		}
	}


	/**
	 * checks if the comment is one of the currently logged in user
	 *
	 * @param 	int $editUid: of the comment id $editUid
	 * @return 	true or false
	 */
	function allowedToEditComment($editUid){
		if ($editUid) {
			list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'email, author', 'tx_t3blog_com', 'uid=' . intval($editUid)
			);
			if ($GLOBALS['TSFE']->fe_user->user['email'] == $row['email'] &&
					$GLOBALS['TSFE']->fe_user->user['name'] == $row['author']) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Gets the trackback data and saves it, if necessary
	 *
	 */
	function trackback() {
		$this->trackbackAddData();
		$this->trackbackSendResponse();
		exit;
	}

	/**
	 * Adds trackback data to the database
	 *
	 * @return void
	 */
	function trackbackAddData() {
		// get the trackback parameters
		$trackbackUrl 		= t3lib_div::_GP('url');
		$trackbackTitle 		= t3lib_div::_GP('title');
		$trackbackExcerpt 		= t3lib_div::_GP('excerpt');
		$trackbackBlogName 	= t3lib_div::_GP('blog_name');

		// save trackback or update, into array first
		$table = 'tx_t3blog_trackback';

		// get a similar trackback (same blog with same title from same url)
		list($rowTrackback) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid', $table,
			'fromurl=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackUrl, $table) .
				' AND title=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackTitle, $table) .
				' AND blogname='. $GLOBALS['TYPO3_DB']->fullQuoteStr($trackbackBlogName, $table) .
				' AND postid=' . intval($this->uid) .
				$this->cObj->enableFields($table),
			'', 'uid', '0,1'
		);

		$data = array(
			'pid' => $GLOBALS['TSFE']->id,
			'fromurl' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackUrl, $table),
			'title' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackTitle, $table),
			'postid' => intval($this->uid),
			'blogname' => $GLOBALS['TYPO3_DB']->quoteStr($trackbackBlogName, $table),
			'text' => $GLOBALS['TYPO3_DB']->quoteStr(strip_tags($trackbackExcerpt), $table),
		);

		// New if there is no data found, else an update
		if (is_array($rowTrackback)) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . $rowTrackback['uid'], $data);
			$this->updateRefIndex($table, $rowTrackback['uid']);
		}
		else {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $data);
			$this->updateRefIndex($table, $GLOBALS['TYPO3_DB']->sql_insert_id());
		}
	}

	/**
	 * Sends XML response to trackback sender
	 *
	 * @return void
	 */
	function trackbackSendResponse() {
		$response = '<?xml version="1.0" encoding="UTF-8"?>' . chr(10) .
			'<response><error>0</error></response>';

		header('Content-Type: text/xml');
		header('Content-length: ' . strlen($response));

		echo $response;
	}

	/**
	* when shall we rise the number?
	*
	*/
	function checkRiseViewNumber (){
		$rise = false;

		/*
		 *
		 * or different possibilities: insert into table and then find out if user has already seen it?
		 * Wenn der gleiche User innerhalb einer bestimmten Zeit zugreift, wird der Zugriff nur als ein einzelner Zugriff gewertet.
		 * Der User wird aufgrund der IP, Browser, System identifiziert. Die Zeitspanne ist zu noch zu definieren.
		 * Thu, 03 Sep 2009 11:52:43 GMT
		 *
		 * Mit USER_INT arbeiten?
		 */

		// if a BE user is logged and these views should not be count we immediatly can switch back
		if($_COOKIE['be_typo_user'] and !($this->conf['countBEUsersViews'])){
			return($rise);
		}

		$cookieKey = md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'].$this->uid);
		if($_COOKIE['fe_typo_user'] and ($_COOKIE['T3BLOGvisit'.$cookieKey]!= $this->uid)){
			setcookie('T3BLOGvisit'.$cookieKey, $this->uid, time()+$this->conf['timePeriodToRecountView']);
			$rise = true;
		}elseif(!$_COOKIE['fe_typo_user']){ // cookies are not accepted by the user...
			$rise = false;
		}


		return($rise);

	}

	/**
	 * Rise number of views as soon as somebody has viewed the post in the single view
	 *
	 * @param int $postUID
	 * @return void
	 */
	function riseViewNumber($postUID) {
		$GLOBALS['TYPO3_DB']->sql_query('UPDATE tx_t3blog_post ' .
			'SET tx_t3blog_post.number_views=tx_t3blog_post.number_views+1 ' .
			'WHERE tx_t3blog_post.uid=' . $postUID);
	}

	/**
	 * Updates reference index for the table
	 *
	 * @return void
	 */
	protected function updateRefIndex($table, $id) {
		t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		if (!class_exists('t3lib_BEfunc', true)) {
			t3lib_div::requireOnce(PATH_t3lib . 'class.t3lib_refindex.php');
		}
		$refIndex = t3lib_div::makeInstance('t3lib_refindex');
		/* @var $refIndex t3lib_refindex */
		$refIndex->updateRefIndexTable($table, $uid);
	}

	/**
	 * Creates a link to unsubscribe from comment notifications
	 *
	 * @return string
	 */
	protected function getUnsubscribeLink($postUid, $code) {
		$additionalParams = t3lib_div::implodeArrayForUrl('tx_t3blog_pi1', array(
			'blogList' => array(
				'showUid' => $postUid,
				'unsubscribe' => 1,
				'code' => $code
			)));
		$typoLinkConf = array(
			'additionalParams' => $additionalParams,
			'parameter' => $GLOBALS['TSFE']->id,
			'no_cache' => true
		);
		$link = t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL($typoLinkConf));
		return $link;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/blogList/class.singleFunctions.php']);
}
?>
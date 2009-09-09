<?php
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_fe.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php' );
require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_feuserauth.php');
require_once(PATH_t3lib.'class.t3lib_cs.php');
require_once(PATH_site.'typo3/sysext/cms/tslib/class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
require_once(PATH_t3lib.'class.t3lib_page.php');


/**
 * blogfunctions
 * 
 * @package   	TYPO3
 * @author 		snowflake <info@snowflake.ch>
 * @copyright 	snowflake <info@snowflake.ch>
 * @access public
 */
class blogfunctions extends t3lib_SCbase {
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;		
		
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			
			// Get the page ID from the extension config
			$this->id = $_GET['id']; 

			// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

			// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
			} else {
			
			// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}


	/**
	 * Get the category names for a post
	 * 
	 * @param 	string	$table: Tablename
	 * @param 	array	$row: Datarow
	 * @return			List of category names
	 */
	function getCategoryNames($table, $row){
		$rsCatNames=$GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_t3blog_cat.catname as cat_names','tx_t3blog_cat,tx_t3blog_post_cat_mm','deleted=0 AND tx_t3blog_cat.uid = tx_t3blog_post_cat_mm.uid_foreign AND tx_t3blog_post_cat_mm.uid_local ='.$row['uid'].'','');
		while($dsCatNames=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($rsCatNames))
		{	

				$content .=$dsCatNames['cat_names'].' ';

		}
		
		return $content;
	}


	/**
	 * Generates a URL string
	 * 
	 * @param	string	$altID: id of the page
	 * @param	string	$table: name of the table 
	 * @param	string	$exclList: list
	 * @return	string	url
	 */
	function listURL($altId='',$table=-1,$exclList='')	{
		return 'index.php'.
			'?id='.(strcmp($altId,'')?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table:$table).

			($this->returnUrl?'&returnUrl='.rawurlencode($this->returnUrl):'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
		
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'')
			;
	}

	 
	 /**
	 * Creates a partial SQL-Query-String for a free-text search
	 * 
	 * @param	string	$table: name of the table 
	 * @return	string	search string
	 */
	function makeSearchString($table){
		global $TCA;
											
		// Loading full table description - we need to traverse fields:
		t3lib_div::loadTCA($table);
								
		// Initialize field array:
		$sfields=array();
		$sfields[]=$table.'.uid';	// Adding "uid" by default.
								
		// Traverse the configured columns and add all columns that can be searched:
		foreach($TCA[$table]['columns'] as $fieldName => $info)	{
			if ($info['config']['type']=='text' || ($info['config']['type']=='input' && !ereg('date|time|int',$info['config']['eval'])))	{
					$sfields[]=$table.'.'.$fieldName;
			}
		}
								
		// If search-fields were defined (and there always are) we create the query:
			if (count($sfields)) {
				$like = ' LIKE \'%'.$GLOBALS['TYPO3_DB']->quoteStr(t3lib_div::GPVar('search_field'), '$table').'%\'';		// Free-text searching...
				$queryPart = ' AND ('.implode($like.' OR ',$sfields).$like.')';
			}
			
			return $queryPart;
	}
	 
	 /**
	 * Creates a Searchbox
	 * 
	 * @param	boolean	$formFields  
	 * @return	string	code for search box
	 */
	function getSearchBox($formFields=1)	{

		// Setting form-elements, if applicable:
		$formElements=array('','');
		if ($formFields)	{
			$formElements=array('<form action="'.htmlspecialchars($this->listURL().'&curPage=1').'.&sort='.t3lib_div::GPVar('sort').'&sortDir='.t3lib_div::GPVar('sortDir').'&cat='.t3lib_div::GPVar('cat').'&pid='.t3lib_div::GPVar('pid').'" method="post">','</form>');
		}
		
		// Table with the search box:
		$content.= '
			'.$formElements[0].'

				<!--
					Search box:
				-->
				<table border="0" cellpadding="0" cellspacing="0" class="bgColor4" id="typo3-dblist-search">
					<tr>
						<td>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.enterSearchString',1).'<input type="text" name="search_field" value="'.htmlspecialchars(t3lib_div::GPVar('search_field')).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(10).' /></td>
						<td>'.$lMenu.'</td>
						<td><input type="submit" name="search" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.search',1).'" /></td>
					</tr>
				
				</table>
			'.$formElements[1];
		
		return $content;
	}	
	
	
	function truncate($string,$pos)  {
        if ( $pos < strlen($string) )
        {
            $text = substr($string, 0, $pos);
            if ( false !== ($strrpos = strrpos($text,' ')) )
            {
                $text = substr($text, 0, $strrpos);
            }
            $string = $text."...";
        }
        return $string;
    }			

	 
	 /**
	 * Sends the trackbacks and saves blogPost
	 * 
	 * @param	int		$uid: uid of the post
	 * @param	int		$pid: pid of the post
	 * @return	string	code for search box
	 */
	function sendTrackbacks($uid,$pid) {
		include_once(t3lib_extMgm::extPath('t3blog').'pi1/lib/trackback_cls.php');		
		
		// select trackback information of a post, even if not available
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, trackback, trackback_hash, author, title',						// SELECT ...
			'tx_t3blog_post',														// FROM ...
			'uid = '.$uid.' AND pid = '.$pid.' AND  deleted = 0 AND hidden = 0 ',	// WHERE ...
			'uid',																	// GROUP BY ...
			'uid',																	// ORDER BY ...
			'0,1'																	// LIMIT ...
		);
				
		
		$t3blog_name 	= '';
		$author 		= '';
		
		// base-url of the site
		$permalink 		= t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$title 			= $row['title'];
		$textPart 		= strip_tags($row['text']);
				
		// if a post is available
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
			
			// select header and bodytext of the post
			$resContent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'header, bodytext',																									// SELECT ...
				'tt_content',																										// FROM ...
				'deleted = 0 AND hidden = 0 AND irre_parenttable = \'tx_t3blog_post\' AND irre_parentid = '.$row['uid'].' ',		// WHERE ...
				'uid',																												// GROUP BY ...
				'sorting',																											// ORDER BY ...
				'0,1'																												// LIMIT ...
			);
			
			// if the post has content, set text
			if($rowContent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resContent)){
				
				$row['text'] = $rowContent['header'].$rowContent['bodytext'];
				
			} else {
				
				$row['text'] = '';
			}
			
			
			// generates froumurl for tx_t3blog_trackback		
			$additionalParams = array(
				'tx_t3blog_pi1[showUid]'	=>	$row['uid'],
			);
			
			$permalink = $this->buildTYPO3href($pid,$additionalParams);
									
			// gets real name of author of this post
			$resUser = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'realName',						// SELECT ...
				'be_users',						// FROM ...
				'uid = '.$row['author'],		// WHERE ...
				'uid',							// GROUP BY ...
				'uid',							// ORDER BY ...
				'0,1'							// LIMIT ...
			);
			
			$rowUser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resUser);
						
			if ($rowUser['realName']) {
				
				$author = $rowUser['realName'];
				
			} else {
				
				$author = 'Admin';				
			}
			
			
			// gets name of the main blog
			$resPage 		= $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'title',			// SELECT ...
				'pages',			// FROM ...
				'uid = '.$pid,		// WHERE ...
				'uid',				// GROUP BY ...
				'uid',				// ORDER BY ...
				'0,1'				// LIMIT ...
			);
			
			$rowPage 		= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resPage);
			$t3blog_name 	= $rowPage['title'];						
			$count 			= 0;
			
			// space = ascii: dec 32
			// only if trackback is set and the hash is not the same
			if(strlen($row['trackback']) > 0 && $row['trackback_hash'] != md5($row['trackback'])){
				
				// initialize trackback
				$trackback 	= new Trackback($t3blog_name, $author,'UTF-8');
				
				// split urls by tilde (chr126)
				$urls[] 	= split(chr(126),$row['trackback']);
				
				if (count($urls)>0 && $urls[0][0]!=''){
					$title 		= $row['title'];
					$textPart 	= substr($row['text'],0,250);
					foreach ($urls[0] AS $url){
						if ($url && strlen($url)>1 && strpos($url,'http')>-1) {
							if ($trackback->ping($url, $permalink, $title, $textPart)) {
								$count++;
							} 
						}					
					}
					
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
							'tx_t3blog_post', // table
							'uid = '.$row['uid'],
							array('trackback_hash'=>md5($row['trackback']))
					);
				}
			}
			
			return $count;
		
		} else {
			
			return 0;
		}		
	}
	
	
	/**
	  * Renders a TYPO3 href url
	  *
	  * @param    	integer $targetId: page id
	  * @param    	string  $addParams: additional parameters (getVars)
	  * @return		string  the link url, not being htmlspecialchar'ed yet
	 */
	function buildTYPO3href($targetId, $addParams='') {
		$link = 'http://'.t3lib_div::getIndpEnv('HTTP_HOST').'/?id='.$targetId.'&bid='.$addParams['tx_t3blog_pi1[showUid]'];
		return $link;
	}

	
	/**
	  * Include the CSS
	  *
	  * @return  string  link to the css-file
	 */
	function getCSS (){
		return(implode('',file('../lib/styles.css')));
	}
	
	
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/class.functions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/mod1/class.functions.php']);
}


?>
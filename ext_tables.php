<?php
	if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	t3lib_extMgm::allowTableOnStandardPages('tx_t3blog_post');
	t3lib_div::loadTCA('pages');
	$TCA['pages']['columns']['module']['config']['items'][] = Array('T3Blog', 't3blog');
	t3lib_extMgm::addToInsertRecords('tx_t3blog_post');

	$TCA['tx_t3blog_post'] = array (
		'ctrl' => array (
			'title'     			=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_post',
			'label'     			=> 'title',
			'tstamp'    			=> 'tstamp',
			'crdate'    			=> 'crdate',
			'cruser_id' 			=> 'cruser_id',
			'versioningWS' 			=> TRUE, 
			'origUid' 				=> 't3_origuid',
			'languageField'            => 'sys_language_uid',    
			'transOrigPointerField'    => 'l18n_parent',    
			'transOrigDiffSourceField' => 'l18n_diffsource',    
			'default_sortby' 		=> 'ORDER BY crdate DESC',
			'delete' 				=> 'deleted',
			'enablecolumns' 		=> array (
				'disabled' 	=> 'hidden',
				'starttime' => 'starttime',
				'endtime' 	=> 'endtime',
				'fe_group' 	=> 'fe_group',
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icons/page.png',
			'dividers2tabs'			=>	TRUE,
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, author, date, content,allow_comments, cat, trackback',
		)
	);

	t3lib_extMgm::allowTableOnStandardPages('tx_t3blog_cat');
	t3lib_extMgm::addToInsertRecords('tx_t3blog_cat');

	$TCA['tx_t3blog_cat'] = array (
		'ctrl' => array (
			'title'     				=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_cat',
			'label'     				=> 'catname',
			'tstamp'    				=> 'tstamp',
			'crdate'    				=> 'crdate',
			'cruser_id' 				=> 'cruser_id',
			'versioningWS' => TRUE, 
			'origUid' => 't3_origuid',
			'languageField'            => 'sys_language_uid',    
			'transOrigPointerField'    => 'l18n_parent',    
			'transOrigDiffSourceField' => 'l18n_diffsource',   
			'treeParentField' 			=> 'parent_id',
			'sortby' 					=> 'sorting',
			'delete' 					=> 'deleted',
			'enablecolumns' 			=> array (
				'disabled' 	=> 'hidden',
				'starttime' => 'starttime',
				'endtime' 	=> 'endtime',
				'fe_group' 	=> 'fe_group',
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY). 'tca.php',
			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY). 'icons/chart_organisation.png',
			'dividers2tabs'			=>	TRUE,
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, parent_id, catname, description',
		)
	);

	t3lib_extMgm::allowTableOnStandardPages('tx_t3blog_com');
	t3lib_extMgm::addToInsertRecords('tx_t3blog_com');

	$TCA['tx_t3blog_com'] = array (
		'ctrl' 	=> array (
			'title'     		=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_com',
			'tagClouds'     	=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_post',
			'label'     		=> 'title',
			'tstamp'    		=> 'tstamp',
			'crdate'    		=> 'crdate',
			'cruser_id' 		=> 'cruser_id',
			'default_sortby' 	=> 'ORDER BY crdate DESC',
			'delete' 			=> 'deleted',
			'enablecolumns' 	=> array (
				'disabled' 		=> 'hidden',
				'starttime' 	=> 'starttime',
				'endtime' 		=> 'endtime',
				'fe_group' 		=> 'fe_group',
			),
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY). 'tca.php',
			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY). 'icons/comment.png',
		),
		'feInterface' => array (
			'fe_admin_fieldList' => 'hidden, starttime, endtime, fe_group, title, author, email, website, date, text, approved, spam, fk_post',
		)
	);

	t3lib_extMgm::allowTableOnStandardPages('tx_t3blog_blogroll');
	t3lib_extMgm::addToInsertRecords('tx_t3blog_blogroll');

	$TCA['tx_t3blog_blogroll'] = array (
	    'ctrl' => array (
	        'title'     				=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_blogroll',
	        'label'     				=> 'title',
	        'tstamp'    				=> 'tstamp',
	        'crdate'    				=> 'crdate',
	        'cruser_id' 				=> 'cruser_id',
			'versioningWS' => TRUE, 
			'origUid' => 't3_origuid',
			'languageField'            => 'sys_language_uid',    
			'transOrigPointerField'    => 'l18n_parent',    
			'transOrigDiffSourceField' => 'l18n_diffsource',   
	        'sortby' 					=> 'sorting',
	        'delete' 					=> 'deleted',
	        'enablecolumns' 			=> array (
	            'disabled' 	=> 'hidden',
	            'starttime' => 'starttime',
	            'endtime' 	=> 'endtime',
	            'fe_group' 	=> 'fe_group',
	        ),
	        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY). 'tca.php',
	        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icons/icon_tx_t3blog_blogroll.png',
			'dividers2tabs'			=>	TRUE,
	    ),
	    'feInterface' => array (
	        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, url, image, description, xfn ',
	    )
	);

	$TCA['tx_t3blog_pingback'] = array (
	    'ctrl' => array (
	        'title'     		=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_pingback',
	        'label'     		=> 'uid',
	        'tstamp'    		=> 'tstamp',
	        'crdate'    		=> 'crdate',
	        'cruser_id' 		=> 'cruser_id',
	        'sortby' 			=> 'sorting',
	        'delete' 			=> 'deleted',
	        'enablecolumns' 	=> array (
	            'disabled' 		=> 'hidden',
	            'starttime' 	=> 'starttime',
	            'endtime' 		=> 'endtime',
	        ),
	        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY). 'tca.php',
	        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY). 'icons/icon_tx_t3blog_pingback.gif',
	    ),
	    'feInterface' => array (
	        'fe_admin_fieldList' => 'hidden, starttime, endtime, title, url, date, text',
	    )
	);

	$TCA['tx_t3blog_trackback'] = array (
	    'ctrl' => array (
	        'title'     		=> 'LLL:EXT:t3blog/locallang_db.xml:tx_t3blog_trackback',
	        'label'     		=> 'uid',
	        'tstamp'    		=> 'tstamp',
	        'crdate'    		=> 'crdate',
	        'cruser_id' 		=> 'cruser_id',
	        'default_sortby' 	=> 'ORDER BY crdate',
	        'delete' 			=> 'deleted',
	        'enablecolumns' 	=> array (
	            'disabled' 	=> 'hidden',
	        ),
	        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY). 'tca.php',
	        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY). 'icons/icon_tx_t3blog_trackback.gif',
	    ),
	    'feInterface' => array (
	        'fe_admin_fieldList' => 'hidden, fromurl, text, title, postid, id',
	    )
	);

	t3lib_extMgm::allowTableOnStandardPages('tx_t3blog_trackback');

	if (TYPO3_MODE == 'BE')	{
		$extPath = t3lib_extMgm::extPath($_EXTKEY);

		if (! isset($TBE_MODULES['txt3blogM1']))	{
			$temp_TBE_MODULES = array();

			foreach($TBE_MODULES as $key => $val) {
				if ($key == 'web') {
					$temp_TBE_MODULES[$key] = $val;
					$temp_TBE_MODULES['txt3blogM1'] = '';
				} else {
					$temp_TBE_MODULES[$key] = $val;
				}
			}
			$TBE_MODULES = $temp_TBE_MODULES;
		}
		t3lib_extMgm::addModule('txt3blogM1', '', '', t3lib_extMgm::extPath($_EXTKEY). 'mod1/');
		t3lib_extMgm::addModule('txt3blogM1', 'txt3blogM2', 'bottom', $extPath. 'mod2/');
		t3lib_extMgm::addModule('txt3blogM1', 'txt3blogM3', 'bottom', $extPath. 'mod3/');
		t3lib_extMgm::addModule('txt3blogM1', 'txt3blogM4', 'bottom', $extPath. 'mod4/');
		t3lib_extMgm::addModule('txt3blogM1', 'txt3blogM5', 'bottom', $extPath. 'mod5/');
	}
	
	// the static templates
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/t3blog/pi1', 'T3BLOG - main configuration');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/t3blog/styling/', 'T3BLOG CSS - snowflake theme 1');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/t3blog/template/', 'T3BLOG template - snowflake theme 1 ');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/t3blog/', 'T3BLOG blog2page - output to the page');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/t3blog/pi2/', 'T3BLOG functionalities on your website');
	
	if (TYPO3_MODE == 'BE')	{
		require_once(t3lib_extMgm::extPath($_EXTKEY). 'lib/class.tx_t3blog_treeview.php');
		require_once(t3lib_extMgm::extPath($_EXTKEY). 'lib/class.tx_t3blog_tcefunc_selecttreeview.php');
	}

	// be_users modification, to upload an image/avatar
	t3lib_div::loadTCA('be_users');
	$tx_t3blog_avatar = Array(
	 	'tx_t3blog_avatar' => txdam_getMediaTCA('image_field', 'tx_t3blog_avatar'),
	);
	t3lib_extMgm::addTCAcolumns('be_users', $tx_t3blog_avatar);
	t3lib_extMgm::addToAllTCATypes('be_users', 'tx_t3blog_avatar', '', 'after:realName');


	t3lib_div::loadTCA('tt_content');
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY. '_pi2'] = 'layout,select_key';

	t3lib_extMgm::addPlugin(array('LLL:EXT:t3blog/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY. '_pi2'), 'list_type');
	
	//Flexform
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY. '_pi2'] = 'layout,select_key,pages,recursive';
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY. '_pi2'] = 'pi_flexform';

	if (TYPO3_MODE=='BE')	{
		include_once(t3lib_extMgm::extPath($_EXTKEY). 'pi2/class.tx_t3blog_pi2_addFieldsToFlexForm.php');
	}
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:'. $_EXTKEY. '/flexform_pi2.xml');

	if (TYPO3_MODE=='BE')	{
		$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_t3blog_pi2_wizicon'] = t3lib_extMgm::extPath($_EXTKEY). 'pi2/class.tx_t3blog_pi2_wizicon.php';
	}
?>
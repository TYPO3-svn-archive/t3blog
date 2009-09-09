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

require_once(PATH_t3lib.'class.t3lib_db.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');

class t3blog_db extends t3lib_db {
	var $cObj;

	/**
	 * initial method, inits the cobj
	 *
	 */
	function init()	{
		// use old php4 function so it's compatible
		if (!is_a($this->cObj,'t3lib_cObj')) {
			$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		}
	}


	/**
	 * Select and fetch associate query from tx_t3blog_post
	 *
	 * @param unknown_type $uid (for future use maybe)
	 * @param unknown_type $where
	 * @return if has, db records from the db, else return false boolean
	 */
	function getRecordsFromDB($uid, $where = '1 = 1', $fields='*'){
		t3blog_db::init();
		$table = 'tx_t3blog_post';
		$where.= $this->cObj->enableFields($table);

		$select = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		$fields,		// SELECT ...
		$table,		// FROM ...
		$where,		// WHERE ...
		'',			// GROUP BY ...
		'',			// ORDER BY ...
		''				// LIMIT ...
		);

		while ($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($select)) {
			$result[] = $rows;
		}
		if ($result){
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Select and fetch associate query from be_users
	 *
	 * @param unknown_type $uid (for future use maybe)
	 * @param unknown_type $where
	 * @return if has, db records from the db, else return false boolean
	 */
	function getUserByWhere($uid, $where = '1 = 1'){
		t3blog_db::init();
		$table = 'be_users';
		$fields = 'uid, username, tx_t3blog_avatar';
		$where.= $this->cObj->enableFields($table);
		$select = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		$fields,		// SELECT ...
		$table,		// FROM ...
		$where,		// WHERE ...
		'',			// GROUP BY ...
		'',			// ORDER BY ...
		''				// LIMIT ...
		);
		$res = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($select);
		return $res;
	}

	/**
	 * Select and fetch associate query to db
	 *
	 * @param unknown_type $uid (for future use maybe)
	 * @param unknown_type $table (for future use maybe, with join's)
	 * @param unknown_type $where
	 * @return if has, db records from the db, else return false boolean
	 */
	function getRecFromDbJoinTables($table, $select_fields , $where ='1 = 1',$order='',$limit = ''){
		t3blog_db::init();
		$table_post = 'tx_t3blog_post';
		$where.= $this->cObj->enableFields($table_post);

		$select = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		$select_fields,		// SELECT ...
		$table,		// FROM ...
		$where,		// WHERE ...
		'',			// GROUP BY ...
		$order,			// ORDER BY ...
		$limit				// LIMIT ...
		);

		while ($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($select)) {
			$result[] = $rows;
		}
		if ($result){
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Select and fetch associate query to db
	 * @author Manu Oehler <moehler@snowflake.ch>
	 *
	 * @param $where
	 * @return if has, db records from the db, else return false boolean
	 */
	function getPostByWhere($where = '1 = 1',$order='',$limit = ''){
		t3blog_db::init();
		$fields = '*';
		$table = 'tx_t3blog_post';

		$where.= $this->cObj->enableFields($table);
		$select = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		$fields,		// SELECT ...
		$table,		// FROM ...
		$where,		// WHERE ...
		'',			// GROUP BY ...
		$order,			// ORDER BY ...
		$limit				// LIMIT ...
		);

		while ($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($select)) {
			$result[] = $rows;
		}
		if ($result){
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Select and fetch associate query to db
	 * @author Meile Simon <smeile@snowflake.ch>
	 *
	 * @param $where
	 * @return if has, db records from the db, else return false boolean
	 */
	function getCommentsByWhere($where = '1 = 1',$order='',$limit = ''){
		t3blog_db::init();
		$fields = '*';
		$table = 'tx_t3blog_com';

		$where.= $this->cObj->enableFields($table);
		$select = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		$fields,		// SELECT ...
		$table,		// FROM ...
		$where,		// WHERE ...
		'',			// GROUP BY ...
		$order,			// ORDER BY ...
		$limit				// LIMIT ...
		);

		while ($rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($select)) {
			$result[] = $rows;
		}
		if ($result){
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * Returns the number of COmment for this post.
	 *
	 * @param int $uid
	 */
	function getNumberOfCommentsByPostUid($uid){
		t3blog_db::init();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'count(uid) as counted',
			'tx_t3blog_com',
			'fk_post = '. $uid.' '. $this->cObj->enableFields('tx_t3blog_com').
			' AND spam=0 AND approved =1'
		);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if($row){
			return $row['counted'];
		}else{
			return 0;
		}


	}

	/**
	 * Number of rows from db
	 *
	 * @param unknown_type $resultFromDB
	 * @return numberof of rows if has, else return false boolean
	 */
	function sql_num_rows($resultFromDB){
		$numbOfRows = $GLOBALS['TYPO3_DB']->sql_num_rows($resultFromDB);
		if ($numbOfRows){
			return $numbOfRows;
		} else {
			return false;
		}
	}


	/**
	 * Inserts data via into TCEmain API, example for inserts:
	 * $inserts['last_name'] = 'oehler';
	 * $inserts['first_name'] = 'manu'
	 * 
	 * $table = 'tt_address';
	 * $pid = 3;
	 *
	 * @param string $table
	 * @param array $inserts
	 * @param int $pid
	 * @param uid string default 'NEW'
	 * @param cmd string, default '', also possible 'delete'
	 * 
	 */
	function insertViaTce($table, $inserts,$pid,$uid = 'NEW',$cmd = ''){
		//insert via tca
		if($uid == 'NEW'){
			$theNewID = uniqid('NEW');
		}else{
			$theNewID = $uid;
		}
		$data[$table][$theNewID] = array();
		// Is it a command or a insert/update
		if($cmd == ''){
			$data[$table][$theNewID]['pid'] = $pid;
			foreach ($inserts as $field => $value) {
				$data[$table][$theNewID][$field] = $value;
			}
			$cmd = array();
		}else{
			$data = array();
			if($cmd == 'delete'){
				$cmd = array();
				$cmd[$table][$theNewID]['delete'] = 1;
			}else{
				//wrong command
				return '';
			}
		}
		
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
       
        $tce->stripslashes_values = 1;
		if($GLOBALS['TSFE']){
			$tce->enableLogging = false;
			$GLOBALS['TSFE']->includeTCA($table);
//	          	$new_BE_USER = $GLOBALS['BE_USER']; // New backend user object
          	$new_BE_USER = t3lib_div::makeInstance("t3lib_beUserAuth");
			$new_BE_USER->OS = TYPO3_OS;
			$resAdmUser = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',		// SELECT ...
				'be_users',		// FROM ...
				'admin = 1 AND disable = 0 AND deleted = 0',		// WHERE ...
				'uid',		// GROUP BY ...
				'uid',		// ORDER BY ...
				'0,1'		// LIMIT ...
			);
			if($rowAdmUser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resAdmUser)){
				$this->beUserUid = $rowAdmUser['uid'];
			}
			$new_BE_USER->setBeUserByUid($this->beUserUid);
			$new_BE_USER->fetchGroupData();
			$new_BE_USER->user["admin"]=1;
			$tce->start($data,$cmd,$new_BE_USER);
        }else{
			$tce->start($data,$cmd);
        }
       
        $tce->exclude_array = array();
		if(count($cmd)<count($data)){
        	$tce->process_datamap();
		}else{
			$tce->process_cmdmap();
		}
	 	if (count($tce->errorLog)) {
	 		// echo the Error. Shouldnt happen if the data is correct
			t3lib_div::debug($tce->errorLog,'tce error');
	 		echo ' <br/>'.$table.' on pid '.$pid.' insert via tce failed <br/> <br/>';	
			echo chr(10);
			return false;
		}
		//insert devision
		if($uid == 'NEW'){
			$uid = $tce->substNEWwithIDs[$theNewID];
		}
		return $uid;
		
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_db.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/lib/class.t3blog_db.php']);
}
?>
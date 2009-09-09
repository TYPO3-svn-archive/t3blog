<?php
header("Content-Type: application/xml");
require_once('../../lib/xmlrpc-2.2/lib/xmlrpc.inc');
require_once('../../lib/xmlrpc-2.2/lib/xmlrpcs.inc');

/**
 * Starts pingback-process 
 *
 * @param 	object	$m
 * @return 	string	Confirmation or error message
*/
function pbprocess($m) {
        global $xmlrpcerruser;

        $x1 		= $m->getParam(0);
        $x2 		= $m->getParam(1);
        $source 	= $x1->scalarval(); # their article
        $dest 		= $x2->scalarval(); # your article


        //USE CLI MODE?
        global $TYPO3_CONF_VARS;
		error_reporting (E_ALL ^ E_NOTICE);
		if ($_SERVER['PHP_SELF']) {
			if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_SERVER['PHP_SELF'])));
		} else {
			if (!defined('PATH_thisScript')) define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', $_ENV['_'])));
		}
		
		if (!defined('PATH_site')) define('PATH_site', dirname(dirname(dirname(dirname(dirname(dirname(PATH_thisScript)))))).'/');
		if (!defined('PATH_t3lib')) if (!defined('PATH_t3lib')) define('PATH_t3lib', PATH_site.'t3lib/');
		
		define('PATH_typo3conf', PATH_site.'typo3conf/');
		define('TYPO3_mainDir', 'typo3/');
		
		if (!defined('PATH_typo3')) define('PATH_typo3', PATH_site.TYPO3_mainDir);
		if (!defined('PATH_tslib')) {
			if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
				define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
			} elseif (@is_dir(PATH_site.'tslib/')) {
				define('PATH_tslib', PATH_site.'tslib/');
			}
		}
		
		define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
		define('TYPO3_MODE', 'BE');
		
		require_once(PATH_t3lib.'class.t3lib_div.php');
		require_once(PATH_t3lib.'class.t3lib_extmgm.php');
		require_once(PATH_t3lib.'config_default.php');
		require_once(PATH_typo3conf.'localconf.php');
		
		require_once(PATH_t3lib.'class.t3lib_userauth.php');
		require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
		require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
		require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
		
		// Connect to the database
		require_once(PATH_t3lib.'class.t3lib_db.php');
		$TYPO3_DB 	= t3lib_div::makeInstance('t3lib_DB');
		$result 	= $TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
		$nodb 		= false; 
		if(!$result){
			$nodb = true ; //die("oh shi, no connection".TYPO3_db_host);
		}
		
		
		$TYPO3_DB->sql_select_db(TYPO3_db);		 
		
        if (url_exists($source)) { # source uri does not exist
	        return new xmlrpcresp(0, 16, "Source uri does not exist");
        }
        if (url_got_link($source,$dest)) { # source uri does not have a link to target uri
			return new xmlrpcresp(0, 17, "Source uri does have link to target uri");
		}

        if (url_exists($dest)) { # target uri does not exist
			return new xmlrpcresp(0, 32, "Target uri does not exist");
		}
		
		/*
		check with allow_pings
		        if (..) { # target uri cannot be used as target
		        	return new xmlrpcresp(0, 33, "Target uri cannot be used as target");
				}
		*/	
		
		$res_exits = $TYPO3_DB->exec_SELECTquery(
			'uid',
			'tx_t3blog_pingback',
			'url == '.$source.'  AND deleted = 0 AND hidden = 0' //for testing, sends it now
		);
		
        if ($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_exits)) { # Pingback already registered
			return new xmlrpcresp(0, 48, "Target uri cannot be used as target");
		}

        if (1==2) { # Access denied
	        return new xmlrpcresp(0, 49, "Access denied");
		}

        if (1==2) { # Could not communicate with upstream server or got error
        	return new xmlrpcresp(0, 50, "Problem with upstream server");
		}

        if ($nodb) { # Generic fault code if not applicable to above
        	return new xmlrpcresp(0, 50, "Unkown error");
		}
		
		$title = getTitleByUrl($source);
				
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_t3blog_pingback',	//TABLE
			array(
				'title'=>$title,
				'crdate'=> time(),
				'cruser_id'=>1,
				'deleted'=>0,
				'hidden'=>0,
				'url'=>$source,
				'text'=>$text, //TODO -> write the url
				'date'=>time()
			)	//values array			
		);

        return new xmlrpcresp(new xmlrpcval("Pingback registered. Have a nice Day.", "string"));
}


/**
 * Checks if the url exists
 *
 * @param 	string 	$strURL: url-address
 * @return 	boolean	true if existing
*/
function url_exists($strURL) { 
    $resURL = curl_init(); 
    curl_setopt($resURL, CURLOPT_URL, $strURL); 
    curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1); 
    curl_setopt($resURL, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback'); 
    curl_setopt($resURL, CURLOPT_FAILONERROR, 1); 

    curl_exec ($resURL); 

    $intReturnCode = curl_getinfo($resURL, CURLINFO_HTTP_CODE); 
    curl_close ($resURL); 

    if ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) { 
       return false; 
    }Else{ 
        return true ; 
    } 
}

/**
 * Checks if the url has a valid link
 *
 * @param 	string 	$strURL: url-address
 * @param	string	$dest: your article
 * @return 	boolean	true if valid
*/
function url_got_link($strURL,$dest) { 
    $resURL = curl_init(); 
    curl_setopt($resURL, CURLOPT_URL, $strURL); 
    curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1); 
    curl_setopt($resURL, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback'); 
    curl_setopt($resURL, CURLOPT_FAILONERROR, 1); 

    $content = curl_exec ($resURL); 

    curl_close ($resURL); 

    if(stripos($content,$dest)){
    	return true;	
    }else{
    	return false; 
    }     
}


/**
 * Gets pingback title by url
 *
 * @param 	string 	$sURL: url-address
 * @return 	string	title of the pingback
*/
function getTitleByUrl($sURL) {
	$clSession = curl_init();
	$iTimeout = 30; 
	
	curl_setopt ($clSession, CURLOPT_URL, $sURL); 
	curl_setopt ($clSession, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($clSession, CURLOPT_CONNECTTIMEOUT, $iTimeout);
	curl_setopt ($clSession, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt ($clSession, CURLOPT_HEADER, TRUE);
	curl_setopt ($clSession, CURLOPT_NOBODY, TRUE);
	
	$sScrape = curl_exec($clSession);
	curl_close($clSession);
	
	$sPingbackAddress = trim(TextBetween("<title>", "</title>", $sScrape));
	
	return $sPingbackAddress;
}


/**
 * Gets pingback address by url
 *
 * @param 	string 	$sURL: url-address
 * @return 	string	pingbackaddress
*/
function GetXPingback($sURL) {
	$clSession = curl_init();
	$iTimeout = 30; 
	
	curl_setopt ($clSession, CURLOPT_URL, $sURL); 
	curl_setopt ($clSession, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($clSession, CURLOPT_CONNECTTIMEOUT, $iTimeout);
	curl_setopt ($clSession, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt ($clSession, CURLOPT_HEADER, TRUE);
	curl_setopt ($clSession, CURLOPT_NOBODY, TRUE);
	
	$sScrape = curl_exec($clSession);
	curl_close($clSession);
	
	$sPingbackAddress = trim(TextBetween("X-Pingback: ", "n", $sScrape));
	
	return $sPingbackAddress;
}


/**
 * Gets text to show for pingback address
 *
 * @param 	string 	$s1
 * @param 	string 	$s2
 * @param 	string 	$s3
 * @return 	string	text
*/	
function TextBetween($s1,$s2,$s){
	$s1 = mb_strtolower($s1);
	$s2 = mb_strtolower($s2);
	$L1 = strlen($s1);
	$scheck = mb_strtolower($s);
	if($L1>0){
		$pos1 = strpos($scheck,$s1);
	} else {
		$pos1=0;
	}
	if($pos1 !== false){
		if($s2 == '') return substr($s,$pos1+$L1);
		
		$pos2 = strpos(substr($scheck,$pos1+$L1),$s2);
		if($pos2!==false) return substr($s,$pos1+$L1,$pos2);
	}
	return '';
}
        
        

$a = array( "pingback.ping" => array( "function" => "pbprocess" ));
$s = new xmlrpc_server($a, false);
$s->service();
?>
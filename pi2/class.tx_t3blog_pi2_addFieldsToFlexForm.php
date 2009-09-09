<?php
class tx_t3blog_pi2_addFieldsToFlexForm {

	/**
	 * fetch the available widgets
	 *
	 * @author 	kay stenschke <kstenschke@snowflake.ch>
	 * 
	 * @param 	array 	$config
	 * @param 	bool 	$getAsKeyValArray	if true: render just an ass. array (to be used not in flexform rendering, but to resolve the values again)
	 * 
	 * @return	widget array
	 */
	function getWidgets($config, $getAsKeyValArray = false)	{
		$optionsList = array();

		// fetch widgets folders:
		$pathT3blogWidgets = realpath(PATH_typo3conf.'/ext/t3blog/pi1/widgets');
		$folders = $this->getFolderContents($pathT3blogWidgets, true);	// fetch list of contained folders
		asort($folders);

		// render options list from widgets and widgets TS:
		$i = 0;
		$assWidgetsArray = array(); // associative widgets array, for usage from other than ff-renderer to resolve the widgets' keys
		if (is_array($folders)) 	{
			foreach($folders as $folder)	{
				if ($folder != '.svn')	{
					list($widgetTitle, $widgetDescription) = $this->fetchWidgetTitleDescriptionLL($folder);
					$optionsList[] = array(
						0	=> $widgetTitle. ($widgetDescription[0] ? ' - '. $widgetDescription : ''),		// option
						1	=> $i, //trim($older),	// value
					);

					$assWidgetsArray[$i] = array(
						'folder'		=> $folder,
						'title' 		=> $widgetTitle,
						'desctiption'	=> $widgetDescription,
					);
					$i++;

				}
			}
			if (is_array($optionsList)) {
				if (! $config['items'] || ! is_array($config['items'])) {
					$config['items'] = array();
				}
				$config['items'] = array_merge($config['items'], $optionsList);
			}
		}

		return $getAsKeyValArray == false ? $config : $assWidgetsArray;
	}


	/**
	 * fetch title and description to widget from the resp. locallang file
	 *
	 * @author 	kay stenschke <kstenschke@snowflake.ch>
	 * 
	 * @param 	string $widgetFoldername
	 * @return 	mixed
	 */
	function fetchWidgetTitleDescriptionLL($widgetFoldername) {
		$pathT3blogWidgets = realpath(PATH_typo3conf.'/ext/t3blog/pi1/widgets/');
		$llFile = $pathT3blogWidgets.'/'. $widgetFoldername. '/locallang.xml';
		if (file_exists($llFile))	{
			$llData = $this->getFileContent($llFile);
			$title = $this->explortXMLlabelValue($llData, 'title');
			if ($title == '') {
				$title = 'Empty title label ('. $llFile. ')';
			} else {
				if (strpos($llData, 'widgetSelector.description') !== false) {	// append description to title
					$descr = $this->explortXMLlabelValue($llData, 'widgetSelector.description');

				}
			}
		} else {
			$title = 'Title label missing ('. $llFile. ')';
		}

		return array($title, $descr);
	}


	/**
	 * fetch locallang value
	 * (export key's value via exploding of xml data content)
	 *
	 * @author kay stenschke <kstenschke@snowflake.ch>
	 * 
	 * @param 	string 	$llData
	 * @param 	string 	$key
	 * 
	 * @return 	xml label value
	 */
	function explortXMLlabelValue($llData, $key) {
		$rc = explode('<label index="'. $key. '">', $llData);
		$rc = $rc[1];
		$rc = explode('</label>', $rc);
		$rc = trim($rc[0]);

		return $rc;
	}


	/**
	 * Fetch all files of given folder into an array
	 *
	 * @param	string	$folder: folder to read-out
	 * @param	bool	$foldersOnly
	 * 
	 * @return	array 	$files: array of found files
	 */
	function getFolderContents($folder, $foldersOnly = false) {
		$listDir= opendir($folder);
		while(($file = readdir($listDir)) !== false) {
			if ($file != '.' && $file != '..') {
				if (! $foldersOnly || is_dir($folder.'/'.$file))
				$files[]= $file;
			}
		}
		closedir ($listDir);

		return $files;
	}


	/**
	 * Read content of file
	 *
	 * @author	kay stenschke <kstenschke@snowflake.ch>
	 * 
	 * @param	string		$filename: name of the file
	 * @return	string		content of the file
	 */
	function getFileContent($filename) {
		$handle = fopen ($filename, "rb");
		if (!$handle) {
			echo'Unable to open file '.$filename .' !';
			exit();
		} else {
			$contents='';
			while(!feof($handle)) {
				$contents= $contents.(fread($handle, 4096));
				//flush();
			}
			fclose ($handle);
			return $contents;
		}
	}
}

?>
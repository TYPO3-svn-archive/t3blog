<?PHP

/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 * Widget class.calendar
 *
 * @author		carmine diluca <cdiluca@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class calendar extends tslib_pibase {
	var $prefixId      = 'calendar';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/archive/class.calendar.php';	// Path to this script relative to the extension dir.
	var $extKey        = 't3blog';	// The extension key.
	var $pi_checkCHash = false;
	var $localPiVars;
	var $globalPiVars;
	var $conf;
	var $day;
	var $month;
	var $year;
	
	
	/**
	* 	Constructor for the Calendar class
	*/
	function main($content,$conf,$piVars){
		$this->globalPiVars = $piVars;
		$this->localPiVars = $piVars[$this->prefixId];
		$this->conf = $conf;
		$this->init();

		if($this->globalPiVars['blogList']['datefrom'])	{
			$year = substr($this->globalPiVars['blogList']['datefrom'], 0, 4);
			$month = ereg_replace('-', '', substr($this->globalPiVars['blogList']['datefrom'], 5, 2));
			$this->day = ereg_replace('-', '', substr($this->globalPiVars['blogList']['datefrom'], 8, 2));
		} elseif ($this->globalPiVars['blogList']['month'] && $this->globalPiVars['blogList']['year']) {
			$year = $this->globalPiVars['blogList']['year'];
			$month = $this->globalPiVars['blogList']['month'];
			$this->day = $this->globalPiVars['blogList']['day'];
		} elseif ($this->globalPiVars['blogList']['showUid']) {

			//If there is a show uid, but no specific date, take the date from the blog entry.

			$where = 'uid = '.(int)$this->globalPiVars['blogList']['showUid'];
			$where.= ' AND pid = '. t3blog_div::getBlogPid();
			$blogentry = t3blog_db::getRecordsFromDB($uid ,$where,'*');
			if($blogentry){
				$this->day = date('j', $blogentry[0]['date']);
				$month = date('n', $blogentry[0]['date']);
				$year = date('Y', $blogentry[0]['date']);
			}
		} else {
			$d = getdate(time());
			$this->day = $d['mday'];
			$month = $d['mon'];
			$year = $d['year'];
		}

		$data['calendar'] = $this->getMonthView($month, $year);

		return t3blog_div::getSingle($data, 'calendaroutput');
	}

	/**
	 * Initial Method
	 */
	function init(){
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj = $this->localCobj;
		$this->pi_loadLL();
	}

	

	/**
	 * Get the array of strings used to label the days of the week. This array contains seven
	 * elements, one for each day of the week. The first entry in this array represents Sunday.
	 *
	 * @return string
	 */
	function getDayNames(){
		return $this->dayNames;
	}


	/**
	 * Set the array of strings used to label the days of the week. This array must contain seven
	 * elements, one for each day of the week. The first entry in this array represents Sunday.
	 *
	 * @param 	array 	$names: number of the days
	 * @return	name of the day 
	 */
	function setDayNames($names){
		$this->dayNames = $names;
	}

	/**
	 * Get the array of strings used to label the months of the year. This array contains twelve
	 * elements, one for each month of the year. The first entry in this array represents January.
	 *
	 * @return name of the month
	 */
	function getMonthNames(){
		return $this->monthNames;
	}

	/**
	 * Set the array of strings used to label the months of the year. This array must contain twelve
	 * elements, one for each month of the year. The first entry in this array represents January.
	 *
	 * @param array		$names: name of the mounts 
	 */
	function setMonthNames($names){
		$this->monthNames = $names;
	}


	/**
	 * Gets the start day of the week. This is the day that appears in the first column
	 * of the calendar. Sunday = 0.
	 *
	 * @return daynumber 
	 */
	function getStartDay(){
		return $this->startDay;
	}

	/**
	* Sets the start day of the week. This is the day that appears in the first column
	* of the calendar. Sunday = 0.
	* 
	* @param	int	$day: number of the day
	*/
	function setStartDay($day){
		$this->startDay = $day;
	}


	/**
	* Gets the start month of the year. This is the month that appears first in the year
	* view. January = 1.
	* 
	* @return	monthnumber
	*/
	function getStartMonth(){
		return $this->startMonth;
	}

	/**
	* Sets the start month of the year. This is the month that appears first in the year
	* view. January = 1.
	*/
	function setStartMonth($month){
		$this->startMonth = $month;
	}


	/**
	* Return the URL to link to in order to display a calendar for a given month/year.
	* You must override this method if you want to activate the "forward" and "back"
	* feature of the calendar.
	*
	* Note: If you return an empty string from this function, no navigation link will
	* be displayed. This is the default behaviour.
	*
	* If the calendar is being displayed in "year" view, $month will be set to zero.
	* 
	* @param	int		$month: number of the mount
	* @param	int		$year: number of the year
	* 
	* @return	returns the url
	*/
	function getCalendarLink($month, $year) {
		$data =  array('month' => $month, 'year' => $year);
		$returnVar = t3blog_div::getSingle($data,'navLink');
		return $returnVar;
	}

	/**
	 * Return the URL to link to  for a given date.
	 * You must override this method if you want to activate the date linking feature of the calendar.
	 *
	 * Note: If you return an empty string from this function, no navigation link will be displayed. This is the default behaviour.
	 *
	 * @param 	int		$day: Day
	 * @param	int		$month: Month
	 * @param	int		$year: Year
	 *
	 * @return	String	Url to the dayview
	 */
	function getDateLink($day, $month, $year){
		$datefrom = $year.'-'.$month.'-'.$day;
		return t3blog_div::getSingle(
			array(
				'day' => $day, 
				'date'=> $datefrom,
				'blogUid'=>t3blog_div::getBlogPid()
			),'dateLink');
	}


	/**
	* Return the HTML for the current month
	* 
	* @return 	returns html for the current month
	*/
	function getCurrentMonthView(){
		$d = getdate(time());
		return $this->getMonthView($d["mon"], $d["year"]);
	}


	/**
	* Return the HTML for the current year
	* 
	* @param	returns html for the current year
	*/
	function getCurrentYearView(){
		$d = getdate(time());
		return $this->getYearView($d["year"]);
	}


	/**
	* Return the HTML for a specified month
	* 
	* @param	int		$month: specific month
	* @param	int		$year: specific year
	* 
	* @return	the html for a specific month
	*/
	function getMonthView($month, $year){
		return $this->getMonthHTML($month, $year);
	}


	/**
	* Return the HTML for a specified year
	* 
	* @param	int		$year: specific year
	* 
	* @return	the html for a specific year
	*/
	function getYearView($year){
		return $this->getYearHTML($year);
	}



	/********************************************************************************

	The rest are private methods. No user-servicable parts inside.
	You shouldn't need to call any of these functions directly.

	*********************************************************************************/


	/**
	* Calculate the number of days in a month, taking into account leap years.
	* 
	* @param	int		$month: month
	* @param	int		$year: year
	* 
	* @return	calculated number of days in a month
	*/
	function getDaysInMonth($month, $year){
		if ($month < 1 || $month > 12){
			return 0;
		}

		$d = $this->daysInMonth[$month - 1];

		if ($month == 2){
			// Check for leap year
			// Forget the 4000 rule, I doubt I'll be around then...

			if ($year%4 == 0){
				if ($year%100 == 0){
					if ($year%400 == 0){
						$d = 29;
					}
				}else{
					$d = 29;
				}
			}
		}

		return $d;
	}

	/**
	 * returns the month name from the locallang
	 * 
	 * @param  	int		$monthNr: month
	 * 
	 * @return 	month as string
	 */
	
	function getMonthName($monthNr){
		$monthNr = intval($monthNr);
		return $this->pi_getLL('month_'.$monthNr);
	}

	/**
	* Generate the HTML for a given month
	* 
	* @param	int		$m: month
	* @param	int		$y: year
	* @param	int		$showYear: show or no show
	* 
	* @return	html
	*/
	function getMonthHTML($m, $y, $showYear = 1){
		$s = "";

		$a = $this->adjustDate($m, $y);
		$month = $a[0];
		$year = $a[1];

		$daysInMonth = $this->getDaysInMonth($month, $year);
		$date = getdate(mktime(12, 0, 0, $month, 1, $year));

		$first = $date["wday"];
		$monthName = $this->getMonthName($month);

		$prev = $this->adjustDate($month - 1, $year);
		$next = $this->adjustDate($month + 1, $year);

		if ($showYear == 1){
			$prevMonth = $this->getCalendarLink($prev[0], $prev[1]);
			$nextMonth = $this->getCalendarLink($next[0], $next[1]);
		}
		else{
			$prevMonth = "";
			$nextMonth = "";
		}

		$header = $monthName . (($showYear > 0) ? " " . $year : "");

		$s .= "<table class=\"calendar\" summary=\"Calendar\">\n";
		$s .= "<tr>\n";
		$s .= "<th class=\"previous navigation\">" . (($prevMonth == "") ? "&nbsp;" : '<a href="'.$prevMonth.'"> &lt; &lt;</a> </th>');
		$s .= "<th colspan=\"5\">$header</th>\n";
		$s .= "<th class=\"next navigation\">" . (($nextMonth == "") ? "&nbsp;" : '<a href="'.$nextMonth.'"> &gt; &gt;</a> </th>');
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\" first \">" . $this->dayNames[($this->startDay)%7]. "</td>\n";
		$s .= "<td>" . $this->dayNames[($this->startDay+1)%7] . "</td>\n";
		$s .= "<td>" . $this->dayNames[($this->startDay+2)%7] . "</td>\n";
		$s .= "<td>" . $this->dayNames[($this->startDay+3)%7] . "</td>\n";
		$s .= "<td>" . $this->dayNames[($this->startDay+4)%7] . "</td>\n";
		$s .= "<td>" . $this->dayNames[($this->startDay+5)%7] . "</td>\n";
		$s .= "<td class=\"last\">" . $this->dayNames[($this->startDay+6)%7] . "</td>\n";
		$s .= "</tr>\n";

		// We need to work out what date to start at so that the first appears in the correct column
		$d = $this->startDay + 1 - $first;
		while ($d > 1){
			$d -= 7;
		}

		if ($this->globalPiVars['blogList']['showUid']){
			$where = 'uid = '.(int)$this->globalPiVars['blogList']['showUid'];
		} else {
			$where = "1 = 1";
		}
		$where.= ' AND pid = '. t3blog_div::getBlogPid();


		$resultFromDB = t3blog_db::getRecordsFromDB($uid ,$where,'*');

		$today = getdate(time());

		while ($d <= $daysInMonth){
			$s .= "<tr>\n";
			$tempD = $d;


			for ($i = 0; $i < 7; $i++) {
				if($daysInMonth - $tempD < 7)	{
					$class = 'lastrow';
				} else {
					$class = '';
				}

				if($i == 0)	{
					$class.=' first';
				} else if ($i == 6)	{
					$class.=' last';
				} else {
					$class.='';
				}

				$startTimestamp = mktime(0, 0, 0, $month, $d, $year);
				$endTimestamp = mktime(23, 59, 59, $month, $d, $year);
				$hasBlogEntry = false;
				if ($year == $today["year"] && $month == $today["mon"] && $d == $today["mday"]){
					$class .= " calendarToday";
				} else {
					$class .= " calendar";
				}
				if($resultFromDB){
					foreach ($resultFromDB as $blog) {
						if ($blog['date'] <= $endTimestamp && $blog['date'] >= $startTimestamp && $d > 0) {
							$hasBlogEntry = true;
							$class.= " isBlogDay";
							break;
						}
					}
				}

				if ($this->day == $d) {
					$class.= " selectedBlogDay";
				}

				$s .= "<td class=\"$class\" align=\"right\" valign=\"top\">";
				if ($d > 0 && $d <= $daysInMonth){
					$s .= ($hasBlogEntry) ? $this->getDateLink($d, $month, $year) : $d;
				}else{
					$s .= "&nbsp;";
				}
				$s .= "</td>\n";
				$d++;
			}

			$s .= "</tr>\n";
		}
		$s .= "</table>\n";
		return $s;
	}


	/**
	* Generate the HTML for a given year
	* 
	* @param	int		$year: year
	* 
	* @return	html for a given year
	*/
	function getYearHTML($year){
		$s = "";
		$prev = $this->getCalendarLink(0, $year - 1);
		$next = $this->getCalendarLink(0, $year + 1);

		$s .= "<table class=\"calendar\" border=\"0\">\n";
		$s .= "<tr>";
		$s .= "<td align=\"center\" valign=\"top\" align=\"left\">" . (($prev == "") ? "&nbsp;" : "<a href=\"".$prev."\">&lt;&lt;</a>")  . "</td>\n";
		$s .= "<td class=\"calendarHeader\" valign=\"top\" align=\"center\">" . (($this->startMonth > 1) ? $year . " - " . ($year + 1) : $year) ."</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" align=\"right\">" . (($next == "") ? "&nbsp;" : "<a href=\"".$next."\">&gt;&gt;</a>")  . "</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(0 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(1 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(2 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(3 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(4 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(5 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(6 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(7 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(8 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(9 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(10 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(11 + $this->startMonth, $year, 0) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "</table>\n";

		return $s;
	}

	/*

	*/
	/**
	 * Adjust dates to allow months > 12 and < 0. Just adjust the years appropriately.
	 * e.g. Month 14 of the year 2001 is actually month 2 of year 2002.
	 *
	 * @param	int		$month: Month
	 * @param	int		$year: Year
	 * 
	 * @return 	int		First element is the month, second the year
	 */
	function adjustDate($month, $year){
		$a = array();
		$a[0] = $month;
		$a[1] = $year;

		while ($a[0] > 12) {
			$a[0] -= 12;
			$a[1]++;
		}

		while ($a[0] <= 0) {
			$a[0] += 12;
			$a[1]--;
		}

		return $a;
	}

	/*
	The start day of the week. This is the day that appears in the first column
	of the calendar. Sunday = 0.
	*/
	var $startDay = 0;

	/*
	The start month of the year. This is the month that appears in the first slot
	of the calendar in the year view. January = 1.
	*/
	var $startMonth = 1;

	/*
	The labels to display for the days of the week. The first entry in this array
	represents Sunday.
	*/
	var $dayNames = array("S", "M", "T", "W", "T", "F", "S");

	/*
	The labels to display for the months of the year. The first entry in this array
	represents January.
	*/
	var $monthNames = array("January", "February", "March", "April", "May", "June",
	"July", "August", "September", "October", "November", "December");


	/*
	The number of days in each month. You're unlikely to want to change this...
	The first entry in this array represents January.
	*/
	var $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/calendar/class.calendar.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/calendar/class.calendar.php']);
}

?>
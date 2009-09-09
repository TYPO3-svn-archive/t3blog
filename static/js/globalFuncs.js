/**
 * Check whether var is set
 *
 * @author	kay stenschke <kstenschke@snowflake.ch>
 */
function isSet(objToTest) {
	if (null == objToTest) {
		return false;
	}
	if ('undefined' == typeof(objToTest) ) {
		return false;
	}
	return true;
}
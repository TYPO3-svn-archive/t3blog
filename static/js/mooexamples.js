/*
 * @author simon lang <slang@snowflake.ch>
*/

//Send get ajax requests and debug the result into the firebug console.
//(NOTE: the default container-div for debug outputs of AJAX calls has the id "debugContainer")

function moo01(){
	//Good url
	var url = 'index.php?ajax=1';
	new Ajax(url, {
			method: 'get',
			update: $('debugContainer'),
			onComplete: function(response) {
				console.log(response);
			},
			onFailure: function(response) {
				console.log(response);
			}
		}).request();
}

//Send get ajax requests and writes the output of the php scripts into the defined (log) element.
function moo02() {
	var url = 'index.php?ajax=1';
	new Ajax(url, {
			method: 'get',
			update: $('inputField_1')
		}).request();
}

//Send post ajax requests and writes the output of the php scripts into the defined (log) element.
function moo03() {
	var url = 'index.php';
	new Ajax(url, {
			method: 'post',
			postBody: 'ajax=1&foo=bar',
			update: $('inputField_1')
		}).request();
}
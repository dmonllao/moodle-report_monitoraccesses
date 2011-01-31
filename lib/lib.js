// $Id$

var callbackResult = false;


function display_users() {
    
    var usersdiv = document.getElementById("id_users");
    usersdiv.innerHTML = "";
    
    // Responses manager
    var callbackHandler = 
    {
          success: process_display_users,
          failure: failure_display_users,
          timeout: 50000
    };

    var params = "action=userslist&output=simple";
    var coursesparams = ""; 
    
    // Send the selected courses
    var courses = document.getElementsByTagName("input");
    for (var i = 0; i < courses.length; i++) {
    	if (courses[i].type == 'checkbox') {
    		
    		// Ensure that the checkbox are checked
    		if (courses[i].checked == true) {
    			coursesparams += "&"+courses[i].name+"=1";
    		}
    	}
    }

    if (coursesparams != "") {
        YAHOO.util.Connect.asyncRequest("POST", "index.php", callbackHandler, params + coursesparams);
    }
    
    return callbackResult;
}

function process_display_users(transaction) {
    
    var usersdiv = document.getElementById("id_users");

    usersdiv.innerHTML = transaction.responseText;
    callbackResult = false;
}
function failure_display_users() {    callbackResult = false;}


function toggle_month(element, stripid, year, month) {

	var elementname;
	var elements = new Array();
	var elementDate;
	var weekday;
	
	var checkboxes = document.getElementsByTagName("input");
	var elementformat = "date_" + stripid + "_" + year + "_" + month;

	for(var i = 0; i < checkboxes.length; i++) {
		
		// If it is one of the month days
		elementname = checkboxes[i].name;
		if (elementname.indexOf(elementformat) != -1) {

			// Don't change the weekend days
			elements = elementname.split('_');
			elementDate = new Date(elements[2], (elements[3] - 1), elements[4]);			
			weekday = elementDate.getDay();

			// Only non weekend days must change (O = sunday, 6 = saturday)
			if (weekday != 6 && weekday != 0) {
				
				// If it's checked it will be unchecked
				if (element.checked) {
					checkboxes[i].checked = true;
					
				// If it's unchecked it will be checked
				} else {
					checkboxes[i].checked = false;
				}
			}
		}
	}
	
	return true;
}

//Framework Specific
function showMessage(data) {
	if(data.success) $("#success-message").html(stripSlashes(data.success)).show();
	if(data.error) $("#error-message").html(stripSlashes(data.error)).show();
}
function stripSlashes(text) {
	if(!text) return "";
	return text.replace(/\\([\'\"])/,"$1");
}


function ajaxError() {
	alert("Error communicating with server. Please try again");
}
function loading() {
	$("#loading").show();
}
function loaded() {
	$("#loading").hide();
}


function showDateRange() {
	$("#select-date-toggle").toggle();
	$("#select-date-area").toggle();
}


function siteInit() {
	$( "#from" ).datepicker({
      defaultDate: "+1w",
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    $( "#to" ).datepicker({
      defaultDate: "+1w",
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#from" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
    $("#select-date-toggle").click(showDateRange);

	$("a.confirm").click(function(e) { //If a link has a confirm class, confrm the action
		var action = (this.title) ? this.title : "do this";
		action = action.substr(0,1).toLowerCase() + action.substr(1); //Lowercase the first char.
		
		if(!confirm("Are you sure you want to " + action + "?")) {
			e.stopPropagation();
		}
	});

	// Show selected city's centers. 
	$("#city_id").change(function() {
		var select = "<select id='center_id'>";
		var city_id = this.value;

		var centers_in_city = centers[city_id];
		for(var center_id in centers_in_city) {
			select += "<option value='"+center_id+"'>"+centers_in_city[center_id]+"</option>";
		}
		select += '</select>';

		$("#center_id").html(select);
	});

	if(window.init && typeof window.init == "function") init(); //If there is a function called init(), call it on load
}
$ = jQuery.noConflict();
jQuery(window).load(siteInit);


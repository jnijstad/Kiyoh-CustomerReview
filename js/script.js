jQuery( document ).ready(function() {
	toogleStatus(300);
    toogleSendMethod(300);
    toogleKiyohServer(500);
	jQuery('select[name="kiyoh_option_event"]').change(function(event) {
		toogleStatus(300);
	});

    jQuery('select[name="kiyoh_option_send_method"]').change(function(event) {
        toogleSendMethod(300);
    });

    jQuery('select[name="kiyoh_option_server"]').change(function(event) {
        toogleKiyohServer(300);
    });
});
function toogleStatus (speed) {
	var my_event = jQuery('select[name="kiyoh_option_event"]').val();
	if (my_event == 'Orderstatus') {
		jQuery('#status').show(speed);
	}else{
		jQuery('#status').hide(speed);
	}
}
function toogleSendMethod (speed) {
    var my_event = jQuery('select[name="kiyoh_option_send_method"]').val();
    if (my_event == 'my') {
        jQuery('.myserver').show(speed);
        jQuery('.kiyohserver').hide(speed);
    }else{
        jQuery('.myserver').hide(speed);
        jQuery('.kiyohserver').show(speed);
    }
}
function toogleKiyohServer (speed) {
    var my_event = jQuery('select[name="kiyoh_option_server"]').val();
    if (my_event == 'kiyoh.nl') {
        jQuery('.dependsonkiyohserver').hide(speed);
    }else{
        jQuery('.dependsonkiyohserver').show(speed);
    }
}
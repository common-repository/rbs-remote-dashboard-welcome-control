jQuery( document ).ready(function($) {
	var url = (window.location != window.parent.location) ? document.referrer : document.location;
	var regexp = /^(https?:\/\/.[^/]+)/;
	host = url.match(regexp)[1];
	if(host !== rrdwcObject.reqHost) {
		alert("There seems to be a misconfiguration, '"+host+"' is not allowed to use the iframe-functionality!");
	}
	else {
		setTimeout(rrdwc_sendHeight, 300);
		
		//Add target _blank to all links
		if(rrdwcObject.linksInNewWindow == 1) {
		    $('a').not('[href*="mailto:"]').each(function () {
				$(this).attr('target', '_blank');
			});
		}
	}
});

function rrdwc_sendHeight() { 
	var height = jQuery("#entry-content").height();
	parent.postMessage(height, rrdwcObject.reqHost);
}

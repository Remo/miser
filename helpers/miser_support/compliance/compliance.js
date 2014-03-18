function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
		var div = document.getElementById("miser_cookies_sticky");
		document.body.removeChild(div);
		}

$(document).ready(function(){
	function sticky_relocate() {
		var window_top = $(window).scrollTop();
		var div_top = $('#miser_cookies_sticky_anchor').offset().top;
		if (window_top > div_top) $('#miser_cookies_sticky').addClass('miser_stick')
		else $('#miser_cookies_sticky').removeClass('miser_stick');
		$('#miser_cookies_sticky').height(80);
		
	}
	$(function() {
		$(window).scroll(sticky_relocate);
			sticky_relocate();
	});
  
});
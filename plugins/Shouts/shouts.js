// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

var Shouts = {

init: function() {
	$("shoutForm").onsubmit = function onsubmit() {
		Shouts.addShout($("shoutContent").value);
		return false;
	};
	disable($("shoutSubmit"));
	$("shoutContent").onkeyup = function onkeydown() {
		window[this.value && !this.placeholder ? "enable" : "disable"]($("shoutSubmit"));
	};
},

addShout: function(content) {
	disable($("shoutSubmit"));
	Ajax.request({
		url: esoTalk.baseURL + "ajax.php?controller=profile",
		post: "action=shout&memberTo=" + encodeURIComponent(this.member) + "&content=" + encodeURIComponent(content),
		success: function() {
			if (this.messages) {
				enable($("shoutSubmit"));
				return;
			} else disable($("shoutSubmit"));
			var div = document.createElement("div");
			div.innerHTML = this.result.html;
			div.id = "shout" + this.result.shoutId;
			div.style.overflow = "hidden";
			$("shouts").insertBefore(div, $("shouts").firstChild);
			Shouts.animateNewShout(div);
			$("shoutContent").value = "";
			$("shoutContent").blur();
			Messages.hideMessage("waitToReply");
		}
	});
},

deleteShout: function(shoutId) {
	Ajax.request({
		url: esoTalk.baseURL + "ajax.php?controller=profile",
		post: "action=deleteShout&shoutId=" + shoutId,
		success: function() {
			Shouts.animateDeleteShout($("shout" + shoutId));
		}
	});
},

animateNewShout: function(shout) {
	(shout.animation = new Animation(function(values, final) {
		shout.style.height = final ? "" : values[0] + "px";
		shout.style.opacity = final ? "" : values[1];
	}, {begin: [0, 0], end: [shout.offsetHeight, 1]})).start();
},

animateDeleteShout: function(shout) {
	shout.style.overflow = "hidden";
	(shout.animation = new Animation(function(opacity, final) {
		shout.style.opacity = opacity;
		if (opacity < .5) {
			(shout.animation = new Animation(function(height, final) {
				shout.style.height = height + "px";
				if (final) shout.parentNode.removeChild(shout);
			}, {begin: shout.offsetHeight, end: 0})).start();
		}
	}, {begin: 1, end: 0})).start();
},

};
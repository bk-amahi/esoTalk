Ajax.request = function(request) {
	if (!request || this.beenLoggedOut) return false;
	if (!request.success) request.success = function() {};
	if (!request._success) request._success = request.success;
	request.success = function() {
		this._success();
		if (!this.json.queries) return;
		$("queries").innerHTML = this.json.queries;
		$("loadTime").innerHTML = this.json.loadTime;
		$("debugPost").innerHTML = this.json.debugPost;
		$("debugGet").innerHTML = this.json.debugGet;
		$("debugFiles").innerHTML = this.json.debugFiles;
		$("debugSession").innerHTML = this.json.debugSession;
		$("debugCookie").innerHTML = this.json.debugCookie;
	};
	this.queue.push(request);
	this.doNextRequest();
};

Ajax.disconnect = function(request) {
	this.disconnected = true;
	request.repeat = true;
	this.disconnectedRequest = request;
	this.queue = [];
	Messages.showMessage("ajaxDisconnected", "warning", esoTalk.language["ajaxDisconnected"], false);
	Messages.showMessage("disconnectedInfo", "info", "<a href='#' onclick='Ajax.toggleDebugInfo(this);return false'>show debug info</a><ul class='form' id='debugInfo' style='display:none;margin-top:1em;overflow:auto;max-height:400px'>" +
		"<li><label>HTTP status code</label><div>" + Ajax.disconnectedRequest.http.status + "</div>" +
		"<li><label>Request URL</label><div>" + Ajax.disconnectedRequest.url + "</div>" +
		"<li><label>POST data</label><div>" + Ajax.disconnectedRequest.post + "</div>" +
		"<li><label>Response text</label><div>" + Ajax.disconnectedRequest.http.responseText.replace(/</g, "&lt;").replace(/>/g, "&gt;") + "</div>" +
		"</ul>", false);
};

Ajax.toggleDebugInfo = function(link) {
	toggle($("debugInfo"));
	link.innerHTML = $("debugInfo").style.display == "none" ? "show debug info" : "hide debug info";
};
/************************************************************************\
| VIPER'S VIDEO QUICKTAGS                                                |
| http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/  |
|************************************************************************|
| This file contains some wrappers for UFO as well as UFO itself.        |
\************************************************************************/

function vvq_youtube(objectID, videoWidth, videoHeight, videoID) {
	var FO = { movie:"http://www.youtube.com/v/" + videoID, width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

function vvq_googlevideo(objectID, videoWidth, videoHeight, videoID) {
	var FO = { movie:"http://video.google.com/googleplayer.swf?docId=" + videoID, width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

function vvq_stage6(objectID, videoWidth, videoHeight, videoID) {
	document.getElementById(objectID).innerHTML = '<object classid="clsid:67DABFBF-D0AB-41fa-9C46-CC0F21721616" codebase="http://go.divx.com/plugin/DivXBrowserPlugin.cab" width="' + videoWidth + '" height="' + videoHeight + '"><param name="autoplay" value="false" /><param name="src" value="http://video.stage6.com/' + videoID + '/.divx" /><param name="custommode" value="Stage6" /><param name="showpostplaybackad" value="false" /><embed type="video/divx" src="http://video.stage6.com/' + videoID + '/.divx" pluginspage="http://go.divx.com/plugin/download/" showpostplaybackad="false" custommode="Stage6" autoplay="false" width="' + videoWidth + '" height="' + videoHeight + '" /></object>';
}

function vvq_ifilm(objectID, videoWidth, videoHeight, videoID) {
	var FO = { movie:"http://www.ifilm.com/efp", flashvars:"flvbaseclip=" + videoID, width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

function vvq_metacafe(objectID, videoWidth, videoHeight, videoID, videoName) {
	var FO = { movie:"http://www.metacafe.com/fplayer/" + videoID + "/" + videoName + ".swf", flashvars:"playerVars=playerVars=showStats=yes|autoPlay=no", width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

function vvq_myspace(objectID, videoWidth, videoHeight, videoID) {
	var FO = { movie:"http://lads.myspace.com/videos/vplayer.swf", flashvars:"m=" + videoID + "&type=video", width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

function vvq_vimeo(objectID, videoWidth, videoHeight, videoID) {
	var FO = { movie:"http://www.vimeo.com/moogaloop.swf?clip_id=" + videoID + "&server=vimeo.com&fullscreen=1&show_title=1&show_byline=1&show_portrait=0&color=01AAEA", width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent", flashvars:"allowfullscreen=true&scale=showAll" };
	UFO.create(FO, objectID);
}

function vvq_flv(objectID, videoWidth, videoHeight, SWFURL, FLVFileURL) {
	var FO = { movie:SWFURL, flashvars:"file=" + FLVFileURL + "&usefullscreen=false", allowFullScreen:"true", width:videoWidth, height:videoHeight, majorversion:"7", build:"0", wmode:"transparent" };
	UFO.create(FO, objectID);
}

// Get around Internet Explorer's retarded click-to-activate thing by writing the HTML via Javascript
function vvq_quicktime(objectID, videoWidth, videoHeight, videoURL) {
	document.getElementById(objectID).innerHTML = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="' + videoWidth + '" height="' + videoHeight + '"><param name="src" value="' + videoURL + '" /><param name="controller" value="true" /><param name="autoplay" value="false" /><param name="wmode" value="transparent" /><object type="video/quicktime" data="' + videoURL + '" width="' + videoWidth + '" height="' + videoHeight + '" class="mov"><param name="controller" value="true" /><param name="autoplay" value="false" /><p><a href="' + videoURL + '">' + videoURL + '</a></p></object></object>';
}

function vvq_videoWMP(objectID, videoWidth, videoHeight, videoURL) {
	document.getElementById(objectID).innerHTML = '<object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" standby="Loading Microsoft Windows Media Player components..." type="application/x-oleobject" width="' + videoWidth + '" height="' + videoHeight + '"><param name="url" value="' + videoURL + '" /><param name="allowchangedisplaysize" value="true" /><param name="autosize" value="true" /><param name="displaysize" value="1" /><param name="showcontrols" value="true" /><param name="showstatusbar" value="true" /><param name="autorewind" value="true" /><param name="autostart" value="false" /><param name="volume" value="100" /></object>';
}

function vvq_videoNoWMP(objectID, videoWidth, videoHeight, videoURL, mimeType) {
	document.getElementById(objectID).innerHTML = '<object type="' + mimeType + '" data="' + videoURL + '" width="' + videoWidth + '" height="' + videoHeight + '" class="vvqbox vvqvideo"><param name="src" value="' + videoURL + '" /><param name="allowchangedisplaysize" value="true" /><param name="autosize" value="true" /><param name="displaysize" value="1" /><param name="showcontrols" value="true" /><param name="showstatusbar" value="true" /><param name="autorewind" value="true" /><param name="autostart" value="false" /><param name="autoplay" value="false" /><param name="volume" value="100" /></object>';
}


/*	Unobtrusive Flash Objects (UFO) v3.20 <http://www.bobbyvandersluis.com/ufo/>
	Copyright 2005, 2006 Bobby van der Sluis
	This software is licensed under the CC-GNU LGPL <http://creativecommons.org/licenses/LGPL/2.1/>
*/

var UFO = {
	req: ["movie", "width", "height", "majorversion", "build"],
	opt: ["play", "loop", "menu", "quality", "scale", "salign", "wmode", "bgcolor", "base", "flashvars", "devicefont", "allowscriptaccess", "seamlesstabbing"],
	optAtt: ["id", "name", "align"],
	optExc: ["swliveconnect"],
	ximovie: "ufo.swf",
	xiwidth: "215",
	xiheight: "138",
	ua: navigator.userAgent.toLowerCase(),
	pluginType: "",
	fv: [0,0],
	foList: [],
		
	create: function(FO, id) {
		if (!UFO.uaHas("w3cdom") || UFO.uaHas("ieMac")) return;
		UFO.getFlashVersion();
		UFO.foList[id] = UFO.updateFO(FO);
		UFO.createCSS("#" + id, "visibility:hidden;");
		UFO.domLoad(id);
	},

	updateFO: function(FO) {
		if (typeof FO.xi != "undefined" && FO.xi == "true") {
			if (typeof FO.ximovie == "undefined") FO.ximovie = UFO.ximovie;
			if (typeof FO.xiwidth == "undefined") FO.xiwidth = UFO.xiwidth;
			if (typeof FO.xiheight == "undefined") FO.xiheight = UFO.xiheight;
		}
		FO.mainCalled = false;
		return FO;
	},

	domLoad: function(id) {
		var _t = setInterval(function() {
			if ((document.getElementsByTagName("body")[0] != null || document.body != null) && document.getElementById(id) != null) {
				UFO.main(id);
				clearInterval(_t);
			}
		}, 250);
		if (typeof document.addEventListener != "undefined") {
			document.addEventListener("DOMContentLoaded", function() { UFO.main(id); clearInterval(_t); } , null); // Gecko, Opera 9+
		}
	},

	main: function(id) {
		var _fo = UFO.foList[id];
		if (_fo.mainCalled) return;
		UFO.foList[id].mainCalled = true;
		document.getElementById(id).style.visibility = "hidden";
		if (UFO.hasRequired(id)) {
			if (UFO.hasFlashVersion(parseInt(_fo.majorversion, 10), parseInt(_fo.build, 10))) {
				if (typeof _fo.setcontainercss != "undefined" && _fo.setcontainercss == "true") UFO.setContainerCSS(id);
				UFO.writeSWF(id);
			}
			else if (_fo.xi == "true" && UFO.hasFlashVersion(6, 65)) {
				UFO.createDialog(id);
			}
		}
		document.getElementById(id).style.visibility = "visible";
	},
	
	createCSS: function(selector, declaration) {
		var _h = document.getElementsByTagName("head")[0]; 
		var _s = UFO.createElement("style");
		if (!UFO.uaHas("ieWin")) _s.appendChild(document.createTextNode(selector + " {" + declaration + "}")); // bugs in IE/Win
		_s.setAttribute("type", "text/css");
		_s.setAttribute("media", "screen"); 
		_h.appendChild(_s);
		if (UFO.uaHas("ieWin") && document.styleSheets && document.styleSheets.length > 0) {
			var _ls = document.styleSheets[document.styleSheets.length - 1];
			if (typeof _ls.addRule == "object") _ls.addRule(selector, declaration);
		}
	},
	
	setContainerCSS: function(id) {
		var _fo = UFO.foList[id];
		var _w = /%/.test(_fo.width) ? "" : "px";
		var _h = /%/.test(_fo.height) ? "" : "px";
		UFO.createCSS("#" + id, "width:" + _fo.width + _w +"; height:" + _fo.height + _h +";");
		if (_fo.width == "100%") {
			UFO.createCSS("body", "margin-left:0; margin-right:0; padding-left:0; padding-right:0;");
		}
		if (_fo.height == "100%") {
			UFO.createCSS("html", "height:100%; overflow:hidden;");
			UFO.createCSS("body", "margin-top:0; margin-bottom:0; padding-top:0; padding-bottom:0; height:100%;");
		}
	},

	createElement: function(el) {
		return (UFO.uaHas("xml") && typeof document.createElementNS != "undefined") ?  document.createElementNS("http://www.w3.org/1999/xhtml", el) : document.createElement(el);
	},

	createObjParam: function(el, aName, aValue) {
		var _p = UFO.createElement("param");
		_p.setAttribute("name", aName);	
		_p.setAttribute("value", aValue);
		el.appendChild(_p);
	},

	uaHas: function(ft) {
		var _u = UFO.ua;
		switch(ft) {
			case "w3cdom":
				return (typeof document.getElementById != "undefined" && typeof document.getElementsByTagName != "undefined" && (typeof document.createElement != "undefined" || typeof document.createElementNS != "undefined"));
			case "xml":
				var _m = document.getElementsByTagName("meta");
				var _l = _m.length;
				for (var i = 0; i < _l; i++) {
					if (/content-type/i.test(_m[i].getAttribute("http-equiv")) && /xml/i.test(_m[i].getAttribute("content"))) return true;
				}
				return false;
			case "ieMac":
				return /msie/.test(_u) && !/opera/.test(_u) && /mac/.test(_u);
			case "ieWin":
				return /msie/.test(_u) && !/opera/.test(_u) && /win/.test(_u);
			case "gecko":
				return /gecko/.test(_u) && !/applewebkit/.test(_u);
			case "opera":
				return /opera/.test(_u);
			case "safari":
				return /applewebkit/.test(_u);
			default:
				return false;
		}
	},
	
	getFlashVersion: function() {
		if (UFO.fv[0] != 0) return;  
		if (navigator.plugins && typeof navigator.plugins["Shockwave Flash"] == "object") {
			UFO.pluginType = "npapi";
			var _d = navigator.plugins["Shockwave Flash"].description;
			if (typeof _d != "undefined") {
				_d = _d.replace(/^.*\s+(\S+\s+\S+$)/, "$1");
				var _m = parseInt(_d.replace(/^(.*)\..*$/, "$1"), 10);
				var _r = /r/.test(_d) ? parseInt(_d.replace(/^.*r(.*)$/, "$1"), 10) : 0;
				UFO.fv = [_m, _r];
			}
		}
		else if (window.ActiveXObject) {
			UFO.pluginType = "ax";
			try { // avoid fp 6 crashes
				var _a = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.7");
			}
			catch(e) {
				try { 
					var _a = new ActiveXObject("ShockwaveFlash.ShockwaveFlash.6");
					UFO.fv = [6, 0];
					_a.AllowScriptAccess = "always"; // throws if fp < 6.47 
				}
				catch(e) {
					if (UFO.fv[0] == 6) return;
				}
				try {
					var _a = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
				}
				catch(e) {}
			}
			if (typeof _a == "object") {
				var _d = _a.GetVariable("$version"); // bugs in fp 6.21/6.23
				if (typeof _d != "undefined") {
					_d = _d.replace(/^\S+\s+(.*)$/, "$1").split(",");
					UFO.fv = [parseInt(_d[0], 10), parseInt(_d[2], 10)];
				}
			}
		}
	},

	hasRequired: function(id) {
		var _l = UFO.req.length;
		for (var i = 0; i < _l; i++) {
			if (typeof UFO.foList[id][UFO.req[i]] == "undefined") return false;
		}
		return true;
	},
	
	hasFlashVersion: function(major, release) {
		return (UFO.fv[0] > major || (UFO.fv[0] == major && UFO.fv[1] >= release)) ? true : false;
	},

	writeSWF: function(id) {
		var _fo = UFO.foList[id];
		var _e = document.getElementById(id);
		if (UFO.pluginType == "npapi") {
			if (UFO.uaHas("gecko") || UFO.uaHas("xml")) {
				while(_e.hasChildNodes()) {
					_e.removeChild(_e.firstChild);
				}
				var _obj = UFO.createElement("object");
				_obj.setAttribute("type", "application/x-shockwave-flash");
				_obj.setAttribute("data", _fo.movie);
				_obj.setAttribute("width", _fo.width);
				_obj.setAttribute("height", _fo.height);
				var _l = UFO.optAtt.length;
				for (var i = 0; i < _l; i++) {
					if (typeof _fo[UFO.optAtt[i]] != "undefined") _obj.setAttribute(UFO.optAtt[i], _fo[UFO.optAtt[i]]);
				}
				var _o = UFO.opt.concat(UFO.optExc);
				var _l = _o.length;
				for (var i = 0; i < _l; i++) {
					if (typeof _fo[_o[i]] != "undefined") UFO.createObjParam(_obj, _o[i], _fo[_o[i]]);
				}
				_e.appendChild(_obj);
			}
			else {
				var _emb = "";
				var _o = UFO.opt.concat(UFO.optAtt).concat(UFO.optExc);
				var _l = _o.length;
				for (var i = 0; i < _l; i++) {
					if (typeof _fo[_o[i]] != "undefined") _emb += ' ' + _o[i] + '="' + _fo[_o[i]] + '"';
				}
				_e.innerHTML = '<embed type="application/x-shockwave-flash" src="' + _fo.movie + '" width="' + _fo.width + '" height="' + _fo.height + '" pluginspage="http://www.macromedia.com/go/getflashplayer"' + _emb + '></embed>';
			}
		}
		else if (UFO.pluginType == "ax") {
			var _objAtt = "";
			var _l = UFO.optAtt.length;
			for (var i = 0; i < _l; i++) {
				if (typeof _fo[UFO.optAtt[i]] != "undefined") _objAtt += ' ' + UFO.optAtt[i] + '="' + _fo[UFO.optAtt[i]] + '"';
			}
			var _objPar = "";
			var _l = UFO.opt.length;
			for (var i = 0; i < _l; i++) {
				if (typeof _fo[UFO.opt[i]] != "undefined") _objPar += '<param name="' + UFO.opt[i] + '" value="' + _fo[UFO.opt[i]] + '" />';
			}
			var _p = window.location.protocol == "https:" ? "https:" : "http:";
			_e.innerHTML = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"' + _objAtt + ' width="' + _fo.width + '" height="' + _fo.height + '" codebase="' + _p + '//download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=' + _fo.majorversion + ',0,' + _fo.build + ',0"><param name="movie" value="' + _fo.movie + '" />' + _objPar + '</object>';
		}
	},
		
	createDialog: function(id) {
		var _fo = UFO.foList[id];
		UFO.createCSS("html", "height:100%; overflow:hidden;");
		UFO.createCSS("body", "height:100%; overflow:hidden;");
		UFO.createCSS("#xi-con", "position:absolute; left:0; top:0; z-index:1000; width:100%; height:100%; background-color:#fff; filter:alpha(opacity:75); opacity:0.75;");
		UFO.createCSS("#xi-dia", "position:absolute; left:50%; top:50%; margin-left: -" + Math.round(parseInt(_fo.xiwidth, 10) / 2) + "px; margin-top: -" + Math.round(parseInt(_fo.xiheight, 10) / 2) + "px; width:" + _fo.xiwidth + "px; height:" + _fo.xiheight + "px;");
		var _b = document.getElementsByTagName("body")[0];
		var _c = UFO.createElement("div");
		_c.setAttribute("id", "xi-con");
		var _d = UFO.createElement("div");
		_d.setAttribute("id", "xi-dia");
		_c.appendChild(_d);
		_b.appendChild(_c);
		var _mmu = window.location;
		if (UFO.uaHas("xml") && UFO.uaHas("safari")) {
			var _mmd = document.getElementsByTagName("title")[0].firstChild.nodeValue = document.getElementsByTagName("title")[0].firstChild.nodeValue.slice(0, 47) + " - Flash Player Installation";
		}
		else {
			var _mmd = document.title = document.title.slice(0, 47) + " - Flash Player Installation";
		}
		var _mmp = UFO.pluginType == "ax" ? "ActiveX" : "PlugIn";
		var _uc = typeof _fo.xiurlcancel != "undefined" ? "&xiUrlCancel=" + _fo.xiurlcancel : "";
		var _uf = typeof _fo.xiurlfailed != "undefined" ? "&xiUrlFailed=" + _fo.xiurlfailed : "";
		UFO.foList["xi-dia"] = { movie:_fo.ximovie, width:_fo.xiwidth, height:_fo.xiheight, majorversion:"6", build:"65", flashvars:"MMredirectURL=" + _mmu + "&MMplayerType=" + _mmp + "&MMdoctitle=" + _mmd + _uc + _uf };
		UFO.writeSWF("xi-dia");
	},

	expressInstallCallback: function() {
		var _b = document.getElementsByTagName("body")[0];
		var _c = document.getElementById("xi-con");
		_b.removeChild(_c);
		UFO.createCSS("body", "height:auto; overflow:auto;");
		UFO.createCSS("html", "height:auto; overflow:auto;");
	},

	cleanupIELeaks: function() {
		var _o = document.getElementsByTagName("object");
		var _l = _o.length
		for (var i = 0; i < _l; i++) {
			_o[i].style.display = "none";
			for (var x in _o[i]) {
				if (typeof _o[i][x] == "function") {
					_o[i][x] = null;
				}
			}
		}
	}

};

if (typeof window.attachEvent != "undefined" && UFO.uaHas("ieWin")) {
	window.attachEvent("onunload", UFO.cleanupIELeaks);
}
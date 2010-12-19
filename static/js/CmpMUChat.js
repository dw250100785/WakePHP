;(function($) {
$.extend($.fn, {muchat: function() {

var chatEl = this;

var chat = {
	serverUrl: {
		ws: 'ws://'+document.domain+'/MUChat',
		comet  : 'http://'+document.domain+'/WebSocketOverCOMET/?_route=MUChat',
		polling : 'http://'+document.domain+'/WebSocketOverCOMET/?_route=MUChat'
	},
	tabs: {},
	curTab: '#room',
	status: null,
	authkey: $.cookie('SESSID'),
	ws: null,
	su: false,
	onStatusReady: null,
	availTags: null,
	tags: [],
	userlist: {},
	tagsDefault: ["%private"],
	username: null,
	lastRecipients: null,	
	userlistUpdateTimeout: null,
	sentBytes: 0,
	recvBytes: 0,
	packetSeq: 1,
	callbacks: {},
	acceptPM : function (bool) {
		for (var k in chat.tags) {
			if (chat.tags[k] == '%private') {
				delete chat.tags[k];
			}
		}
		if (bool) {
			chat.tags.push('%private');
		}
		chat.setTags(chat.tags);
	},
	roomSelectScreen : function () {
		chat.query({"cmd":'getAvailTags'},function (o) {
			$('.darkbox', chatEl).remove();    
			$('#tabs', chatEl).append($('<div class="darkbox" style="opacity: 0.5">'));
			$('#tabs', chatEl).append('<div class="darkboxWindow" style="height: 70%; width: 70%;"><span class="i18n">Select a new room:</span><br /><br /><br /><div class="roomsList"></div>').i18n();
			for (var k in o.tags) {
				$('.roomsList', chatEl).append($('<div>').attr('name',k)
				.click(function () {
					if (chat.kicked) {
						chat.kicked = false;
						chat.connect();
					}
					$('#room .messages', chatEl).html('');
					if ($.inArray('%private', chat.tags)) {
						chat.tags = [$(this).attr('name'), '%private'];
					}
					else {
						chat.tags = [$(this).attr('name')];
					}
					chat.setTags();
					$('.darkbox, .darkboxWindow', chatEl).remove();
				})
				.html('<h3>' + $.xmlescape(o.tags[k].title != null ? o.tags[k].title:k) + ' (' + o.tags[k].number + ')</h3>' + (o.tags[k].description != null ? $.xmlescape(o.tags[k].description) : '<i class="i18n">no description</i>') + '<br /><br />').i18n());
			}
			chat.onResize($(window).width(),$(window).height());
		});
	},
	query : function (o, c, d) {
		if (chat.status == 0) {
			return;
		}
		o._id = ++chat.packetSeq;
		if (c) {
			chat.callbacks[o._id] = [c, d];
			$('.ajaxloader', chatEl).show();
		}
		chat.sendPacket(o);
	},
	sendPacket : function (packet) {
		var s = $.toJSON(packet);
		chat.sentBytes += s.length;
		try {
			chat.ws.send(s);
		}
		catch (err) {}
	},
	setForms : function () {
		if (chat.username != null) {
			$('.yourusername', chatEl).text(chat.username);
			$('.inputForm', chatEl).show();
			$('#loginForm', chatEl).hide();
		}
		else {
			$('.inputForm', chatEl).hide();
			$('#loginForm', chatEl).show();
		}
		$('.yourtags', chatEl).text(chat.tags.toString());
	},
	setRecipient : function (username) {
		chat.lastRecipients = '@' + username;
		var e = $('.inputMessage:visible', chatEl).val().split(': ', 2);
		$('.inputMessage:visible', chatEl).val(chat.lastRecipients + ': ' + (e[1] != null ? e[1] : ''));
		$('.inputMessage:visible', chatEl).focus();
	},
	setIgnore : function (username, action) {
		chat.sendPacket({
			cmd: "setIgnore",
			username: username,
			action: action
		});
	},
	updatedUserlist : function () {
		$('#room .userlist', chatEl).html('');
		var found = false;
		for (var k in chat.userlist) {
			var u = chat.userlist[k];
			var titleEl = $('<span>').html($.xmlescape(k));
			var statuses = '';
			for (var j in u) {
				if (u[j].statusmsg != null) {statuses += $.xmlescape(u[j].statusmsg)+' ... ';}
			}
			if (statuses != '') {
				title += ' - '+statuses;
			}
			var tagsEl = $('<span>');
			var i = 0;
			$.each(u[j].tags, function (key, tag) {
				if (tag == '%private') {
					return true;
				}
				tagsEl.append((i++ > 0 ? ', ' : '') + $.xmlescape(tag));
			});
			titleEl.append(' - '+_('Tags:')+' ').append(tagsEl);
			$('#room .userlist', chatEl).append($('<p>').append(
			$('<a href="#" class="MUChatUsername">')
				.attr('title',titleEl.html())
				.data('username', k)
				.html($.xmlescape(k))
				.attr('_id',k)
				.contextMenu({
					menu: $('.MUChatContextMenu', chatEl)
				},
				function(action, el, pos) {
				
						if (action == 'profile') {
							window.open('/'+$('html').attr('lang')+'/Account/Info/?'+$(el).data('username'));
						}
						else if (action == 'sendpm') {
							chat.sendPM($(el).data('username'));
						}
						else if (action == 'ignore') {
							chat.setIgnore($(el).data('username'),true);
						}
						else if (action == 'kick') {
							chat.sendMessage({text: "/kick "+$(el).data('username'),color:"black",tags:chat.tags});
						}
				})
			));
			found = true;
		}
		if (!found) {
			if (chat.tags.toString() != '') {
				$('#room .userlist', chatEl).html("<i>No users</i>");
			}
			else {
				$('#room .userlist', chatEl).html("<i>You\'re not listed on any tags.</i>");
			}
		}
		else {
			$('#room .userlist a', chatEl).tooltip({ 
				track			: true, 
				delay 		: 500, 
				showURL		: false, 
				showBody	: " - ", 
				fade			: 250 
			});
		}
		$('.MUChatUsername', chatEl).live('click', function() {chat.setRecipient($(this).data('username')); return false;});
	},
	setTags : function (tags) {
		if (tags == null) {
			tags = chat.tags;
		}
		else {
			chat.tags = tags;
		}
		chat.sendPacket({
			"cmd": "setTags",
			"tags": tags
		});
	},
	keepalive: function () {
		chat.sendPacket({
			cmd: "keepalive"
		});
	},
	lastTS: 0,
	addressOnChange: function () {
		var $tabs = $('#tabs', chatEl).tabs();
		$tabs.tabs('select', location.hash);
		$('.messages', chatEl).each(function () {
			$(this).scrollTo('100%',{"axis": "y"});
		});
	},
	sendPM: function (username) {
		chat.addTab(username);
	},
	closeTab: function (username) {
		var $tabs = $('#tabs', chatEl).tabs();
		$tabs.tabs("remove", $("#tabli_room_"+username, chatEl).attr('index'));
		$("#tabs ul", chatEl).index($("#tabli_room_"+username, chatEl))
		chat.updateTabIndexes();
		$("#room_"+username, chatEl).remove();
	},
	addTab: function (username) {
		var $tabs = $('#tabs', chatEl).tabs();
		if ($('#room_'+username, chatEl).size() > 0) {
			location.href = '#room_'+username;
			return;
		}
		$('#tabs', chatEl).append($('<div id="room_'+username+'" class="room">').html(
									'<div class="nowrap">'
								+ '<div class="nowrap">'
								+ '<div class="messages messagesWide"></div>'
								+ '</div>'
								+ '<br class="clearfloat" /><br />'
								+ '<form action="#" class="inputForm">'
								+ '<input type="hidden" name="prefix" value="@'+$.xmlescape(username)+': "/>'
								+ '<input type="text" size="70" name="text" autocomplete="off" class="inputMessage" />'
								+ '<select name="color">'
								+		'<option value="black" class="i18n">Black</option>'
								+		'<option value="red" class="i18n">Red</option>'
								+		'<option value="green" class="i18n">Green</option>'
								+		'<option value="blue" class="i18n">Blue</option>'
								+ '</select>'
								+ '<button type="submit" class="i18n">Send</button> (<span class="yourusername"></span>)'
								+ '<br /><br /><span class="i18n">Sent/Received data:</span> <span class="sentDataCounter">~</span>/<span class="recvDataCounter">~</span>'
								+ '</form>'
								+ '</div>').i18n());
		chat.initInputForm();
		$tabs.tabs("add", "#room_"+username,
			$('<span>')
			.append('['+$.xmlescape(username)+'] ')
			.append($('<img style="cursor: pointer" src="files/cross.png"/>')
							.click(function() {
								chat.closeTab('+$.xmlescape($.toJSON(username))+');
								return false;
							}))
		);
		chat.onResize($(window).width(),$(window).height());
		chat.updateTabIndexes();
		location.href = '#room_'+username;
	},
	updateTabIndexes : function () {
		$('#tabs ul:first li', chatEl).each(function (index) {
			$(this).attr('id','tabli_'+$(this).find('a').attr('href').substring(1));
			$(this).attr('index',index);
		});
	},
	onResize : function () {
		var h = $(window).height();
		$('#tabs', chatEl).css({height: h - 70});
		$('.messages', chatEl).css({height: h - 300});
		$('.userlist', chatEl).css({height: h - 300});
		$('.darkboxWindow .flow', chatEl).css({top: $('#tabs', chatEl).height()*0.30, left: $('#tabs', chatEl).width()*0.30});
	},
	init: function () {
		$(window).resize(chat.onResize);
		chat.onResize($(window).width(),$(window).height());
		$.address
			.init(function (event) {})
			.change(chat.addressOnChange);
		$('#tabs', chatEl).tabs({
			remove: function (event, ui) {
				var $tabs = $('#tabs', chatEl).tabs();
				$tabs.tabs('select',$('#tabs li', chatEl).find('a').attr('href'));
			},
			add: function (event, ui) {
				var $tabs = $('#tabs', chatEl).tabs();
				$tabs.tabs('select', '#' + ui.panel.id);
			},
			select: function (event, ui) {
				location.href =  ui.tab.href;
				chat.curTab = location.hash;
				return true;
			}
    });
	},
	initConnect : function () {
		chat.connect();
		setInterval(function () {
			chat.connect();
			$('.sentDataCounter', chatEl).html($.fsize(chat.sentBytes));
			$('.recvDataCounter', chatEl).html($.fsize(chat.recvBytes));
		},1000);
	},
	initInputForm : function () {
		$('.yourusername', chatEl).text(chat.username);
		$('.inputMessage', chatEl).keyboard('esc', function (event) {$(this).val('');});
		$('.inputForm', chatEl).submit(function () {
			var val = $(this).find('.inputMessage', chatEl).val();
			if (val == '') {
				return false;
			}
			var packet = {};
			var o = $(this).formToArray(true);
			for (var k in o) {
				packet[o[k].name] = o[k].value;
			}
			packet.tags = chat.tags;
			if (chat.curTab != '#room') {
				if (packet.prefix != null) {
					packet.text = packet.prefix + packet.text;
					packet['tags'] = ['%private'];
					delete packet.prefix;
				}
			}
			chat.sendMessage(packet);
			var s = '';
			if (chat.lastRecipients != null) {
				var e = val.split(': ',2);
				if ((e[0] != null) && (e[0] == chat.lastRecipients)) {s = e[0]+': ';}
			}
			$(this).find('.inputMessage').val(s);
			$(this).find('.inputMessage').focus();
			return false;
		});
	},
	initLoginForm : function () {
		$('#inputUsername', chatEl).keyboard('esc', function (event) {$('#inputUsername').val('');});
		$('#loginForm', chatEl).submit(function () { 
			setTimeout(function () {
				chat.setUsername($('#inputUsername', chatEl).val());
			},5);
			return false;
		});
	},
	addMsg : function (o) {
		if (o.color == null) {
			o.color = 'black';
		}
		var dateStr = (o.ts != null)?('['+(new Date(o.ts*1000).toTimeString().substring(0,8))+'] '):'';
		var msgEl = $('<p style="color: '+$.xmlescape(o.color)+'">');
		if (o.mtype == 'status') {
			if ((chat.userlist[o.from] != null) && (chat.userlist[o.from][o.sid] != null)) {
				chat.userlist[o.from][o.sid].statusmsg = o.text;
				chat.updatedUserlist();
			}
			msgEl.append('* ').append(
				$('<a href="#" style="color: '+$.xmlescape(o.color)+'" class="MUChatUsername">')
				.data('username',o.from)
				.text(o.from)
			).append(' '+$.xmlescape(o.text));
		}
		else if (o.mtype == 'astatus') {
			msgEl.append(dateStr+'* ').append(
				$('<a href="#" style="color: '+$.xmlescape(o.color)+'" class="MUChatUsername">')
				.data('username',o.from)
				.text(o.from)
			).append(' '+$.xmlescape(o.text));
		}
		else if (o.mtype == 'system') {
			msgEl.append(dateStr+' * '+$.xmlescape(o.text));
		}
		else {
			msgEl.css({'color' : o.color});
			//if ((o.to != null) && (o.to.indexOf(chat.username) != -1)) {style += ' font-weight: bold;';}
			msgEl.append(dateStr+'* ').append(
				$('<a href="#" style="color: '+$.xmlescape(o.color)+'" class="MUChatUsername">')
				.data('username',o.from)
				.text('<'+o.from+'>')
			).append(': '+$.xmlescape(o.text));
		}
		var roomId = '#room';
		if (o.tags != null && o.tags[0] == '%private') {
			if (o.from == chat.username) {
				roomId = '#room_'+o.to[0];
				chat.addTab(o.to[0]);
			}
			else {
				roomId = '#room_'+o.from;
				chat.addTab(o.from);
			}
		}
		else if (o.tab != null) {
			roomId = o.tab;
		}
		$(roomId+' .messages', chatEl).append(msgEl);
		$(roomId+' .messages', chatEl).scrollTo('100%',{"axis": "y"});
		while ($(roomId+' .messages p', chatEl).size() > 200) {
			$(roomId+' .messages p:first', chatEl).remove();
		};
	},
	sendMessage : function (o) {
		o.cmd = "sendMessage";
		o.tab = chat.curTab;
		if (o.tags.length > 1) {
			for (var k in o.tags) {
				if (o.tags[k] == '%private') {
					delete o.tags[k];
				}
			}
		}
		chat.sendPacket(o);
	},
	getHistory : function () {
		chat.sendPacket({
			cmd: "getHistory",
			tags: chat.tags,
			lastTS: chat.lastTS
		});
	},
	msgCommand : function (o) {
		if (o.ts) {
			chat.lastTS = o.ts;
		}
		chat.addMsg(o);
	},
	youAreModeratorCommand : function (o) {
		chat.su = true;
	},
	availableTagsCommand : function (o) {
		if (chat.availTags == null) {
			$('.darkbox', chatEl).remove();    
			$('#tabs', chatEl).append($('<div class="darkbox" style="opacity: 0.5">'));
			$('#tabs', chatEl).append('<div class="darkboxWindow" style="height: 70%; width: 70%;"><span class="i18n">Select a room:</span><br /><br /><br /><div class="roomsList"></div>').i18n();
			for (var k in o.tags) {
				$('.roomsList', chatEl)
					.append($('<div>')
					.attr('name',k)
					.click(function () {
						if (chat.kicked) {
							chat.kicked = false;
							chat.connect();
						}
						chat.tags.push($(this).attr('name'));
						chat.setTags();
						$('.darkbox, .darkboxWindow', chatEl).remove();
					})
					.html('<h3>' + $.xmlescape(o.tags[k].title != null ? o.tags[k].title:k) + ' (' + o.tags[k].number + ')</h3>' + (o.tags[k].description != null ? $.xmlescape(o.tags[k].description) : '<i class="i18n">no description</i>')+'<br /><br />').i18n());
			}
			chat.onResize($(window).width(),$(window).height());
		}
		chat.availTags = o.tags;
	},
	tagsCommand : function (o) {
		chat.tags = o.tags;
		$('.yourtags', chatEl).text(chat.tags.toString());
		chat.getUserlist();
	},
	userlistCommand : function (o) {
		var newlist = {};
		for (var k in o.userlist) {
			var u = o.userlist[k].username;
			if (newlist[u] == null) {
				newlist[u] = {};
			}
			newlist[u][o.userlist[k].id] = o.userlist[k];
		};
		chat.userlist = newlist;
		chat.updatedUserlist();
	},
	youWereKickedCommand : function (o) {
		chat.kicked = true;
		chat.username = null;
		chat.availTags = null;
		chat.ws.close();
		chat.ws = null;
		$('.darkbox', chatEl).remove();
		$('#tabs', chatEl).append($('<div class="darkbox">'));
		$('#tabs', chatEl).append($('<div class="darkboxWindow flow">')
						.append('<span class="i18n">You were kicked. Reason:</span> <span class="i18n">'+o.reason+'</span><br /><br /><br />')
						.append($('<center>').append($('<button class="i18n">Reconnect</button>').click(function() {
							chat.kicked = false;
							chat.connect();
							$('.darkbox, .darkboxWindow').remove();
						}))
						).i18n()
		);
		chat.onResize($(window).width(),$(window).height());
	},
	cstatusCommand : function (o) {
		if (chat.username == null) {
			chat.setTags(chat.tagsDefault);
			chat.getHistory();
			chat.getUserlist();
		}
		chat.username = o.username;
		chat.setForms();
	},
	joinsUserCommand : function (o) {
		if (o.history == true) {
			return;
		}
		if (chat.userlist[o.username] == null) {
			chat.userlist[o.username] = {};
		}
		chat.userlist[o.username][o.sid] = {"username": o.username, "sid": o.sid, "tags": o.tags, "statusmsg": o.statusmsg};
		chat.updatedUserlist();
	},
	partsUserCommand : function (o) {
		if (o.history == true) {
			return;
		}
		if (chat.userlist[o.username] == null) {
			return;
		}
		if (chat.userlist[o.username][o.sid] == null) {
			return;
		}
		delete chat.userlist[o.username][o.sid];
		var found = false;
		for (var k in chat.userlist[o.username]) {
			found = true;
			break;
		}
		if (!found) {
			delete chat.userlist[o.username];
		}
		chat.updatedUserlist();
	},
	changedUsernameCommand : function (o) {
		if (o.history == true) {
			return;
		}
		if (chat.userlist[o.old] == null) {
			return;
		}
		if (chat.userlist[o.old][o.sid] == null) {
			return;
		}
		var n = o['new'];
		if (chat.userlist[n] == null) {
			chat.userlist[n] = {};
		}
		chat.userlist[n][o.sid] = chat.userlist[o.old][o.sid];
		chat.userlist[n][o.sid].username = n;
		delete chat.userlist[o.old][o.sid];
		var found = false;
		for (var k in chat.userlist[o.old]) {
			found = true;
			break;
		}
		if (!found) {
			delete chat.userlist[o.old];
		}
		chat.updatedUserlist();
	},
	getUserlist : function () {
		if (chat.userlistUpdateTimeout != null) {
			clearTimeout(chat.userlistUpdateTimeout);
		}
		chat.sendPacket({
			cmd: "getUserlist",
			tags: chat.tags
		});
		chat.userlistUpdateTimeout = setTimeout(chat.getUserlist,25000);
	},
	setUsername : function (username) {
		chat.sendPacket({
			cmd: "setUsername",
			username: username
		});
	},
	sendHello : function (authkey) {
		chat.sendPacket({
			cmd: "hello",
			authkey: authkey
		});
	},
	connect : function () {
		if (chat.kicked) {
			return;
		}
		if (chat.ws != null) {
			if (chat.ws.readyState != 2) {
				return;
			}
			else {
				chat.addMsg({"text": "Trying to reconnect..", "color": "gray", "mtype": "system"});
			}
		}
		chat.addMsg({"text": "Connecting...", "color": "gray", "mtype": "system"});
		//chat.ws = new WebSocket(chat.serverUrl.ws);
		chat.ws = new WebSocketConnection({url: chat.serverUrl,root: '/js/'});
		chat.ws.onopen = function () {
			chat.kicked = false;
			chat.addMsg({"text": "Connected successfully.", "color": "gray", "mtype": "system"});
			if (chat.username != null) {
				chat.setUsername(chat.username);
			}
			if (chat.authkey != null) {
				chat.sendHello(chat.authkey);
			}
			chat.su = false;
			setInterval(function () {
				chat.keepalive();
			},20000);
		};
		chat.ws.onmessage = function (e) {
			if (e.data == null) {
				return;
			}
			chat.recvBytes += e.data.length;
			var o = $.parseJSON($.urldecode(e.data));
			if ((typeof (o._id) != 'undefined') && (typeof (chat.callbacks[o._id]) != 'undefined')) {
				chat.callbacks[o._id][0](o,chat.callbacks[o._id][1]);
				delete chat.callbacks[o._id];
			}
			else if (chat[o.type+'Command'] != null) {
				chat[o.type+'Command'](o);
			}
			else {
				alert(o.type+'Command');
			}
		};
		chat.ws.onclose = function () {
			if (!chat.kicked) {
				chat.addMsg({"text": "Ooops! You're disconnected from the server!", "color": "gray", "mtype": "system"});
				chat.ws = null;
			}
		};
	}
};
chat.init();
chat.initInputForm();
chat.initConnect();
chatEl.append($('<ul class="MUChatContextMenu contextMenu">').html(
			'<li class="edit"><a href="#profile" class="i18n">Profile</a></li>'
		+ '<li class="quit separator"><a href="#sendPM" class="i18n">Send private message</a></li>'
		+ '<li class="quit separator"><a href="#ignore" class="i18n">Ignore this user</a></li>'
		+ '<li class="quit separator"><a href="#quit" class="i18n">Quit</a></li>'
		+ '</ul>').i18n());
$('.changeRoomButton', chatEl).live('click', function() {chat.roomSelectScreen();});
$('.MUChatUsername', chatEl).live('click', function() {chat.setRecipient($(this).data('username'));});
$('.accept_pm input', chatEl).change(function() {chat.acceptPM(!this.checked)});

}});
$(function() {$('.MUChat').muchat();});
})(jQuery);
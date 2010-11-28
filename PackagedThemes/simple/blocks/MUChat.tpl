<link href="/css/muchat.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/js/websocket.js"></script>
<script type="text/javascript" src="/js/jquery.contextmenu.old.js"></script>
<script type="text/javascript" src="/js/CmpMUChat.js"></script>
<script>
$.chat.authkey = "yourauthkeyhere";
</script>
<!-- <script type="text/javascript" src="http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js"></script> -->
</head>
<body>
<div id="tabs" class="ui-tabs">
     <ul>
         <li><a href="#room"><span>Room</span></a></li>
     </ul>
     <div id="room" class="room">
       <div class="nowrap">
         <div class="nowrap">
         <div class="messages"></div>
         <div class="userlist"></div>
         </div>
         <br class="clearfloat" /><br />
         <form action="#" class="inputForm" style="display: none">
<input type="text" size="70" name="text" autocomplete="off" class="inputMessage" />
<select name="color">
<option value="black">Black</option>
<option value="red">Red</option>
<option value="green">Green</option>
<option value="blue">Blue</option>
</select>
<input type="submit" value="Send" /> (<span class="yourusername"></span>)
<br /><br />Sent/Received data: <span class="sentDataCounter">~</span>/<span class="recvDataCounter">~</span>&nbsp;&nbsp;&nbsp;&nbsp;<label for="accept_pm" class="accept_pm"><input type="checkbox" id="accept_pm" onchange="$.chat.acceptPM(!this.checked)" value="1" /> Do not accept incoming private messages</label>
&nbsp;&nbsp;&nbsp;<input type="button" onclick="$.chat.roomSelectScreen();" value="Change a room" />
</form>

       </div>
     </div>
</div>
<script type="text/javascript">{literal}
$(document).ready(function() {
	if (!$.chat.loaded) {
		$.chat.loaded = true;
		$.chat.init();
		$.chat.initInputForm();
		$.chat.initConnect();
 }
});
{/literal}</script>
    <div class="contextMenu" id="userContextMenu">
      <ul>
        <li id="profile"><img src="files/folder.png" /> Profile</li>
        <li id="sendpm"><img src="files/email.png" /> Send private message</li>
        <li id="ignore"><img src="files/cross.png" /> Ignore this user</li>
        <li id="kick"><img src="files/cross.png" /> Kick</li>
      </ul>
    </div>
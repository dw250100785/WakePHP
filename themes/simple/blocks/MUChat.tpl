<link href="/css/muchat.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/js/websocket/websocket.js"></script>
<script type="text/javascript" src="/js/CmpMUChat.js"></script>
<div id="tabs" class="ui-tabs">
<ul><li><a href="#room"><span>Room</span></a></li></ul>
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
<option value="black" class="i18n">Black</option>
<option value="red" class="i18n">Red</option>
<option value="green" class="i18n">Green</option>
<option value="blue" class="i18n">Blue</option>
</select>
<button type="submit" class="i18n">Send</button> (<span class="yourusername"></span>)
<br /><br /><span class="i18n">Sent/Received data:</span> <span class="sentDataCounter">~</span>/<span class="recvDataCounter">~</span>
<label for="accept_pm" class="accept_pm"><input type="checkbox" id="accept_pm" value="1" /> <span class="i18n">Do not accept incoming private messages.</span></label>
<button class="changeRoomButton i18n">Change a room</button>
</form>
       </div>
     </div>
</div>

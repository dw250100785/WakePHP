function rand(min, max) {
	if (max) {
		return Math.floor(Math.random() * (max - min + 1)) + min;
	} else {
		return Math.floor(Math.random() * (min + 1));
	}
}


;(function($) {$.extend($.fn, {sheshbesh: function() {

var gameEl = this;
var game = {
	el: this,
	serverUrl: {
		ws: 'ws://'+document.domain+'/MUChat',
		comet  : 'http://'+document.domain+'/WebSocketOverCOMET/?_route=MUChat',
		polling : 'http://'+document.domain+'/WebSocketOverCOMET/?_route=MUChat'
	},
	tabs: {},
	curTab: '#room',
	status: null,
	authkey: $.cookie('SESSID'),

	query : function (o, c, d) {
		if (game.status == 0) {
			return;
		}
		o._id = ++game.packetSeq;
		if (c) {
			game.callbacks[o._id] = [c, d];
			$('.ajaxloader', gameEl).show();
		}
		game.sendPacket(o);
	},
	sendPacket : function (packet) {
		var s = $.toJSON(packet);
		game.sentBytes += s.length;
		try {game.ws.send(s);}
		catch (err) {}
	},
	onResize : function () {
		var h = $(window).height();
	},
	map: {

		group: null, // holder for Kinetic.Group
		fieldSize: 25,
		sideFieldsNum: 18,
		moonCurveHeight: 280,

	},

	init: function () {

		$(gameEl).append($('<div class="gameBoard">'));

		game.stage = new Kinetic.Stage({
         container: $('.gameBoard', gameEl).get(0),
          width: 1200,
          height: 600,
          scale: 0.1,
        });

		$('.kineticjs-content', gameEl).css('border','1px dotted #9C9898');

        //game.stage.setScale(1);
        game.layer = new Kinetic.Layer();
        game.stage.add(game.layer);

        var canvas = game.layer.getCanvas();
    
    	game.map.lineLength = game.map.fieldSize * game.map.sideFieldsNum;
        game.layer.add(game.map.group = new Kinetic.Group({
        	x: 350,
        	y: 350,
        	width: game.map.fieldSize * (game.map.sideFieldsNum + 1),
          	height: game.map.fieldSize * game.map.sideFieldsNum,

        }));
        game.map.group.setOffset(game.map.group.getWidth() / 2, game.map.group.getHeight() / 2);


        if (0) game.map.group.add(new Kinetic.Rect({
        	x: 0,
        	y: 0,
         	width: game.map.group.getWidth(),
         	height: game.map.group.getHeight(),
         	stroke: 'orange',
         	strokeWidth: 5
        }));


        var fieldId = 1;
        for (var sideId = 0; sideId < 4; ++sideId) {
        	var fieldsNum = game.map.sideFieldsNum;
        	if (sideId == 1 || sideId == 3) {
        		--fieldsNum;
        	}
        	for (var i = 0; i < fieldsNum; ++i) {
        		var rect = {
        			id: 'field-' + fieldId,
        			name: 'field',
					width: game.map.fieldSize,
					height: game.map.fieldSize,
					stroke: 'black',
         			strokeWidth: 2,
		        };
		       	if (sideId == 0) { // top
		        	rect.stroke = 'black';
		        	rect.x = game.map.lineLength - game.map.fieldSize * i;
		        } else if (sideId == 1) { // left
		        	rect.y = game.map.fieldSize * i;
		        	rect.stroke = 'green';
		        }
		        else if (sideId == 2) { // bottom 
		        	rect.x = game.map.fieldSize * i;
		        	rect.y = game.map.lineLength - game.map.fieldSize;
		        	rect.stroke = 'red';
		        }
		        else if (sideId == 3) { // right
		        	rect.x = game.map.lineLength;
		        	rect.y = game.map.lineLength - game.map.fieldSize * (i + 1);
		        	rect.stroke = 'blue';
		        
		   		}
		           	if ((i == 4) || (i === fieldsNum - 4)) { // prison
		        		rect.stroke = 'yellow';
		        	}
		        	if (i == Math.ceil(fieldsNum/2)) { // prison
		        		rect.stroke = 'orange';
		        	}

        		game.map.group.add(new Kinetic.Rect(rect));
        		++fieldId;
		    }
        }

       	var $drawMoon = function(data, pos, control, end, fc, sc, arrow) {
			game.map.group.add(new Kinetic.Shape({
				drawFunc: function(ctx) {
					ctx.beginPath();
					ctx.moveTo(pos.x, pos.y);
					ctx.quadraticCurveTo(control.x, control.y, end.x, end.y);
					ctx.strokeStyle = "black";
					ctx.lineWidth = 2;
					ctx.stroke();
					ctx.closePath();
				},
				stroke: "black",
				strokeWidth: 4

			}));
			if (arrow) {
			var arrowShape = new Kinetic.Polygon({
          		points: [
          			52.5, 45,
          			60, 60,
          			45, 60
          		],
          		fill: "black",
        	});
			game.map.group.add(arrowShape);
			arrowShape.rotate(Math.PI * arrow.rotate);
			arrowShape.setX(end.X);
			arrowShape.setY(end.Y);
			arrowShape.move(arrow.move.x, arrow.move.y);
			}

       		$circleWithText = function(pos, text) {
 				var circle = new Kinetic.Circle({
					x: pos.x,
					y: pos.y,
					id: 'field-moon'+data.id+'-'+text,
					name: 'field-moon',
					radius: game.map.fieldSize,
					fill: 'white',
					stroke: 'black',
					strokeWidth: 2
       			});
	       		game.map.group.add(circle);
	       		circle.moveUp();
	       		var complexText = new Kinetic.Text({
					x: circle.getX() - 7,
					y: circle.getY() - 10, 
					text: text,
					opacity: 0.3,
					fontSize: 18,
					fontFamily: 'Calibri',
					textFill: 'black',
					align: 'center',
					fontStyle: 'bold',
    	    	});
       			game.map.group.add(complexText);
    	   		complexText.moveUp();
	       		complexText.moveUp();
       		};
       		if (!fc) {
       			return;
       		}
       		$circleWithText(fc, '1');
			$circleWithText(sc, '3');
       	};
       	var control;
		$drawMoon( // top moon
			/* data */ {id:'1'},
			/* pos */ {x: game.map.fieldSize * 2.5, y: game.map.fieldSize},
			/* control */ control = {x: game.map.group.getWidth() / 2, y: game.map.moonCurveHeight}, 
			/* end */ {x: game.map.group.getWidth() - game.map.fieldSize * 2.5, y: game.map.fieldSize},
			/* first circle */ {x: control.x + game.map.fieldSize * 1.5, y: control.y - game.map.moonCurveHeight / 2 + game.map.fieldSize/2.5},
			/* second circle */ {x: control.x - game.map.fieldSize * 1.5, y: control.y - game.map.moonCurveHeight / 2 + game.map.fieldSize/2.5},
			/* arrow */ {rotate: 1.8, move: {x: -7, y: 19}}
		);
		$drawMoon( // left moon
			/* data */ {id:'2'},
			/* pos */ {x: game.map.fieldSize, y: game.map.fieldSize * 2.5},
			/* control */ control = {x: game.map.moonCurveHeight, y: game.map.group.getHeight() / 2}, 
			/* end */ {x: game.map.fieldSize, y: game.map.group.getHeight() - game.map.fieldSize * 2.5},
			/* first circle */  {x: control.x - game.map.moonCurveHeight / 2 + game.map.fieldSize/2.5, y: control.y - game.map.fieldSize * 1.5},
			/* second circle */ {x: control.x - game.map.moonCurveHeight / 2 + game.map.fieldSize/2.5, y: control.y + game.map.fieldSize * 1.5},
			/* arrow */ {rotate: 1.3, move: {x: 19, y: 457}}
		);

		$drawMoon( // bottom moon
			/* data */ {id:'3'},
			/* pos */ {x: game.map.fieldSize * 2.5, y: game.map.group.getHeight() - game.map.fieldSize},
			/* control */ control = {x: game.map.group.getWidth() / 2, y: game.map.group.getHeight() - game.map.moonCurveHeight}, 
			/* end */ {x: game.map.group.getWidth() - game.map.fieldSize * 2.5, y: game.map.group.getHeight() - game.map.fieldSize},
			/* first circle */ {x: control.x - game.map.fieldSize * 1.5, y: control.y + game.map.moonCurveHeight / 2 - game.map.fieldSize/2.5},
			/* second circle */ {x: control.x + game.map.fieldSize * 1.5, y: control.y + game.map.moonCurveHeight / 2 - game.map.fieldSize/2.5},
			/* arrow */ {rotate: 0.8, move: {x: 481, y: 430}}
		);

		$drawMoon( // right moon
			/* data */ {id:'4'},
			/* pos */ {x: game.map.group.getWidth() - game.map.fieldSize, y: game.map.group.getHeight() - game.map.fieldSize * 2.5},
			/* control */ control = {x: game.map.group.getWidth() - game.map.moonCurveHeight, y: game.map.group.getHeight() / 2}, 
			/* end */ {x: game.map.group.getWidth() - game.map.fieldSize, y: game.map.fieldSize * 2.5},
			/* first circle */  {x: control.x + game.map.moonCurveHeight / 2 - game.map.fieldSize/2.5, y: control.y + game.map.fieldSize * 1.5},
			/* second circle */ {x: control.x + game.map.moonCurveHeight / 2 - game.map.fieldSize/2.5, y: control.y - game.map.fieldSize * 1.5},
			/* arrow */ {rotate: 2.3, move: {x: 457, y: -8}}
		);

		$drawPrison = function(data) {
				var rect = {
        			id: 'field-jail-' + data.id,
        			name: 'field-jail',
					width: game.map.fieldSize,
					height: game.map.fieldSize,
					stroke: 'black',
         			strokeWidth: 2,
         			x: 0,
         			y: 0,
		        };
		};
		$drawPrison({
			id: 1,
		});


		$drawHouse = function(data) {
			var rect = {
        		id: 'field-jail-' + data.id,
        		name: 'field-jail',
				width: game.map.fieldSize,
				height: game.map.fieldSize,
				stroke: 'black',
         		strokeWidth: 2,
         		x: 0,
         		y: 0,
			};
		};
		
		$drawHouse({
			id: 1,
		});

     
		$(window).resize(game.onResize);
		game.onResize($(window).width(),$(window).height());


		 game.layer.draw();
		
		
		$splashEffect = function() {
			var duration = 1;
			duration = 0.01;
			var durationmsec = duration * 1000;
			var newScale = 1;
			var timecount = 0;
			var oldScale = game.stage.getScale().x;
			var diff = newScale - oldScale;
			var scaleAnim = new Kinetic.Animation({
       			func: function(frame) {
       				if (timecount > durationmsec) {
       					timecount = durationmsec;
       					scale = newScale;
       					scaleAnim.stop();
       				}
       				else {
	       				timecount += frame.timeDiff;
       					var scale = oldScale + diff / durationmsec * timecount;
       				}
					game.stage.setScale(scale);
				},
				node: game.layer
			});
			scaleAnim.start();
			game.map.group.rotate(Math.PI * 1.5);
			game.map.group.transitionTo({
				rotation: Math.PI * 2,
				scale: {x:1, y:1},
				x: 550,
				y: 250,
				duration: duration,
			});

		};
		$splashEffect();
		//game.layer.draw();
		//game.initConnect();
	},
	initConnect : function () {
		game.connect();
		setInterval(function () {
			game.connect();
			$('.sentDataCounter', gameEl).html($.fsize(game.sentBytes));
			$('.recvDataCounter', gameEl).html($.fsize(game.recvBytes));
		},1000);
	},
	sendHello : function (authkey) {
		game.sendPacket({
			cmd: "hello",
			authkey: authkey
		});
	},
	connect : function () {
		if (game.ws != null) {
			if (game.ws.readyState != 2) {
				return;
			}
			else {
				game.addMsg({"text": "Trying to reconnect..", "color": "gray", "mtype": "system"});
			}
		}
		game.addMsg({"text": "Connecting...", "color": "gray", "mtype": "system"});
		//game.ws = new WebSocket(chat.serverUrl.ws);
		game.ws = new WebSocketConnection({url: game.serverUrl, root: '/js/'});
		game.ws.onopen = function () {
			game.addMsg({"text": "Connected successfully.", "color": "gray", "mtype": "system"});
			if (game.username != null) {
				game.setUsername(game.username);
			}
			if (game.authkey != null) {
				game.sendHello(game.authkey);
			}
			game.su = false;
			setInterval(function () {
				game.keepalive();
			},20000);
		};
		game.ws.onmessage = function (e) {
			if (e.data == null) {
				return;
			}
			game.recvBytes += e.data.length;
			var o = $.parseJSON($.urldecode(e.data));
			if (typeof (o._id) != 'undefined') {
				if (typeof (game.callbacks[o._id]) != 'undefined') {
					game.callbacks[o._id][0](o, game.callbacks[o._id][1]);
					delete game.callbacks[o._id];
				}
			}
			else if (chat[o.type+'Command'] != null) {
				chat[o.type+'Command'](o);
			}
			else {
				alert(o.type+'Command');
			}
		};
		game.ws.onclose = function () {
			if (!chat.kicked) {
				chat.addMsg({"text": "Ooops! You're disconnected from the server!", "color": "gray", "mtype": "system"});
				chat.ws = null;
			}
			};
	},
	addMsg : function (o) {
	},
};
game.init();


}});
$(function() {$('.sheshbesh').sheshbesh();});
})(jQuery);
function showmsg(msg, time) {
    if (!msg) {
        msg = "操作失败";
    }
    if (!time) {
        time = 1500;
    }
    return layer.msg(msg, {
        time: time,
        shade: [0.8, '#393D49']
    })
}

var ws = {};
var key = "";
var can = 0;
var num = 0;
$(function() {
    opengame();
    $(".gezi").click(function() {
        var span = $(this).find("span");
        var index = $(this).index();
        console.log(num);
        if (num != 2) {
            showmsg("还不够人");
            return;
        }
        if (span.hasClass('o1') || span.hasClass('o2')) {
            showmsg("这个格子已经选过啦");
            return;
        }
        if (can == 0) {
            showmsg("还没到你");
            return;
        }
        span.addClass("o1");
        ws.send('{"type":"post","num":"' + index + '","key":"' + key + '"}');
        can = 0;
    });
})

function randomChar(l) {
    var x = "0123456789qwertyuioplkjhgfdsazxcvbnm";
    var tmp = "";
    var timestamp = new Date().getTime();
    for (var i = 0; i < l; i++) {
        tmp += x.charAt(Math.ceil(Math.random() * 100000000) % x.length);
    }
    return timestamp + tmp;
}


function opengame() {
    showmsg("进入中", 999999);
    ws = new WebSocket("ws://127.0.0.1:7272");
    ws.onopen = onopen;
    // 当有消息时根据消息类型显示不同信息
    ws.onmessage = onmessage;
    ws.onclose = function() {
        console.log("连接关闭，定时重连");
        layer.closeAll();
        // opengame();
    };
    ws.onerror = function() {
        layer.closeAll();
        // layer.msg("出现错误");
        console.log("出现错误");
    };
}

function onopen() {
    console.log("连接成功");
    key = randomChar(15);
    layer.closeAll();
    // showmsg("成功");
    var data = '{"type":"login","key":"' + key + '"}';
    ws.send(data);
}

function onmessage(e) {
    console.log(e.data);
    var data = eval("(" + e.data + ")");
    switch (data['type']) {
        case 'login':
            if (data['num'] == 2) { num = 2; }
            console.log(num);
            if (key == data['key']) {
                showmsg("你已进入", 2000);
                if (data['can'] == 1) { can = 1; }
            } else {
                showmsg("有人进入", 2000);
            }
            break;
        case 'ping':
            ws.send('{"type":"pong"}');
            break;
        case 'post':
            if (data['key'] != key) {
                index = data['num'];
                $(".gezi:eq(" + index + ")").find("span").addClass("o2");
                showmsg("到你了", 1000);
                can = 1;
            }

            if (data['wdata']) {
                if (data['win'] == key) {
                    showmsg("你赢了！！！！！", 999999);
                } else {
                    showmsg("你输了", 999999);

                }
            }
            break;
        case 'logout':
            showmsg("有人退出", 999999);
            num = 0;
            var span = $("span");
            span.each(function() {
                    $(this).removeClass('o1');
                    $(this).removeClass('o2');
                })
                // setTimeout("location.reload()",2000);
            break;
    }


}

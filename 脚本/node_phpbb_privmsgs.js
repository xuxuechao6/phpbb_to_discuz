// test.js
var mysql = require('mysql');
// 创建连接
var pool = mysql.createPool({
    host : '127.0.0.1',
    user : 'root',
    password : '123456',
    database : 'phpbbtest'
})

pool.getConnection(function(err, connection){
    if(err) throw err;

    connection.query('SELECT * FROM `phpbb_privmsgs`', function(err, result){
        if(err) throw err;
        console.log(result.length)
        for (var i=0;i<result.length;i++) {
            var str = result[i].message_text;
            if (str.indexOf("[url") != -1 ||str.indexOf("[code") != -1 ||str.indexOf("[quote") != -1 || str.indexOf("[attach") != -1 ){
                var str2 = str.replace(/&#58;/g, ":");
                var str3 = str2.replace(/&#46;/g, ".");
                var str4 = str3.replace(/:[a-z0-9]{8}\]/g, ']');
                var str5 =str4.replace(/\[code.*?]/g,"[code]")
                var str6 =str5.replace(/\[quote.*?]/g,"[quote]")
                var msg_id = result[i].msg_id;
                if (str !== str6) {
                    var message = str6.replace(/'/g, "\\'");
                    var sql = "UPDATE `phpbb_privmsgs` SET `message_text`='" + message + "' WHERE `msg_id`= '" + msg_id + "'";
                    console.log(sql);
                    connection.query(sql, function (err, result) {
                        if (err) {
                            console.log(sql);
                            throw err;
                        }
                        console.log(result);
                        console.log("结束")
                    });

                }

            }
        }
    })
});

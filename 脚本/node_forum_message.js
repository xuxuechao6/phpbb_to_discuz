// test.js
var mysql = require('mysql');
// 创建连接
var pool = mysql.createPool({
    host : '127.0.0.1',
    user : 'root',
    password : '123456',
    database : 'discuz'
})

pool.getConnection(function(err, connection){
    if(err) throw err;
    connection.query('SELECT * FROM `pre_forum_post`', function(err, result){
        if(err) throw err;
        console.log(result.length)
        for (var i=0;i<result.length;i++) {
            var str = result[i].message;
            if (str.indexOf("[url") != -1) {
                var str2 = str.replace(/&#58;/g, ":");
                var str3 = str2.replace(/&#46;/g, ".");
                var str4 = str3.replace(/:[a-z0-9]{8}\]/g, ']');
                var pid = result[i].pid;
                if (str !== str4) {
                    var str5 = str4.replace(/'/g, "\\'");
                    var sql = "UPDATE `pre_forum_post` SET `message`='" + str5 + "' WHERE `pid`= '" + pid + "'";
                    console.log(sql);
                    connection.query(sql, function (err, result) {
                        if (err) throw err;
                        console.log(result);
                        console.log("结束")
                    });

                }

            }
        }
        })
});

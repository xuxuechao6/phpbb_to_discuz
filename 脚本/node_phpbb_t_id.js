// test.js
var mysql = require('mysql');
// 创建连接
var pool = mysql.createPool({
    host : '127.0.0.1',
    user : 'root',
    password : '123456',
    database : 'phpbb'
})

pool.getConnection(function(err, connection){
    if(err) throw err;
    connection.query('SELECT * FROM `phpbb_posts`', function(err, result){
        if(err) throw err;
       console.log(result.length)
        for (var i=0;i<result.length;i++) {
            var aid = result[i].a_id;
            var post_id = result[i].post_id;
            var str = result[i].post_text;
               var j = str.split("[/attachment]").length-1
           if(j!=0){
                   console.log(str)
               var reg =/\[attachment.*?attachment]/g;
               var str2 = str.replace(reg,function (match) {
                   console.log("match:"+match);
                   var str3=match.split("[attachment=")[1].substr(0, 1)
                   var num = parseInt(aid) -parseInt(str3)
                   console.log("aid:"+aid);
                   console.log("str3:"+str3);
                   console.log("num:"+num);
                   return "[attachment="+num+"]"
               })
               console.log("str2:"+str2)
               if (str !== str2) {
                   var str5 = str2.replace(/'/g, "\\'");
                   var sql = "UPDATE `phpbb_posts` SET `post_text`='" + str5 + "' WHERE `post_id`= '" + post_id + "'";
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

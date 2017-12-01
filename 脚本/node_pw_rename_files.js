var fs = require('fs');
var fileDirectory = "/var/www/html/discuz/phpBB3_files"


// test.js
var mysql = require('mysql');
// 创建连接
var pool = mysql.createPool({
    host : '192.168.1.192',
    user : 'rtt_dev',
    password : 'v5MZbWAtCYymqYPF',
    database : 'rtt_dev'
})

pool.getConnection(function(err, connection){
    if(err) throw err;
    connection.query('SELECT * FROM `pw_attachs`', function(err, result) {
        if (err) throw err;
        console.log(result.length)
        if (fs.existsSync(fileDirectory)) {
            fs.readdir(fileDirectory,result, function (err, files) {
                if (err) {
                    console.log(err);
                    return;
                }
                files.forEach(function (filename) {
                    if(filename.indexOf(".")==-1){

                    
                    for(i=0;i>=1500,i<result.length;i++){
                        var str =result[i].attachurl;
                        if (str != "") {
                            var str2 = str.split(".")[0];
                            console.log("str2:" + str2)
                            if (filename == str2) {
                                var oldFileName = fileDirectory + "/" + filename;
                                var newFileName = fileDirectory + "/" + str;
                                fs.rename(oldFileName, newFileName, function (err) {
                                    if (err) {
                                        console.error(err);
                                    };
                                });
                                //fs.rename()
                                console.log("newFileName:"+newFileName);
                                return false
                            }
                        }
                    }
                }
                });
                console.log(result.length)
            });
        }
        else {
            console.log(fileDirectory + ":  Not Found!");
        }
    })
});

//
//

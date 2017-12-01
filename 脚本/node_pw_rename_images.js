var fs = require('fs');
var fileDirectory = "H:\\wamp64\\www\\web\\phpwindtest\\attachment\\upload\\middle"


// test.js
var mysql = require('mysql');
// 创建连接
var pool = mysql.createPool({
    host : '127.0.0.1',
    user : 'root',
    password : '123456',
    database : 'phpwindtest2'
})

pool.getConnection(function(err, connection){
    if(err) throw err;
    connection.query('SELECT * FROM `pw_members`', function(err, result) {
        if (err) throw err;
        console.log(result.length)
        if (fs.existsSync(fileDirectory)) {
            fs.readdir(fileDirectory,result, function (err, files) {
                if (err) {
                    console.log(err);
                    return;
                }
                files.forEach(function (filename) {
                    console.log("filename:"+filename)

                    if(filename.indexOf("_") != "-1"){
                        fileName = filename.split("_")[1].split(".")[0];
                        console.log("fileName:"+fileName);
                        for(i=0;i<result.length;i++){
                            var str =result[i].icon;
                            if (str != "" && str.indexOf("_")!= "-1") {
                                var str2 = str.split("_")[0];
                                var str3 = str.split("|")[0];
                                console.log("str2:" + str2)
                                console.log("str3:" + str3)
                                if (fileName == str2) {
                                    var oldFileName = fileDirectory + "/" + filename;
                                    var newFileName = fileDirectory + "/" + str3;
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

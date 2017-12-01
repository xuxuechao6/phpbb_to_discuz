<?php

$error_msg = <<<EOT
<h1>数据库语句执行过程中发生了一个错误</h1>
<div class="error">
<h2>系统返回的错误信息：</h2>%s
</div><br />
<div class="sql">
<h2>发生错误的SQL语句：</h2>%s
</div>
EOT;

?>
# CurlMulti-PHP
php模拟多线程同时发起批量请求

### 原理
使用了操作系统的多线程

### 参考
https://github.com/php/php-src/blob/master/ext/curl/interface.c#L472
http://blog.csdn.net/loophome/article/details/53266814

### 方法说明
```php
// 构建请求参数
$curlData = Array();
$curlData["http://localhost/Test/m"] = Array("name"=>"zhangsan","age"=>18);
$curlData["http://localhost/Test/m"] = Array("name"=>"lisi","age"=>19);
// 初始化对象
$CurlMulti = new CurlMultiModel();
// 发起get请求
$CurlMulti->doGet($curlData); 
// 发起post请求
$CurlMulti->doPost($curlData); 
// 获取请求结果
$result = $CurlMulti->getResult();
var_dump($result);
```

### 使用范例：
```php
header("Content-type:text/html;charset=utf-8");
include_once 'CurlMultiModel.php';

$CurlMulti = new CurlMultiModel();
// 请求参数
$curlData = Array ();
$start = 0;
$count = 5;
for($time = $start; $time < ($start + $count); $time ++) {
	$curlData['http://localhost:8080/mnw-war/api/test/testGet?a=' . $time.$time] = ["m_index" => ($time + 1), "n_index" => $time * $time];
}
// 发起请求
$CurlMulti->doGet($curlData);
$res = $CurlMulti->getResult();
// 响应参数
foreach ($res as $key => $val) {
	echo "-----------------------------------------------------<br>";
	echo "请求地址：" . $val["url"] ."<br>";
	echo "请求参数：<br>";
	var_dump($val["params"]);
	echo "<br>";
	echo "响应参数：<br>";
	var_dump($val["response"]);
	echo "<br>";
	echo "-----------------------------------------------------<br>";
}
```
# CurlMulti-PHP
php模拟多线程同时发起批量请求

使用范例：
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

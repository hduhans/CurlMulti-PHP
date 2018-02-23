<?php

class CurlMultiModel {
	
	/* 请求结果 */
	private $result;
	
	/* curl对象列表 */
	private $chList = array();
	
	/* 保存请求参数 */
	private $paramList = array();
	
	/* 附加参数序号  */
	private $param_index = 0;
	
	/* 附加参数前缀 */
	private $param_name = "_mpi_";
	
	function __construct(){
		$this->param_name .= rand(10, 1000000);
	}
	
	/**
	 * 获得请求结果
	 * @return multitype:
	 */
	public function getResult() {
		return $this->result;
	}
	
	/**
	 * 发起get请求
	 * @param unknown $curlData
	 */
	public function doGet($curlData) {
		return $this->doCurl($curlData, false);
	}
	
	/**
	 * 发起post请求
	 * @param unknown $curlData
	 * @return Ambigous <string, void>
	 */
	public function doPost($curlData) {
		return $this->doCurl($curlData, true);
	}
	
	/**
	 * 执行批量请求
	 * @param unknown $curlData
	 * @return string
	 */
	private function doCurl($curlData, $isPost) {
		// 初始化校验
		$initSuccess = $this->init($curlData, $isPost);
		if (!$initSuccess || count($this->chList) == 0) {
			if ($this->result == "") {
				$this->result = "初始化失败";
			}
			return;
		}
		// 创建多请求执行对象
		$downloader = curl_multi_init();
		// 将三个待请求对象放入下载器中
		foreach ($this->chList as $ch) {
			curl_multi_add_handle($downloader, $ch);
		}
		// 是否执行成功
		$isSuccess = true;
		$this->result = Array();
		// 轮询执行请求
		while (true) {
			// 遍历等待执行完成
			while (($execrun = curl_multi_exec($downloader, $running)) == CURLM_CALL_MULTI_PERFORM);
			if ($execrun != CURLM_OK) {
				$isSuccess = false;
				$this->result = "请求失败";
				break;
			}
			
			// 处理完成的请求
			while ($done = curl_multi_info_read($downloader)) {
				// 从请求中获取信息、内容、错误
				$info = curl_getinfo($done['handle']);
				$output = curl_multi_getcontent($done['handle']);
				$error = curl_error($done['handle']);
	
				$url = $info["url"];
				$tmp_param_index = $this->getLastParam($url, $this->param_name);
				$params = $this->paramList[$tmp_param_index];
				$url = substr($url, 0, strpos($url, $this->param_name) - 1);
				
				// 保存请求结果
				$this->result[] = Array(
					"url" => $url,
					"params" => $params,
					"response" => $output
				);
	
				// 清除已完成的请求
				curl_multi_remove_handle($downloader, $done['handle']);
			}
	
			// 当没有数据的时候进行堵塞，把 CPU 使用权交出来，避免上面死循环空跑数据导致 CPU 100%
			if ($running) {
				$rel = curl_multi_select ( $downloader, 1 );
				if ($rel == - 1) {
					usleep ( 1000 );
				}
			}
	
			if ($running == false) {
				break;
			}
		}
		
		// 请求完毕,关闭下载器
		curl_multi_close($downloader);
		return "批量请求成功";
	}
	
	/**
	 * 批量请求初始化
	 * @param unknown $curlData
	 * @return boolean
	 */
	private function init($curlData, $isPost) {
		foreach ($curlData as $url => $params) {
			if (!$this->isLegalUrl($url)) {
				$this->result = "初始化失败：" . $url. "不是一个合法的地址";
				return false;
			}
			if (!is_array($params)) {
				$this->result = "初始化失败：" . $url. "不是数组";
				return false;
			}
			if (!$isPost) {
				$url = $this->appendParamArray($url, $params);
			}
			$url = $this->appendParam($url, $this->param_name, $this->param_index);
			$this->paramList[] = $params;
			$this->chList[] = $this->initCurlObject($url, $isPost, $params);
			$this->param_index++;
		}
		return true;
	}
	
	/**
	 * 初始化curl对象
	 * @param unknown $url
	 * @param unknown $paramData
	 * @param unknown $header
	 * @return resource
	 */
	private function initCurlObject($url, $isPost, $paramData = array()) {
		$options = array();
		$url = trim($url);
		$options[CURLOPT_TIMEOUT] = 10;
		$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
		$options[CURLOPT_RETURNTRANSFER] = true;
		if ($isPost) {
			$options[CURLOPT_POST] = true;
			if (!empty($paramData) && is_array($paramData)) {
				$options[CURLOPT_POSTFIELDS] = http_build_query($paramData);
			}
		}  
		if (stripos($url, 'https') === 0) {
			$options[CURLOPT_SSL_VERIFYPEER] = false;
		}
		$options[CURLOPT_URL] = $url;
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		return $ch;
	}
	
	/**
	 * 判断url是否合法地址
	 * @param unknown $str
	 * @return boolean
	 */
	private function isLegalUrl($str) {
		return $this->startWith($str, "http://") || $this->startWith($str, "https://");
	}
	
	/**
	 * 判断字符串是否以$part开头
	 * @param unknown $str
	 * @param unknown $part
	 * @return boolean
	 */
	private function startWith($str, $part) {
		return strpos($str, $part) === 0;
	}
	
	/**
	 * url后附加单个请求参数
	 * @param unknown $url
	 * @param unknown $param_name
	 * @param unknown $param_val
	 * @return string
	 */
	private function appendParam($url, $param_name, $param_val) {
		return $url . $this->calcUrlParamSymbol($url) . $param_name . "=" . $param_val;
	}
	
	/**
	 * url后附加多个请求参数
	 * @param unknown $url
	 * @param unknown $param_array
	 * @return unknown|string
	 */
	private function appendParamArray($url, $param_array) {
		if (!is_array($param_array) || empty($param_array)) {
			return $url;
		}
		return $url . $this->calcUrlParamSymbol($url) . http_build_query($param_array);
	}
	
	/**
	 * 计算url下一个参数连接符
	 * @param unknown $url
	 * @return string
	 */
	private function calcUrlParamSymbol($url) {
		return strpos($url, "?") === FALSE ? "?" : "&";
	}
	
	/**
	 * 获得url最后一个参数值
	 * @param unknown $url
	 * @param unknown $param_name
	 * @return Ambigous <string, unknown>
	 */
	function getLastParam($url, $param_name) {
		return preg_match('/' . $param_name .'=(.*)$/i', $url, $matches) ? $matches[1] : "";
	}
	
}

?>
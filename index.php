<?php
/**
 * wechat php test
 */

//define your token
//require_once "BaeFetchUrl.class.php";
require "sFetchUrl.php";
require_once "simple_html_dom.php";
require_once 'AipOcr.php'; //for OCR 

// 定义OCR常量
//@2017/3/20
define("OCR_APP_ID", "9346425");
define("OCR_API_KEY", "z4QhGg5AjlGXyH6HNCGbNz1s");
define("OCR_SECRET_KEY", "YfLCFUnaNU7EfMAx0Yf9ZqUH1oexSfEb");

//定义普通的常量
define("TOKEN", "wechatTrans");
define("APIKEY", "UjeA5SxF10EY86Gt0fsWtzcQ");
//Baidu trans cloud released, have to updated to there. 2015/11/11
define("BAIDU_TRANS_APIID","20151111000005014");
define("BD_TRANS_APIKEY","jChDKz7uksuVbbiapKWv");
$wechatObj = new wechatCallbackapiTest();
$wechatObj -> responseMsg();

class wechatCallbackapiTest {
	public function valid() {
		$echoStr = $_GET["echostr"];

		echo $echoStr;

		//valid signature , option
		if ($this -> checkSignature()) {
			echo $echoStr;
			exit ;
		}
	}

	public function responseMsg() {
		//get post data, May be due to the different environments
		$postStr = file_get_contents('php://input');

		//extract post data
		if (!empty($postStr)) {

			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$fromUsername = $postObj -> FromUserName;
			$toUsername = $postObj -> ToUserName;
			$keyword = trim($postObj -> Content);
			$time = time();
			$msgType = $postObj -> MsgType;
			$event = $postObj -> Event;
			$createTime = $postObj -> CreateTime;
			$textTpl = "<xml>
            			<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";

			// Handle subscribe event
			if ($event == 'subscribe') {
				$contentStr = "感谢关注多语言翻译！
				欢迎回复命令进行互动：
				支持语音识别，直接发一段中文语音便能得到该段文字英语和日语的翻译。
				直接输入任何语言自动识别并翻译成中文返回。
				直接输入中文自动翻译成英语。
				?任意中文， 可把中文翻译成英语再朗读出来。
				@任何中文 ，获取日语。
				上传任何图片可以自动识别并获得翻译。
				完整的翻译和朗读功能请回复‘简介’获得简介和完整命令介绍,已支持超过7种常用语言互译。
				作者邮箱hellosharing123@gmail.com。欢迎留言。
				
				请尽量输入简短的语句提高成功率及响应速度。
				翻译结果仅作学习练习参考。";
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
				echo $resultStr;
			} else if ($event == 'unsubscribe') {//handle unsubscribe event
				$contentStr = "请不要离开我。";
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
				echo $resultStr;
			} else if ($msgType == 'text') {
				//判定发来消息的人是否已经在数据库里，如果没有就log一下
				if (($keyword == '*注册*')) {
					if ($this -> getUserNameFromDB($fromUsername)) {
						//已存在，do nothing
						//$username_fromDB = $this -> getUserNameFromDB($fromUsername);
						$contentStr = "您好，您已存在在数据库中，无需再次注册";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					} else {
						//不存在，存一下
						if ($this -> saveOpenID($fromUsername, $createTime)) {
							$contentStr = "已注册";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						} else {
							$contentStr = "注册失败";
							$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
							echo $resultStr;
						}
					}
				}
				//  handle text feedback
				else if (($keyword == '简介')) {//回复简介
					$contentStr = "这是一个简单的多语言翻译公众微信。
				您可回复以下命令进行互动(符号后不用加空格)：
				支持语音识别，直接发一段中文语音便能得到该段文字英语和日语的翻译。
				直接输入任何语言自动识别并翻译成中文返回。
				直接输入中文自动翻译成英语。
				?任意中文， 可把中文翻译成英语再朗读出来。
				上传任何图片可以自动识别并获得翻译。
				+任何文字，获取该段文字的二维码图片。
				@任何中文 ，获取日语。
				&任何中文，获取法语。
				-任何中文，获取德语。
				_任何中文，获取俄语。
				~任何中文，获取阿拉伯语。
				%任何中文，获取西班牙语。
				^任何中文，获取泰语。
				*任何中文，获取韩语。
				。任何中文，获取古文。
				=任何中文，获取粤语。
				\$任何中文，强制翻译成英语。(自动识别可能会把某些中文字符认作日语翻回中文)
				!任意英语， 获取英语朗读，长度不超过100字。
				输入‘简介’：获得简介。
				如有其他反馈，欢迎发送邮件到hellosharing123@gmail.com联系作者。
				请尽量输入简短的语句提高成功率及响应速度。
				翻译结果仅作学习练习参考。";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				} else if (((stripos($keyword, "#") == 0) && (stristr($keyword, "#"))) || ((stripos($keyword, "＃") == 0) && (stristr($keyword, "＃")))) {//处理留言，发送给目标邮箱
					$pattern_removePrefix = '/[^#].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];

					$subject = $fromUsername . "发来了留言";
					$to = "hellosharing123@gmail.com";
					$message = $keyword;
					$bcms_queue = "b8712f2d3588a598ade016e7f1f5440d";
					//判定是否是已知用户
					if ($this -> getUserNameFromDB($fromUsername)) {
						$username_fromDB = $this -> getUserNameFromDB($fromUsername);
						$subject = $username_fromDB . "发来了留言";
						$this -> mail_bcms($to, $subject, $message, $bcms_queue, 'hellosharing123@gmail.com');

						$contentStr = $username_fromDB . "您好，我们已收到您的留言。会认真拜读并给予反馈的。";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					} else {
						$this -> mail_bcms($to, $subject, $message, $bcms_queue, 'hellosharing123@gmail.com');
						$contentStr = "我们已收到您的留言，会认真拜读并给予反馈的。";
						$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
						echo $resultStr;
					}

				} else if (((stripos($keyword, "@") == 0) && (stristr($keyword, "@"))) || ((stripos($keyword, "＠") == 0) && (stristr($keyword, "＠")))) {//输入中文翻成日语。
					$pattern_removePrefix = '/[^@].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_1 = '/[^＠].*/';
					preg_match($pattern_removePrefix_1, $keyword, $afterRemeval_1);
					$keyword = $afterRemeval_1[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=jp";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// //$fetchjTransURL -> get($jTrans_URL);
					// //$jTrans_ResTemp = $fetchjTransURL -> getResponseBody();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);


					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=jp";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;
					
					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if ((stripos($keyword, "&") == 0) && (stristr($keyword, "&"))) {//输入中文翻成法语。
					$pattern_removePrefix = '/[^&].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=fra";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// //$fetchjTransURL -> get($jTrans_URL);
					// //$jTrans_ResTemp = $fetchjTransURL -> getResponseBody();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=fra";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if (((stripos($keyword, "%") == 0) && (stristr($keyword, "%"))) || ((stripos($keyword, "％") == 0) && (stristr($keyword, "％")))) {//输入中文翻成西班牙语。
					$pattern_removePrefix = '/[^%].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^％].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=spa";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// //$fetchjTransURL -> get($jTrans_URL);
					// //$jTrans_ResTemp = $fetchjTransURL -> getResponseBody();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=spa";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if (((stripos($keyword, "~") == 0) && (stristr($keyword, "~"))) || ((stripos($keyword, "～") == 0) && (stristr($keyword, "～")))) {//输入中文翻成阿拉伯语。
					$pattern_removePrefix = '/[^~].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^～].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=ara";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// //$fetchjTransURL -> get($jTrans_URL);
					// //$jTrans_ResTemp = $fetchjTransURL -> getResponseBody();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=ara";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);
					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if ((stripos($keyword, "*") == 0) && (stristr($keyword, "*"))) {//输入中文翻成韩语。
					$pattern_removePrefix = '/[^\*].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^\*].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=kor";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=kor";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if (((stripos($keyword, "-") == 0) && (stristr($keyword, "-"))) || ((stripos($keyword, "—") == 0) && (stristr($keyword, "—")))) {//输入中文翻成德语。
					$pattern_removePrefix = '/[^\-].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^\—].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=de";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=de";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if ((stripos($keyword, "=") == 0) && (stristr($keyword, "="))) {//输入中文翻成粤语。
					$pattern_removePrefix = '/[^\=].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=yue";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=yue";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				}else if ((stripos($keyword, "。") == 0) && (stristr($keyword, "。"))) {//输入中文翻成文言文。
					$pattern_removePrefix = '/[^。].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^。].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=wyw";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=wyw";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];
					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				} else if ((stripos($keyword, "^") == 0) && (stristr($keyword, "^"))) {//输入中文翻成泰语。
					$pattern_removePrefix = '/[^\^].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					$pattern_removePrefix_2 = '/[^\^].*/';
					preg_match($pattern_removePrefix_2, $keyword, $afterRemeval_2);
					$keyword = $afterRemeval_2[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=th";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=th";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];

					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if ((stripos($keyword, "_") == 0) && (stristr($keyword, "_"))){//输入中文翻成俄语。   2016/4/30
					$pattern_removePrefix = '/[^\_].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];
					

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=th";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix_ru = "&from=zh&to=ru";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL_ru = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix_ru.$jTrans_PostSign;
					$fetchjTransURL_ru = new sFetchUrl();
					$jTrans_ResTemp_ru = $fetchjTransURL_ru -> get($jTrans_URL_ru);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);

					// $contentStr = $jTrans_Res_2[0];

					//2015/5/4 udpated RE into JSON parser
					$sJSON_ru = json_decode($jTrans_ResTemp_ru);
					$contentStr_ru = $sJSON_ru->trans_result[0]->dst;

					$contentStr_ru = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr_ru);
					$resultStr_ru = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr_ru);
					echo $resultStr_ru;

				} else if ((stripos($keyword, "$") == 0) && (stristr($keyword, "$"))) {//输入中文翻成英语。
					$pattern_removePrefix = '/[^$].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					$keyword = $afterRemeval[0];

					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_PostFix = "&from=zh&to=en";
					// $jTrans_Q = "&q=" . $keyword;
					// $jTrans_URL = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix;
					// $fetchjTransURL = new sFetchUrl();
					// $jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=en";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					
					// $pattern_transRes_1 = '/"dst":".*"/';
					// preg_match($pattern_transRes_1, $jTrans_ResTemp, $jTrans_Res_1);
					// $jTrans_ResTemp1 = $jTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":"].*[^"]/';
					// preg_match($pattern_transRes_2, $jTrans_ResTemp1, $jTrans_Res_2);
					
					//2015/5/4 改掉了原来的正则表达式方法，换成用解析json的方法去获取dst字段。好处是可以规避那个align的defect
					$sJSON = json_decode($jTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					//$contentStr = $jTrans_Res_2[0];
					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;

				} else if (((stripos($keyword, "+") == 0) && (stristr($keyword, "+"))) || ((stripos($keyword, "＋") == 0) && (stristr($keyword, "＋")))) {//生成二维码功能
					$keyword = trim($keyword);
					$rep = array("+");
					$keyword = str_replace($rep, "", $keyword);
					$rep = array("＋");
					$keyword = str_replace($rep, "", $keyword);

					$qr_content = urlencode($keyword);
					$qrcode_bas_url = "http://wechattrans.duapp.com/QR_Generator.php?id=" . $qr_content;
					$contentStr = "<a href='" . $qrcode_bas_url . "'>点此获得密语二维码</a>";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}
				//翻译并朗读功能
				else if (((stripos($keyword, "?") == 0) && (stristr($keyword, "?"))) || ((stripos($keyword, "？") == 0) && (stristr($keyword, "？")))) {
					$pattern_removePrefix = '/[^?].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					//过滤英语的?
					$afterRemeval = str_ireplace("？", "", $afterRemeval[0]);
					//过滤中文的？

					$keyword = $afterRemeval;
					//中文->英文。
					// $apiKey = APIKEY;
					// $autoTrans_clientAPI = "?client_id=" . $apiKey;
					// $autoTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $autoTrans_PostFix = "&from=zh&to=en";
					// $autoTrans_Q = "&q=" . $keyword;
					// $autoTrans_URL = $autoTrans_URL_Basic . $autoTrans_clientAPI . $autoTrans_Q . $autoTrans_PostFix;
					// $fetchAutoTransURL = new sFetchUrl();
					// $autoTrans_ResTemp = $fetchAutoTransURL -> get($autoTrans_URL);

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=zh&to=en";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$autoTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);


					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $autoTrans_ResTemp, $autoTrans_Res_1);
					// $autoTrans_ResTemp1 = $autoTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $autoTrans_ResTemp1, $autoTrans_Res_2);

					// $contentStr = $autoTrans_Res_2[0];

					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($autoTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;

					$voiceSection = " <xml>
									 <ToUserName><![CDATA[%s]]></ToUserName>
									 <FromUserName><![CDATA[%s]]></FromUserName>
									 <CreateTime>%s</CreateTime>
									 <MsgType><![CDATA[music]]></MsgType>
									 <Music>
									 <Title><![CDATA[%s]]></Title>
									 <Description><![CDATA[%s]]></Description>
									 <MusicUrl><![CDATA[%s]]></MusicUrl>
									 <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
									 </Music>
									 </xml>";
					//Try to transmit text to voice and get the content.
					$fetchVoiceURL = new sFetchUrl();

					//$fetchVoiceURL -> get("http://stuffthatspins.com/stuff/php-TTS/index.php?heckle=" . urlencode($contentStr));
					//$voicePageContent = $fetchVoiceURL -> getResponseBody();
					$voicePageContent = $fetchVoiceURL -> get("http://stuffthatspins.com/stuff/php-TTS/index.php?heckle=" . urlencode($contentStr));

					$pattern_mp3_1 = '/MP3: http:.*mp3/';
					if (preg_match($pattern_mp3_1, $voicePageContent, $voiceResult)) {
						$voiceURL = $voiceResult[0];
						$HQVoice = $voiceResult[0];
						$pattern_mp3_2 = '/[^MP3:].*/';
						if (preg_match($pattern_mp3_2, $voiceURL, $voiceResult_2)) {
							$voiceTitle = "点击播放英语";
							$voiceURL = $voiceResult_2[0];
							$HQVoice = $voiceResult_2[0];
							//这里没想清楚，不过先沿用吧。
							if ($afterRemeval) {
								$voiceDes = $contentStr;
								//$voiceDes = $afterRemeval[0];
							} else {
								$voiceDes = $contentStr;
							}
						}
					} else {
						$voiceTitle = "Click to read";
						$voiceDes = "Fail to translate.";
						$voiceURL = "http://stuffthatspins.com/stuff/php-TTS/files/1377515377.mp3";
						$HQVoice = "http://stuffthatspins.com/stuff/php-TTS/files/1377515377.mp3";
					}
					$resultStr = sprintf($voiceSection, $fromUsername, $toUsername, $time, $voiceTitle, $voiceDes, $voiceURL, $HQVoice);
					echo $resultStr;

				}
				//直接读英语功能
				else if (((stripos($keyword, "!") == 0) && (stristr($keyword, "!"))) || (stripos($keyword, "！") == 0) && (stristr($keyword, "！"))) {
					$voiceSection = " <xml>
									 <ToUserName><![CDATA[%s]]></ToUserName>
									 <FromUserName><![CDATA[%s]]></FromUserName>
									 <CreateTime>%s</CreateTime>
									 <MsgType><![CDATA[music]]></MsgType>
									 <Music>
									 <Title><![CDATA[%s]]></Title>
									 <Description><![CDATA[%s]]></Description>
									 <MusicUrl><![CDATA[%s]]></MusicUrl>
									 <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
									 </Music>
									 </xml>";
					//Try to transmit text to voice and get the content.
					$fetchVoiceURL = new sFetchUrl();
					$pattern_removePrefix = '/[^!].*/';
					preg_match($pattern_removePrefix, $keyword, $afterRemeval);
					//过滤英语的!
					$afterRemeval = str_ireplace("！", "", $afterRemeval[0]);
					//过滤中文的！

					//$keyword = $afterRemeval[0];
					//$keyword = $afterRemeval;

					//$fetchVoiceURL -> get("http://stuffthatspins.com/stuff/php-TTS/index.php?heckle=" . urlencode($afterRemeval));
					//$voicePageContent = $fetchVoiceURL -> getResponseBody();
					$voicePageContent = $fetchVoiceURL -> get("http://stuffthatspins.com/stuff/php-TTS/index.php?heckle=" . urlencode($afterRemeval));

					$pattern_mp3_1 = '/MP3: http:.*mp3/';
					if (preg_match($pattern_mp3_1, $voicePageContent, $voiceResult)) {
						$voiceURL = $voiceResult[0];
						$HQVoice = $voiceResult[0];
						$pattern_mp3_2 = '/[^MP3:].*/';
						if (preg_match($pattern_mp3_2, $voiceURL, $voiceResult_2)) {
							$voiceTitle = "点击播放英语";
							$voiceURL = $voiceResult_2[0];
							$HQVoice = $voiceResult_2[0];
							if ($afterRemeval) {$voiceDes = $afterRemeval;
							} else {
								$voiceDes = $keyword;
							}
						}
					} else {
						$voiceTitle = "Click to read";
						$voiceDes = "Fail to translate.";
						$voiceURL = "http://stuffthatspins.com/stuff/php-TTS/files/1377515377.mp3";
						$HQVoice = "http://stuffthatspins.com/stuff/php-TTS/files/1377515377.mp3";
					}
					$resultStr = sprintf($voiceSection, $fromUsername, $toUsername, $time, $voiceTitle, $voiceDes, $voiceURL, $HQVoice);
					echo $resultStr;
				} else {
					//直接输入可以获得自动翻译。

					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$keyword.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $keyword;
					$jTrans_PostFix = "&from=auto&to=auto";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$autoTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

					// $pattern_transRes_1 = '/"dst":.*}/';
					// preg_match($pattern_transRes_1, $autoTrans_ResTemp, $autoTrans_Res_1);
					// $autoTrans_ResTemp1 = $autoTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":].*[^"}\]}]/';
					// preg_match($pattern_transRes_2, $autoTrans_ResTemp1, $autoTrans_Res_2);

					//为了修复一个奇怪的问题改了新的正则表达，2015/5/4
					// $pattern_transRes_1 = '/"dst":".*"/';
					// preg_match($pattern_transRes_1, $autoTrans_ResTemp, $autoTrans_Res_1);
					// $autoTrans_ResTemp1 = $autoTrans_Res_1[0];
					// $pattern_transRes_2 = '/[^"dst":"].*[^"]/';
					// preg_match($pattern_transRes_2, $autoTrans_ResTemp1, $autoTrans_Res_2);

					//2015/5/4 udpated RE into JSON parser
					$sJSON = json_decode($autoTrans_ResTemp);
					$contentStr = $sJSON->trans_result[0]->dst;


					if (stripos($autoTrans_ResTemp, '"to":"zh"')) {// 如果翻译成中文
						//此处相当的坑爹啊。网上搜了大半天才找到转码办法。现在是凌晨3点18分了。。。8/30/2013不过还是挺开心的。
						$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
					} else {//如果不是中文就不转，不然会慢
						$contentStr = $contentStr;
					}

					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}
			} else if ($msgType == 'image') {//处理图片信息
				$picURL = $postObj -> PicUrl;
				//@2015/11/25 因为微信平台屏蔽了外链，所以需要转到一些特殊站点上饶一圈儿
				$preQQUrl = "http://read.html5.qq.com/image?src=forum&q=5&r=0&imgflag=7&imageUrl=";
				//$preQQ_all = urlencode($preQQUrl.$picURL.".jpg");
				$preQQ_all = $preQQUrl.$picURL.".jpg";


				/*
				Updated Date:2017/3/20
				目的:用百度ai识别图中文字
				*/

				//初始化ocr的对象
				$aipOcr = new AipOcr(OCR_APP_ID, OCR_API_KEY, OCR_SECRET_KEY);
				$orc_option = array('detect_language' => "true");

				// 调用通用文字识别接口
				//等待写一个分支判定是否识别成功，如果是空就要返回提示语句
				//if (){

				//}
				$orc_result = $aipOcr->general(file_get_contents($preQQ_all), $orc_option);
				$str_orc = $orc_result['words_result'][0]['words']; //能用的一句
				//$str_orc =implode(" ",$orc_result); 

				$apiKey = BD_TRANS_APIKEY;
				$apiId = BAIDU_TRANS_APIID;
				$salt = 1314;
				$sign = md5($apiId.$str_orc.$salt.$apiKey);

				$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
				$jTrans_Q = "&q=" . $str_orc;
				$jTrans_PostFix = "&from=auto&to=auto";
				$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
				$jTrans_PostSign = "&sign=".$sign;
				$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
				$fetchjTransURL = new sFetchUrl();
				$autoTrans_ResTemp = $fetchjTransURL -> get($jTrans_URL);

				$sJSON = json_decode($autoTrans_ResTemp);
				$contentStr = $sJSON->trans_result[0]->dst;

				if (stripos($autoTrans_ResTemp, '"to":"zh"')) {// 如果翻译成中文
					//此处相当的坑爹啊。网上搜了大半天才找到转码办法。现在是凌晨3点18分了。。。8/30/2013不过还是挺开心的。
					$contentStr = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr);
				} else {//如果不是中文就不转，不然会慢
					$contentStr = $contentStr;
				}

				//返回orc的结果
				//拼接所有返回结果
				$contentStr1 = "您照片的文字识别结果是： " . $str_orc. "
				翻译结果是： ". $contentStr."." ;
				$msgType = 'text';
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr1);
				echo $resultStr;

				/*以下重新写过，利用百度AI自动识别图中的文字
				@2017/3/20	
				
				//转发给百度识图
				$preUrl = "http://stu.baidu.com/n/pc_search?rn=10&appid=0&tag=1&isMobile=0&queryImageUrl=";//http://stu.baidu.com/i?objurl=";
				$aftUrl = "&querySign=&fromProduct=&productBackUrl=&fm=&uptype=plug_in";//"&filename=&rt=0&rn=10&ftn=searchstu&ct=1&stt=1&tn=faceresult";
				$totalShiTuUrl = $preUrl.$preQQ_all.$aftUrl;//$preUrl . $picURL . ".jpg" . $aftUrl;

				//找到对应的百科地址，比较tricky的地方在于，因为很多页面元素是异步生成的，去拿的时候还没生成好，比如baike的title。所以抓到百科地址以后再从后台拿一次。
				$imgHTML = file_get_html($totalShiTuUrl);
				$imgHTML = file_get_html($totalShiTuUrl);//发第一次经常容易无法获得正确消息，试试多发几次。
				//2015/11/25 还有很多问题本来上面一句是file_get_html来获得内容然后用正则过滤的。结果因为内容也是动态生成的，直接用file_get_html并不能获得很好的效果

				//2015/11/25 百度页面改动导致需要重新更新正则
				$pattern_baike1 = '/data\-baike\-url=".*fr=aladdin"/';
				preg_match($pattern_baike1, $imgHTML, $imgBK_Res_1);
				$pattern_baike2 = '/[^data\-baike\-url="].*[^"]/';
				preg_match($pattern_baike2, $imgBK_Res_1[0], $imgBK_Res_2);
				$imgResultTitleURL = $imgBK_Res_2[0];

				//2015/11/25 页面改动后用正则三次滤出百科的题目
				$pattern_baike3 = '/guess\-info\-word\-link.+?<\/a>/';
				preg_match($pattern_baike3, $imgHTML, $imgBK_Res_3);
				$pattern_baike4 = '/>.*</';
				preg_match($pattern_baike4, $imgBK_Res_3[0], $imgBK_Res_4);
				$pattern_baike5 = '/[^>].*[^<]/';
				preg_match($pattern_baike5, $imgBK_Res_4[0], $imgBK_Res_5);
				$baikeTitle = $imgBK_Res_5[0];

				if ((!isset($imgResultTitleURL)) || (empty($imgResultTitleURL))) {
					$contentStr = "不好意思木有找到你上传的奇怪图片，原因可能是图像模糊或者特征不够明显。请拍摄一张更靠谱的吧！";
					$msgType = 'text';
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				} else {
					$imgResultTitle2 = $baikeTitle;

					//拼接所有返回结果
					$contentStr = "你要找的可能是：'" . $imgResultTitle2 . "' <a href='" . $imgResultTitleURL . "'>点此打开百科</a>";
					$msgType = 'text';
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}*/

			} else if ($msgType == 'voice') {
				$MediaId = $postObj -> MediaId;
				$Format = $postObj -> Format;
				$Recognition = $postObj -> Recognition;
				$contentStr_t = $Recognition;
				//get the original info

				//处理空值情况
				if ((!isset($contentStr_t)) || (empty($contentStr_t)) || (is_null($contentStr_t)) || ($contentStr_t == "")) {
					$contentStr = "用户老爷您说的语句未能被识别，请尽量清晰剪短的对着话筒重复您的短句。谢谢！";
					$msgType = "text";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}
				//处理非空情况
				else {
					// $apiKey = APIKEY;
					// $jTrans_clientAPI = "?client_id=" . $apiKey;
					// $jTrans_URL_Basic = "http://openapi.baidu.com/public/2.0/bmt/translate";
					// $jTrans_Q = "&q=" . $contentStr_t;

					// //to English
					// $jTrans_PostFix_en = "&from=zh&to=en";

					// $jTrans_URL_en = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix_en;
					// $fetchjTransURL_en = new sFetchUrl();
					// $jTrans_ResTemp_en = $fetchjTransURL_en -> get($jTrans_URL_en);

					//to english
					//2015/11/11, start using new baidu trans api:
					$apiKey = BD_TRANS_APIKEY;
					$apiId = BAIDU_TRANS_APIID;
					$salt = 1314;
					$sign = md5($apiId.$contentStr_t.$salt.$apiKey);

					$jTrans_URL_Basic = "http://api.fanyi.baidu.com/api/trans/vip/translate?";
					$jTrans_Q = "&q=" . $contentStr_t;
					$jTrans_PostFix = "&from=zh&to=en";
					$jTrans_PostDynamic = "&appid=".$apiId."&salt=".$salt;
					$jTrans_PostSign = "&sign=".$sign;
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix.$jTrans_PostSign;
					$fetchjTransURL = new sFetchUrl();
					$jTrans_ResTemp_en = $fetchjTransURL -> get($jTrans_URL);


					$sJSON = json_decode($jTrans_ResTemp_en);
					$contentStr_en = $sJSON->trans_result[0]->dst;
					$contentStr_en = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr_en);

					//to Japanese
					$jTrans_PostFix_jp = "&from=zh&to=jp";
					

					// $jTrans_URL_jp = $jTrans_URL_Basic . $jTrans_clientAPI . $jTrans_Q . $jTrans_PostFix_jp;
					// $fetchjTransURL_jp = new sFetchUrl();
					// $jTrans_ResTemp_jp = $fetchjTransURL_jp -> get($jTrans_URL_jp);
					$jTrans_URL = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix_jp.$jTrans_PostSign;
					$fetchjTransURL_jp = new sFetchUrl();
					$jTrans_ResTemp_jp = $fetchjTransURL_jp -> get($jTrans_URL);

					$sJSON = json_decode($jTrans_ResTemp_jp);
					$contentStr_jp = $sJSON->trans_result[0]->dst;
					$contentStr_jp = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr_jp);

					//2016/4/30 add russia
					$jTrans_PostFix_ru = "&from=zh&to=ru";
					//to russia 2016/4/30@lele's home
					$jTrans_URL_ru = $jTrans_URL_Basic.$jTrans_Q.$jTrans_PostDynamic.$jTrans_PostFix_ru.$jTrans_PostSign;
					$fetchjTransURL_ru = new sFetchUrl();
					$jTrans_ResTemp_ru = $fetchjTransURL_ru -> get($jTrans_URL_ru);

					$sJSON_ru = json_decode($jTrans_ResTemp_ru);
					$contentStr_ru = $sJSON_ru->trans_result[0]->dst;
					$contentStr_ru = preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $contentStr_ru);


					//Put Original/English/Japnese together
					$contentStr = "用户老爷您的原话是:  " . $contentStr_t . ".
					 英语翻译结果: " . $contentStr_en . "; 
					 日语翻译结果: " . $contentStr_jp.";
					 俄语翻译结果: " . $contentStr_ru.";";
					$msgType = "text";
					$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
					echo $resultStr;
				}
			}
		} else {
			echo "";
			exit ;
		}
	}

	private function checkSignature() {
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);

		if ($tmpStr == $signature) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $bcms_queue
	 * @param string $from
	 * @return boolean
	 * @tutorial BCMS邮件发送函数，最近BAE封掉了Bcms::FROM
	 * @example mail_bcms("hankcs@test.com", "主题", "正文", "消息队列名", "hankcs@baidu.com");
	 */
	public function mail_bcms($to, $subject, $message, $bcms_queue, $from = '') {
		require_once 'Bcms.class.php';
		$bcms = new Bcms();
		$ret = $bcms -> mail($bcms_queue, $message, array($to), array(Bcms::FROM => $from, Bcms::MAIL_SUBJECT => $subject));
		if (false === $ret) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * DB handler
	 * Get user name from DB by openID
	 */
	public function getUserNameFromDB($fromUser) {
		/*从平台获取查询要连接的数据库名称*/
		$dbname = 'HWbHgJjFkZlJqnbZOcfP';
		$sql = "SELECT * FROM users_info WHERE openID='" . $fromUser . "'";

		/*从环境变量里取出数据库连接需要的参数*/
		$host = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
		$port = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
		$user = getenv('HTTP_BAE_ENV_AK');
		$pwd = getenv('HTTP_BAE_ENV_SK');

		/*接着调用mysql_connect()连接服务器*/
		$link = @mysql_connect("{$host}:{$port}", $user, $pwd, true);
		if (!$link) {
			die("Connect Server Failed: " . mysql_error());
		}
		/*连接成功后立即调用mysql_select_db()选中需要连接的数据库*/
		if (!mysql_select_db($dbname, $link)) {
			die("Select Database Failed: " . mysql_error($link));
		}

		/*至此连接已完全建立，就可对当前数据库进行相应的操作了*/
		/*！！！注意，无法再通过本次连接调用mysql_select_db来切换到其它数据库了！！！*/
		/* 需要再连接其它数据库，请再使用mysql_connect+mysql_select_db启动另一个连接*/
		$ret = mysql_query($sql, $link);
		$row = mysql_fetch_row($ret);
		/*显式关闭连接，非必须*/
		mysql_close($link);
		if ($row[0])
			return $row[0];
		else {
			return false;
		}
	}

	/**
	 * DB handler
	 * Save openID to DB
	 */
	public function saveOpenID($fromUser, $createTime) {
		/*从平台获取查询要连接的数据库名称*/
		$dbname = 'HWbHgJjFkZlJqnbZOcfP';
		//$timeNow = time();
		//$transedTime = date(DATE_RFC822,strtotime($createTime));
		//$createTimeTransed = (string)$transedTime;
		$sql = "INSERT INTO  users_info (name ,gender ,reginal ,openID ,misc) VALUES (" . $createTime . ",  '',  '',  '" . $fromUser . "',  " . $createTime . ");";

		/*从环境变量里取出数据库连接需要的参数*/
		$host = getenv('HTTP_BAE_ENV_ADDR_SQL_IP');
		$port = getenv('HTTP_BAE_ENV_ADDR_SQL_PORT');
		$user = getenv('HTTP_BAE_ENV_AK');
		$pwd = getenv('HTTP_BAE_ENV_SK');

		/*接着调用mysql_connect()连接服务器*/
		$link = @mysql_connect("{$host}:{$port}", $user, $pwd, true);
		if (!$link) {
			die("Connect Server Failed: " . mysql_error());
		}
		/*连接成功后立即调用mysql_select_db()选中需要连接的数据库*/
		if (!mysql_select_db($dbname, $link)) {
			die("Select Database Failed: " . mysql_error($link));
		}

		/*至此连接已完全建立，就可对当前数据库进行相应的操作了*/
		/*！！！注意，无法再通过本次连接调用mysql_select_db来切换到其它数据库了！！！*/
		/* 需要再连接其它数据库，请再使用mysql_connect+mysql_select_db启动另一个连接*/
		$ret = mysql_query($sql, $link);
		/*显式关闭连接，非必须*/
		mysql_close($link);
		if ($ret === false) {
			return false;
		} else {
			return true;
		}
	}

}
?>
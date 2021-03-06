<?php
namespace Util\Controller;
use Think\Controller;
import("Org.Util.alisms.TopSdk");
/**
 * 短信工具
 * @author asus
 *
 */
class SmsController extends Controller {
	
	private $app_key="23701810";
	private $app_secret="332182535e33f53400a35976bc60649e";

	/**
	 * 发送注册验证短信
	 * @param unknown $code
	 * @param unknown $tel
	 */
	public function SendRegisterSms($code,$tel){

		$c = new \TopClient;
		$c->appkey = $this->app_key;
		$c->secretKey = $this->app_secret;
		$c->format='json';
		$req = new \AlibabaAliqinFcSmsNumSendRequest;
		$req->setExtend("123456");
		$req->setSmsType("normal");
		$req->setSmsFreeSignName("叮当书舍");
		$map['code']=$code;
		$map['product']="叮当书舍";
		$req->setSmsParam(json_encode($map));
		$req->setRecNum($tel);
		$req->setSmsTemplateCode("SMS_56105055");
		$resp = $c->execute($req);
		return $resp;
	}

   
   
}

?>

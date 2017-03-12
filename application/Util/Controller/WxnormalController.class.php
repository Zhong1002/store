<?php
namespace Util\Controller;
use Think\Controller;

/**
 * 网页支付接口
 * @author Jason
 *
 */
class WxnormalController extends Controller {

	/**
	 * 返回带有Code的URL
	 * @return string
	 */
	public function getRequestCodeURL() {
		$appid = 'wxc3cc0ce351b3bf86'; //AppID(应用ID)
		$appsecret = '5c6913157773e47eb3d9cab72b103f3d'; //appsecret
		$redirect_uri = 'http://www.ddbookstore.com/index.php?g=WeChat&m=User&a=index';
			
		/* 加载微信高级接口SDK */
		$wechatAuth  = new \WechatAuth($appid, $appsecret);
		$myURL = $wechatAuth->getRequestCodeURL($redirect_uri,'jason');
		return $myURL;
	}
}
<?php
namespace Util\Controller;
use Think\Controller;
/**
 * 网页支付接口
 * @author Jason
 *
 */
class WebpayController extends Controller {

	/**
	 * 获取支付信息
	 * @param unknown $order_id
	 */
	public function getCharge($order_id,$openid=''){
		$order=D('Order')->where('order_id='.$order_id)->find();
		
		if(!$order||($order['status']!=0&&$order['status']!=11)){
			$res['code']=2000;
			$res['message']="订单不存在或者订单状态不对";
			return $res;
		}
		
		import("Org.Pay.WxWeb.autoload");
		$wx_order=new \WxPayUnifiedOrder();
		$wx_order->SetOut_trade_no($order_id);
		$wx_order->SetBody("叮当书店-".$order_id);
		$wx_order->SetAttach("0");
		$wx_order->SetTotal_fee($order['total_price']*100);
		
		$wx_order->SetOpenid($openid);
		$wx_order->SetTrade_type("JSAPI");

		$notify_url='http://'.$_SERVER['HTTP_HOST'].'/index.php/api/payhook/wx_web_hook';
		$wx_order->SetNotify_url($notify_url);

		$result=\WxPayApi::unifiedOrder($wx_order);
		
		if($result['return_code']!='SUCCESS'){
			$res['code']=2000;
			$res['message']=$result;
			return $res;
		}
		if($result['result_code']!='SUCCESS'){
			$res['code']=2000;
			$res['message']="交易失败:错误代码为".$result['err_code'];
			return $res;
		}
		
		//成功返回值
		$pay_params['appId']=$result['appid'];
		$pay_params['nonceStr']=$result['nonce_str'];
		$pay_params['signType']="MD5";
		$pay_params['timeStamp']=time()."";
		$pay_params['package']="prepay_id=".$result['prepay_id'];
		
		$app_params=new \AppPayParams();
		$app_params->FromArray($pay_params);
		$app_params->SetSign();
		
		$sing_array=$app_params->getArray();
		$sing_array['paySign']=$sing_array['sign'];
		unset($sing_array['sign']);
		$res['code']=0;
		$res['charge']=json_encode($sing_array);
		
		return $res;
	}
}

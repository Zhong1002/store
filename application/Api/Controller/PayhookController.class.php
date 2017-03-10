<?php
namespace Api\Controller;
use Think\Controller;
use com\unionpay\acp\sdk;
use Qiniu\json_decode;
/**
 * 支付回调接口
 * @author Zhong
 *
 */
class PayhookController extends Controller {

	public function wx_hook(){
		import("Org.Pay.WxApp.autoload");
		$wx_notify=new \Util\Controller\WxNotify();
		$wx_notify->Handle(true);
	}
	
	public function wx_web_hook(){
		import("Org.Pay.WxWeb.autoload");
		$wx_notify=new \Util\Controller\WxWebNotify();
		$wx_notify->Handle(true);
	}
	
	
	public function ali_hook(){
		import("Org.Pay.Ali.autoload");
		$aop_client=new \AopClient();
		//$body = @file_get_contents('php://input');
		//$params=json_decode($body,true);
		$params=I('param.');
		$params['fund_bill_list']=html_entity_decode($params['fund_bill_list']);
		//echo $params['fund_bill_list'];
		\Think\Log::write( $params['out_trade_no'],'ERR','File',C('LOG_PATH').'alipaylog.log');
		$var=$aop_client->rsaCheckV1($params);
		//var_dump($var);
		if(!$var){
			\Think\Log::write( json_encode($params)."sign error",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		\Think\Log::write( json_encode($params)."sign success",'ERR','File',C('LOG_PATH').'alipaylog.log');
		if($params['trade_status']!='TRADE_SUCCESS'){
			\Think\Log::write( $params['out_trade_no']."status error",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		$subject=split('-', $params['subject']);
		if('听说'==$subject[0]){
			$this->ali_handle_order($params);
		}
		elseif ('听说打赏'==$subject[0]){
			$this->ali_handle_reward($params);
		}
		
	}
	
	/**
	 * 支付宝处理订单回调
	 * @param unknown $params
	 */
	private function  ali_handle_order($params){
		$order=D('Order')->where('order_id='.$params['out_trade_no'])->find();
		if(!$order){
			\Think\Log::write(  $params['out_trade_no']."order_not_exist",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		if($order['status']!=0){
			\Think\Log::write(  $params['out_trade_no']."order_status_error",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		
		$charge['id']=0;
		$charge['paid']=true;
		$charge['channel']='alipay';
		$charge['order_no']=$order['order_id'];
		$charge['amount']=$params['total_amount']*100;
		$charge['subject']=$order['order_id'];
		$charge['body']='听说-'.$order['order_id'];
		$charge['time_paid']=strtotime($params['gmt_payment']);
		$charge['transaction_no']=$params['trade_no'];
			
		if(!$order['charge']){
			$order['status']=7;
			$order['charge']=json_encode($charge);
			if(false!==D('Order')->save($order)){
				//增加提成记录
				$order_p=A('Common/OrderProgress');
				$order_p->setOrderBack($order['order_id']);
				$temp['id']=0;
				$temp['order_id']=$order['order_id'];
				$temp['created']=$charge['time_paid'];
				$temp['livemode']=1;
				$temp['paid']=$charge['paid']?1:0;
				$temp['channel']=$charge['channel'];
				$temp['order_no']=$charge['order_no'];
				$temp['client_ip']=0;
				$temp['amount']=$charge['amount'];
				$temp['currency']='cny';
				$temp['time_paid']=$charge['time_paid'];
				$temp['time_expire']=0;
				$temp['transaction_no']=$charge['transaction_no'];
				D('OrderCharge')->add($temp);
				//$this->log($charge['body']."更改订单状态成功,退出");
				//$this->sendCode(200);
			}
			else{
				exit('error');
			}
				
		
		}
		exit('success');
	}
	
	private function ali_handle_reward($params){
		$order=D('NoteReward')->where('id='.$params['out_trade_no'])->find();
		if(!$order){
			\Think\Log::write(  $params['out_trade_no']."reward_order_not_exist",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		if($order['status']!=0){
			\Think\Log::write(  $params['out_trade_no']."reward_order_status_error",'ERR','File',C('LOG_PATH').'alipaylog.log');
			exit('success');
		}
		
		$charge['id']=0;
		$charge['paid']=true;
		$charge['channel']='alipay';
		$charge['order_no']=$order['id'];
		$charge['amount']=$params['total_amount'];
		$charge['subject']=$order['id'];
		$charge['body']='听说打赏-'.$order['id'];
		$charge['time_paid']=strtotime($params['gmt_payment']);
		$charge['transaction_no']=$params['trade_no'];
			
		if(!$order['charge']){
			$order['status']=1;
			$order['charge']=json_encode($charge);
			if(false===D('NoteReward')->save($order)){
				exit('error');
			}
		
		
		}
		exit('success');
	}
	
	
	public function union_hook(){
		import("Org.Pay.Union.autoload");
		$params=I('param.');
 		\Think\Log::write( json_encode($params),'ERR','File',C('LOG_PATH').'unionpaylog.log');
		if(\com\unionpay\acp\sdk\AcpService::validate ( $params )){
			\Think\Log::write( $params['orderId']."sign success",'ERR','File',C('LOG_PATH').'unionpaylog.log');
			if($params['respCode']!='00'&&$params['respCode']!='A6'){
				\Think\Log::write(  $params['orderId']."status_error",'ERR','File',C('LOG_PATH').'unionpaylog.log');
				exit('success');
			}
			
			$order=D('Order')->where('order_id='.substr($params['orderId'], 8))->find();
			if(!$order){
				\Think\Log::write(  $params['orderId']."order_not_exist",'ERR','File',C('LOG_PATH').'unionpaylog.log');
				exit('success');
			}
			if($order['status']!=0&&$order['status']!=11){
				\Think\Log::write(  $params['orderId']."order_status_error",'ERR','File',C('LOG_PATH').'unionpaylog.log');
				exit('success');
			}
			
			$charge['id']=0;
			$charge['paid']=true;
			$charge['channel']='upacp';
			$charge['order_no']=$order['order_id'];
			$charge['amount']=$params['txnAmt'];
			$charge['subject']=$order['order_id'];
			$charge['time_paid']=strtotime($params['txnTime']);
			$charge['transaction_no']=$params['queryId'];
			
			$order_handle=new \Common\Controller\OrderHandle();
			$pre_no=substr($params['orderId'],0, 8);
			if($pre_no=='00000000'){
				$order_handle->handle_common_order($order,$charge);
			}
			elseif ($pre_no=='00000001'){
				$order_handle->handle_prepaid_order($order,$charge);
			}
			elseif ($pre_no=='00000002'){
				$order_handle->handle_lastpaid_order($order,$charge);
			}
			elseif ($pre_no=='00000003'){
				 $order_handle->handle_group_order($order,$charge);
			}
		}
		else{
			\Think\Log::write(  $params['orderId']."sign_error",'ERR','File',C('LOG_PATH').'unionpaylog.log');
		}
		exit('success');
	}
	

	
}

?>

<?php
namespace Common\Controller;

/**
 * 订单处理支付回调
 * @author Zhong
 *
 */
class OrderHandle{

	/**
	 * 处理普通订单支付
	 * @param unknown $data
	 * @return boolean
	 */
	public function handle_common_order($order,$charge){
	
		$charge['body']='听说-'.$charge['subject'];
	
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
				return false;
			}
	
	
		}
		return true;
	}
	
	/**
	 * 处理预付订单支付
	 * @param unknown $data
	 * @return boolean
	 */
	public function handle_prepaid_order($order,$charge){
	
		$charge['body']='听说预付款-'.$charge['subject'];
	
		if(!$order['prepaid_charge']){
			$order['status']=11;
			$order['prepaid_charge']=json_encode($charge);
			if(false===D('Order')->save($order)){
				return false;
			}
	
	
		}
		return true;
	}
	
	/**
	 * 处理尾付订单支付
	 * @param unknown $data
	 * @return boolean
	 */
	public function handle_lastpaid_order($order,$charge){
		$charge['body']='听说尾款-'.$charge['subject'];
	
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
				return false;
			}
	
	
		}
		return true;
	}
	
	/**
	 * 处理拼团订单支付
	 * @param unknown $data
	 * @return boolean
	 */
	public function handle_group_order($order,$charge){
	
		$charge['body']='听说拼团-'.$charge['subject'];
	
		$order_detail=D('OrderDetail')->where('order_id='.$order['order_id'])->find();
		$gbg_temp['order_id']=$order['order_id'];
		$gbg_temp['member_id']=$order['member_id'];
		$gbg_temp['activity_id']=$order['prepaid_activity'];
		$gbg_temp['gg_id']=$order_detail['aim_id'];
		$gbg_temp['gg_num']=$order_detail['num'];
		$gbg_temp['parent_id']=$order['type_aim_id'];
		$gbg_temp['status']=-2;
		$gbg_temp['create_time']=time();
		$res1=D('GroupbuyGroup')->add($gbg_temp);
		if(!$order['charge']){
			$order['status']=13;
			$order['charge']=json_encode($charge);
			$res2=D('Order')->save($order);
				
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
				
		}
	
		if(!$res1||!$res2){
			return false;
		}
	
		if($order['type_aim_id']>0){
			$gbgmap['gbg_id|parent_id']=$order['type_aim_id'];
			$groupbuy_list=D('GroupbuyGroup')->where($gbgmap)->order('gbg_id asc')->select();
			if($groupbuy_list[0]['status']>1){
				D('GroupbuyGroup')->where($gbgmap)->setField('status',$groupbuy_list[0]['status']-1);
			}
			elseif($groupbuy_list[0]['status']==-1){
				D('GroupbuyGroup')->where($gbgmap)->setField('status',-1);
				$order['status']=14;
				D('Order')->save($order);
			}
			elseif($groupbuy_list[0]['status']==1){
				D('GroupbuyGroup')->where($gbgmap)->setField('status',0);
				$order_p=A('Common/OrderProgress');
				foreach ($groupbuy_list as $g){
					D('Order')->where('order_id='.$g['order_id'])->setField('status',7);
					$order_p->setOrderBack($g['order_id']);
				}
			}
			elseif($groupbuy_list[0]['status']==0){
				D('GroupbuyGroup')->where($gbgmap)->setField('status',0);
				$order_p=A('Common/OrderProgress');
				$order_p->setOrderBack($order['order_id']);
			}
	
		}
		else{
			$gg=D('GroupbuyGoods')->where('gg_id='.$order_detail['aim_id'])->find();
			D('GroupbuyGroup')->where('gbg_id='.$res1)->setField('status',$gg['join_num']-1);
		}
		return true;
	}

}

?>

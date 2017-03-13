<?php
namespace WeChat\Controller;
use Common\Controller\HomebaseController;
/**
 * 微信支付
 */
class WeixinpayController extends HomebaseController{

    /**
     * notify_url接收页面
     */
    public function notify(){
    	$order_model = M('Order');
    	$order_model->add(array('order_sn'=>111,'create_time'=>time()));
        // 导入微信支付sdk
        vendor('WxPay.Weixinpay');
        $wxpay=new \Weixinpay();
        $result=$wxpay->notify();
        if ($result) {
            // 验证成功 修改数据库的订单状态等 $result['out_trade_no']为订单号
            $order_rst = $order_model->where(array('order_id'=>$result['out_trade_no']))->setField('status',2);
            if($order_rst === false) $this->error($order_model->getError());
        }
    }

    /**
     * 公众号支付 必须以get形式传递 out_trade_no 参数
     * 示例请看 /Application/Home/Controller/IndexController.class.php
     * 中的weixinpay_js方法
     */
    public function pay(){
        // 导入微信支付sdk
        vendor('WxPay.Weixinpay');
        $wxpay=new \Weixinpay();
        // 获取jssdk需要用到的数据
        $data=$wxpay->getParameters();
        // 将数据分配到前台页面
        $assign=array(
            'data'=>json_encode($data['data']),
        	'order_id'=>$data['order_id']
        );
        $this->assign($assign);
        $this->display();
    }

}
<?php
namespace WeChat\Controller;
use Common\Controller\HomebaseController;

vendor('WeChat.WechatAuth#class');

class UserController extends HomebaseController{
	
	protected $users_model;
	protected $config;
	
	public function _initialize() {
		parent::_initialize();
		$this->users_model = M('Member');
	}
	
	 public function index(){
	 	 $usersThird = M('MemberThird');
 	  	 $userURL = 'http://www.ddbookstore.com/index.php?g=&m=Member&a=index';
		 $this->config = C('WECHAT_CONFIG');
 	  	 
         /* 加载微信高级接口SDK */
         $wechatAuth = new \WechatAuth($this->config['APPID'], $this->config['APPSECRET']);
         /*通过code换取网页授权access_token*/
         $content = $wechatAuth->getAccessToken('code',$_GET['code']);
         /*拉取用户信息(需scope为 snsapi_userinfo)*/
         $userInfo = $wechatAuth->getUserInfo($content['openid']);
         
         if(isset($userInfo['errcode'])){
         	throw new \Exception($userInfo['errmsg']);
         }else {
         	//查询是否已经注册
         	$isuser = $usersThird->where(array('uuid'=>$userInfo['openid']))->getField('member_id');
         	if(!empty($isuser)) {
         		$result = $this->users_model->where(array('member_id'=>$isuser))->find();
         		//已经注册的话直接登录，将member数据以session形式存放
         		$result['id'] = $result['member_id'];
         		session('user',$result);
         		redirect($userURL,'1','您已注册，正在为您登陆...');
         	}else {
         		$data1 = array(
         				'nick_name'   => $userInfo['nickname'],
         				'sex'         => $userInfo['sex'],
         				'avatar'      => $userInfo['headimgurl'],
         				'create_time' => time(),
         		);
         		$rst1 = $this->users_model->add($data1);
         		if($rst1) {
         			$data1['id']=$rst1;
         			session('user',$data1);
         		
         			$data2 = array(
         				'member_id'   => $rst1,
         				'type'        => 1,
         				'uuid'		  => $userInfo['openid'],
         				'create_time' => time(),
         			);
         			$rst2 = $usersThird->add($data2);
         			if ($rst2) {
         				$this->success("仅仅获取了您的头像和昵称，不用担心",$userURL);
         			}else {
         				$this->error('注册出错');
         			}
         		}else {
         			$this->error('注册出错');
         		}
         	}
         }
     }
}
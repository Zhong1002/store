<?php
namespace Book\Model;
use Think\Model\ViewModel;

class MemberCartViewModel extends ViewModel{
	public $viewFields = array(
		'MemberCart' => array('goods_id','member_id','num','create_time'),
		'Goods' => array('name','pre_price','now_price','cover','_on'=>'Goods.goods_id=MemberCart.goods_id'),
	);
}
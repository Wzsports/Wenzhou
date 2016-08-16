<?php

use \LeanCloud\Engine\Cloud;
use \LeanCloud\Query;
use \LeanCloud\Object;
use \LeanCloud\CloudException;

/*
 * Define cloud functions and hooks on LeanCloud
 */

/**
 * 评论数量自动修改
 */
function changeComment($obj){
    $gymId = $obj->get('gymId');

    $query = new Query("GymComment");
    $query->equalTo('gymId', $gymId);
    $total = $query->count();
    $obj = $query->find();
    $star = 0;
    foreach ($obj as $o) {
        $star += $o->get('star');
    }
    $objSave = new Object('Gym', $gymId);
    $objSave->set('comment', intval($total));
    $objSave->set('score', $star/$total);
    try {
        $objSave->save();
    } catch (CloudException $ex) {
        throw new FunctionError("计算评论数量失败" . $ex->getMessage());
    }
}


Cloud::afterSave("GymComment", function($obj, $user, $meta) {
    changeComment($obj);
    return ;
});

Cloud::afterDelete("GymComment", function($obj, $user, $meta) {
    changeComment($obj);
    return ;
});

/**
 * 测试云函数
 */
Cloud::define("test", function($params, $user) {
    error_log('添加测试日志');
});

/**
 * 预定状态重置
 */

Cloud::define("resetStatus", function($params, $user) {
    $query = new Query('GymSports');
    $query->equalTo('orderType', '2');
    $objSports = $query->find();
    // 统计场地数量
    $courtNumber = array();
    foreach ($objSports as $sports) {
        $courtNumber[$sports->getObjectId()] = $sports->get('courtNumber') ? $sports->get('courtNumber') : 1;
    }

    $query2 = new Query('Price');
    $query2->equalTo('orderType', '2');
    $objPrice = $query2->find();

    $count = 0;
    foreach ($objPrice as $price) {
        $objSave = new Object("Price", $price->getObjectId());
        $number = $courtNumber[$price->get('sportsId')] ? $courtNumber[$price->get('sportsId')] : 1;
        $status_array = array();
        for ($i=1; $i <= $court_number; $i++) { 
            $status_array[] = array($i=>0);
        }
        $objSave->set("courtStatus", $status_array);
        $count++;
    }
    error_log('共修改price记录'.$count.'条');
});
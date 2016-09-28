<?php

use \LeanCloud\Engine\Cloud;
use \LeanCloud\Engine\FunctionError;
use \LeanCloud\Query;
use \LeanCloud\Object;
use \LeanCloud\CloudException;
use \LeanCloud\Client;


Client::useMasterKey(true);


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
 * 提取取票密码
 */

Cloud::afterSave("Booking", function($obj, $user, $meta) {
    error_log('testBookingafterSave');
    // ticketPass
    $query = new Query("Booking");
    $query->equalTo('ticketPass', null);
    $objs = $query->find();
    error_log('共有'.strval(count($objs)).'个需要修改');
    foreach ($objs as $key => $value) {
        $objSave = new Object('Booking', $value->getObjectId());
        $objSave->set('ticketPass', substr($value->getObjectId(), -10));
        try {
            $objSave->save();
            error_log('取票密码update成功');
        } catch (CloudException $ex) {
            error_log('取票密码update失败'.$ex->getMessage());
            throw new FunctionError("取票密码update失败" . $ex->getMessage());
        }
    }
    // ticketPass 
    $obj->disableAfterHook();
    return ;
});
Cloud::afterDelete("Booking", function($obj, $user, $meta) {
    error_log('testBookingafterDelete');
    // ticketPass
    $query = new Query("Booking");
    $query->equalTo('ticketPass', null);
    $objs = $query->find();
    error_log('共有'.strval(count($objs)).'个需要修改');
    foreach ($objs as $key => $value) {
        $objSave = new Object('Booking', $value->getObjectId());
        $objSave->set('ticketPass', substr($value->getObjectId(), -10));
        try {
            $objSave->save();
            error_log('取票密码update成功');
        } catch (CloudException $ex) {
            error_log('取票密码update失败'.$ex->getMessage());
            throw new FunctionError("取票密码update失败" . $ex->getMessage());
        }
    }
    // ticketPass 
    $obj->disableAfterHook();
    return ;
});

/**
 * 报名人数自动修改
 */
function changeSign($obj){
    $eventPointer = $obj->get('Event');
    $eventId = $eventPointer->getObjectId();
    error_log($eventId);

    $query = new Query("EventSignUp");
    $query->equalTo('Event', $eventPointer);
    $query->equalTo('payStatus', true);
    $query->notEqualTo('cancelSignStatus', true);
    $total = $query->count();
    error_log($total);

    $query2 = new Query('Event');
    $query2->equalTo('objectId', $eventId);
    $obj2 = $query2->find();
    if ($obj2[0]) {
        $maxPeople = $obj2[0]->get('maxPeople');
        $objSave = new Object('Event', $eventId);
        $objSave->set('remainPeople', strval($maxPeople-$total));
        error_log(strval($maxPeople-$total));
        try {
            $objSave->save();
        } catch (CloudException $ex) {
            throw new FunctionError("计算报名人数失败" . $ex->getMessage());
        }
    } else {
        error_log('没有这个赛事');
    }
}

Cloud::afterSave("EventSignUp", function($obj, $user, $meta) {
    error_log('test');
    changeSign($obj);
    return ;
});

Cloud::afterDelete("EventSignUp", function($obj, $user, $meta) {
    error_log('test');
    changeSign($obj);
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
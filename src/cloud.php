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
 * 人气自动更新
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
    // 人气自动更新
    // 查询之前的人气值
    $gym = $obj->get('gym');
    $query = new Query("Gym");
    $query->equalTo('objectId', $gym->getObjectId());
    $obj_gym = $query->find();
    if (isset($obj_gym[0])) {
        $popularity_set = $obj_gym[0]->get('popularitySet');
        $popularity_old = $obj_gym[0]->get('popularity');
        $popularity_set = $popularity_set ? $popularity_set : 0;
        $popularity_old = $popularity_old ? $popularity_old : 0;
        $popularity = $popularity_set > $popularity_old ? $popularity_set : $popularity_old;

        $ticketArray = $obj->get('ticketArray');
        $popularity_plus = 0;
        foreach ($ticketArray as $key => $value) {
            // 小时数
            if (isset($value['startTime']) && isset($value['endTime'])) {
                $start_time = $value['startTime'];
                $end_time = $value['endTime'];
                $start_time = intval(str_replace(':', '', $start_time));
                $end_time = intval(str_replace(':', '', $end_time));
                $hour_diff = round(($end_time-$start_time)/100);
            } else {
                $hour_diff = 1;
            }
            // 票数
            if (isset($value['count'])) {
                $count = $value['count'] ? $value['count'] : 1;
            } else {
                $count = 1;
            }
            
            // 场地类
            if (isset($value['orderStatusId'])) {
                if (isset($value['startDate']) && isset($value['endDate'])) {
                    $start_date = $value['startDate'];
                    $end_date = $value['endDate'];
                    $start_date = str_replace('年', '-', $start_date);
                    $start_date = str_replace('月', '-', $start_date);
                    $start_date = str_replace('日', '', $start_date);
                    $end_date = str_replace('年', '-', $end_date);
                    $end_date = str_replace('月', '-', $end_date);
                    $end_date = str_replace('日', '', $end_date);
                    $day_time_diff = intval(strtotime($end_date)) - intval(strtotime($start_date));
                    $day_diff = ceil($day_time_diff/7/86400);
                } else {
                    $day_diff = 1;
                }

                $popularity_plus += $hour_diff*$day_diff*$count;
            } else {
                // 门票类
                $popularity_plus += $hour_diff*$count;
            }
        }
        $obj_gym[0]->set('popularity', $popularity+$popularity_plus);
        $obj_gym[0]->save();
        error_log('人气自动更新成功');
    } else {
        error_log('人气自动更新失败，未查询到场馆');
    }
    
    
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
 * 修改报名
 */
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

/**
 * 报名人数自动修改
 */
function changeSign($obj){
    $current_users = $obj->get('userContacts');
    $eventPointer = $obj->get('Event');
    $eventId = $eventPointer->getObjectId();
    error_log($eventId);

    $query = new Query("EventSignUp");
    $query->equalTo('Event', $eventPointer);
    $query->equalTo('payStatus', 1);
    $query->notEqualTo('cancelSignStatus', 1);
    $total = 0;
    // if ($obj->get('payStatus') == 1 && $obj->get('cancelSignStatus') != 1) {
    //     $total += count($current_users);
    // }
    
    $obj_signup = $query->find();
    foreach ($obj_signup as $value) {
        $leader = $value->get('eventLeaderArray');
        // 有领队
        if (isset($leader[0])) {
            $total += 1;
        }
        $user_contact = $value->get('userContacts');
        $total += count($user_contact);
    }
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

/**
 * 云函数
 */
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
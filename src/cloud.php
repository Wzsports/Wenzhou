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
Cloud::afterSave("GymComment", function($obj, $user, $meta) {
    $gymId = $obj->get('gymId');

    $query = new Query("GymComment");
    $query->equalTo('gymId', $gymId);
    $total = $query->count();

    $objSave = new Object('Gym', $gymId);
    $objSave->set('comment', array($total));
    try {
        $objSave->save();
    } catch (CloudException $ex) {
        throw new FunctionError("计算评论数量失败" . $ex->getMessage());
    }
    return ;
});


Cloud::afterDelete("GymComment", function($obj, $user, $meta) {
    $gymId = $obj->get('gymId');

    $query = new Query("GymComment");
    $query->equalTo('gymId', $gymId);
    $total = $query->count();

    $objSave = new Object('Gym', $gymId);
    $objSave->set('comment', array($total));
    try {
        $objSave->save();
    } catch (CloudException $ex) {
        throw new FunctionError("计算评论数量失败" . $ex->getMessage());
    }
    return ;
});
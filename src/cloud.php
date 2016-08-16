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
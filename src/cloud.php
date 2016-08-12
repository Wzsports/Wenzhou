<?php

use \LeanCloud\Engine\Cloud;
use \LeanCloud\LeanQuery;
use \LeanCloud\LeanObject;
use \LeanCloud\CloudException;

try {
    $query = new LeanQuery("GymComment");
} catch (CloudException $ex) {
    throw new FunctionError('1');
}
try {
    $query = new \LeanQuery("GymComment");
} catch (CloudException $ex) {
    throw new FunctionError('2');
}
try {
    $query = new LeanCloud\LeanQuery("GymComment");
} catch (CloudException $ex) {
    throw new FunctionError('3');
}
try {
    $query = new \LeanCloud\LeanQuery("GymComment");
} catch (CloudException $ex) {
    throw new FunctionError('4');
}

/*
 * Define cloud functions and hooks on LeanCloud
 */

// /1.1/functions/sayHello
Cloud::define("sayHello", function($params, $user) {
    return "hello {$params['name']}";
});

// /1.1/functions/sieveOfPrimes
Cloud::define("sieveOfPrimes", function($params, $user) {
    $n = isset($params["n"]) ? $params["n"] : 1000;
    error_log("Find prime numbers less than {$n}");
    $primeMarks = array();
    for ($i = 0; $i <= $n; $i++) {
        $primeMarks[$i] = true;
    }
    $primeMarks[0] = false;
    $primeMarks[1] = false;

    $x = round(sqrt($n));
    for ($i = 2; $i <= $x; $i++) {
        if ($primeMarks[$i]) {
            for ($j = $i * $i; $j <= $n;  $j = $j + $i) {
                $primeMarks[$j] = false;
            }
        }
    }

    $numbers = array();
    forEach($primeMarks as $i => $mark) {
        if ($mark) {
            $numbers[] = $i;
        }
    }
    return $numbers;
});

Cloud::afterSave("GymComment", function($obj, $user, $meta) {
    // function can accepts optional 3rd argument $meta, which for example
    // has "remoteAddress" of client.
    $gymId = $obj->get('gymId');

    $query = new LeanQuery("GymComment");
    $query->equalTo('gymId', $gymId);
    $total = $query->count();

    $objSave = new LeanObject('Gym', $gymId);
    $objSave->set('comment', array($total));
    try {
        $objSave->save();
    } catch (CloudException $ex) {
        throw new FunctionError("计算评论数量失败" . $ex->getMessage());
    }
    return ;
});

/*

Cloud::onLogin(function($user) {
    // reject blocker user for login
    if ($user->get("isBlocked")) {
        throw new FunctionError("User is blocked!", 123);
    }
});

Cloud::onInsight(function($params) {
    return;
});

Cloud::onVerified("sms", function($user){
    return;
});

Cloud::beforeSave("TestObject", function($obj, $user) {
    return $obj;
});

Cloud::beforeUpdate("TestObject", function($obj, $user) {
    // $obj->updatedKeys is an array of keys that is changed in the request
    return $obj;
});

Cloud::afterSave("TestObject", function($obj, $user, $meta) {
    // function can accepts optional 3rd argument $meta, which for example
    // has "remoteAddress" of client.
    return ;
});

*/

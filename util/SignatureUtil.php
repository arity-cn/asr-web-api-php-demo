<?php
function generate_signature($accessKey, $accessKeySecret, $appCode) {
    $timestamp = round(microtime(true) * 1000);

    // 将签名参数按照字典序排序
    $params = array(
        'accessKey' => $accessKey,
        'accessKeySecret' => $accessKeySecret,
        'appCode' => $appCode,
        'timestamp' => $timestamp
    );
    ksort($params);

    // 拼接排序后的参数
    $sign_str = '';
    foreach ($params as $key => $value) {
        $sign_str .= $key . $value;
    }

    // 计算签名
    $md5_str = md5($sign_str);
    $sign = strtoupper($md5_str);

    return array(
        'signature' => $sign,
        'timestamp' => $timestamp
    );
}
?>
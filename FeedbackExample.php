<?php
require_once 'util/SignatureUtil.php';

// 语音识别反馈 webapi 示例代码
$url = "https://k8s.arity.cn/asr/http/asr/V1/feedback";
// 对应语音识别时传的的btId
$btId = "btId(请替换为正确的btId)";
// 对应语音识别返回的requestId
$requestId = "requestId(请替换为正确的requestId)";
// 是否识别准确 0: 准确 1: 不准确
$exactType = 1;
$accessKey = "accessKey(请替换为正确的accessKey)";
$accessKeySecret = "accessKey(请替换为正确的accessKeySecret)";
$appCode = "appCode(请替换为正确的appCode)";
$channelCode = "channelCode(请替换为正确的channelCode)";

$headers = array('Content-Type: application/json');
// 生成签名
$signResult = generate_signature($accessKey, $accessKeySecret, $appCode);
// 构建请求参数
$data = array(
    "btId" => $btId,
    "requestId" => $requestId,
    "accessKey" => $accessKey,
    "appCode" => $appCode,
    "channelCode" => $channelCode,
    "timestamp" => $signResult['timestamp'],
    "sign" => $signResult['signature'],
    "exactType" => $exactType
);
// 发送http请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
// 解析结果
if ($httpCode == 200) {
    $response_data = json_decode($response, true);
    if ($response_data['success']) {
        echo "反馈成功";
    } else {
        echo "反馈异常: " . $response . "\n";
    }
} else {
    echo "请求异常, httpCode: " . $httpCode . "\n";
}

?>
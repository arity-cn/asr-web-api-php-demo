<?php
require_once 'util/SignatureUtil.php';

// http form-data webapi 示例代码
$url = "https://k8s.arity.cn/asr/http/asr/toTextBinary";
// 业务方唯一标识id，最高128位，建议不要重复，这里只是模拟
$btId = "123";
$accessKey = "accessKey(请替换为正确的accessKey)";
$accessKeySecret = "accessKey(请替换为正确的accessKeySecret)";
$appCode = "appCode(请替换为正确的appCode)";
$channelCode = "channelCode(请替换为正确的channelCode)";

$headers = array();
// 生成签名
$signResult = generate_signature($accessKey, $accessKeySecret, $appCode);
// 构建请求参数
$data = array(
    "btId" => $btId,
    "accessKey" => $accessKey,
    "appCode" => $appCode,
    "channelCode" => $channelCode,
    "timestamp" => $signResult['timestamp'],
    "sign" => $signResult['signature'],
    "sampleRateEnum" => "SAMPLE_RATE_16K"
);
$files = array(
    "file" => array("BAC009S0002W0164.wav", fopen("audio/BAC009S0002W0164.wav", "rb"), 'application/octet-stream')
);

// 发送http请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_POSTFIELDS, $files);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 解析结果
if ($httpCode == 200) {
    $response_data = json_decode($response, true);
    if ($response_data["success"]) {
        echo "语音识别结果: " . $response_data["data"]["audioText"];
    } else {
        echo "请求异常: " . $response_data;
    }
} else {
    echo "请求异常, httpCode: " . $httpCode;
}

?>
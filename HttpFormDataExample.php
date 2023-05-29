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

$headers = array('Content-Type: multipart/form-data');
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

// 文件字段名
$file_field = 'file';
// 文件路径
$file_path = 'audio/BAC009S0002W0164.wav';

// 发送http请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 构建 multipart/form-data
$boundary = uniqid();
$body = '';

// 添加参数字段
foreach ($data as $key => $value) {
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n";
    $body .= "\r\n";
    $body .= "{$value}\r\n";
}

// 添加文件字段
$body .= "--{$boundary}\r\n";
$body .= "Content-Disposition: form-data; name=\"{$file_field}\"; filename=\"" . basename($file_path) . "\"\r\n";
$body .= "Content-Type:application/octet-stream\r\n";
$body .= "\r\n";
$body .= file_get_contents($file_path) . "\r\n";
$body .= "--{$boundary}--\r\n";

$header = "Content-Type: multipart/form-data; boundary={$boundary}\r\n";
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: multipart/form-data; boundary={$boundary}",
    "Content-Length: " . strlen($body)
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 解析结果
if ($httpCode == 200) {
    $response_data = json_decode($response, true);
    if ($response_data["success"]) {
        echo "语音识别结果: " . $response_data["data"]["audioText"];
    } else {
        echo "请求异常: " . $response;
    }
} else {
    echo "请求异常, httpCode: " . $httpCode;
}

?>
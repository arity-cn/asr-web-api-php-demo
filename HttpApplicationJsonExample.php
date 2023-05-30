<?php
require_once 'util/SignatureUtil.php';

// http application/json webapi 示例代码

$url = "https://k8s.arity.cn/asr/http/asr/toText";
// 业务方唯一标识id，最高128位，建议不要重复，这里只是模拟
$btId = "123";
$accessKey = "accessKey(请替换为正确的accessKey)";
$accessKeySecret = "accessKey(请替换为正确的accessKeySecret)";
$appCode = "appCode(请替换为正确的appCode)";
$channelCode = "channelCode(请替换为正确的channelCode)";
// http头部
$headers = array('Content-Type: application/json');
// 生成签名
$signResult = generate_signature($accessKey, $accessKeySecret, $appCode);
// 读取文件并转为base64
$fileContent = file_get_contents('audio/ARITY2023S001W0001.wav');
$base64Content = base64_encode($fileContent);
// 构建请求参数
$data = array(
    'btId' => $btId,
    'accessKey' => $accessKey,
    'appCode' => $appCode,
    'channelCode' => $channelCode,
    'contentType' => 'RAW',
    'formatInfo' => 'WAV',
    'content' => $base64Content,
    'timestamp' => $signResult['timestamp'],
    'sign' => $signResult['signature']
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
        echo "语音识别结果: " . $response_data['data']['audioText'] . "\n";
    } else {
        echo "请求异常: " . $response . "\n";
    }
} else {
    echo "请求异常, httpCode: " . $httpCode . "\n";
}

?>
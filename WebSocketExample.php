<?php

require_once 'util/SignatureUtil.php';
require 'vendor/autoload.php';

use WebSocket\Client;

// 语音识别结果
$result = '';
// websocket连接
$ws = null;

// 构建请求参数，获取携带参数的ws url
function get_websocket_url($websocket_url, $bt_id, $access_key, $access_key_secret, $app_code, $channel_code) {
    $params = [
        "accessKey" => $access_key,
        "appCode" => $app_code,
        "channelCode" => $channel_code,
        "btId" => $bt_id
    ];

    $sign_result = generate_signature($access_key, $access_key_secret, $app_code);
    $params["sign"] = $sign_result['signature'];
    $params["timestamp"] = $sign_result['timestamp'];

    return $websocket_url . '?' . http_build_query($params);
}

// 构建开始报文
function build_start_frame() {
    $business = [
        'vadEos' => 5000
    ];
    $data = [
        'audioFormatInfo' => 'WAV',
        'sampleRate' => 'SAMPLE_RATE_16K'
    ];
    $frame = [
        'signal' => 'start',
        'business' => $business,
        'data' => $data
    ];
    return json_encode($frame);
}

// 构建结束报文
function build_end_frame() {
    $frame = [
        'signal' => 'end'
    ];
    return json_encode($frame);
}

// 处理验证结果报文
function after_process_verify($ws, $message_obj) {
    if ($message_obj->status == 'ok') {
        echo "校验通过，requestId: {$message_obj->requestId}, code: {$message_obj->code}\n";
        $ws->send(build_start_frame());
    } else {
        echo "校验失败，requestId: {$message_obj->requestId}, code: {$message_obj->code}, message: {$message_obj->message}\n";
    }
}

// 处理准备好进行语音识别报文
function after_process_server_ready($ws, $message_obj) {
    echo "处理服务端准备好进行语音识别报文\n";
    if ($message_obj->status == 'ok') {
        $file_path = "audio/BAC009S0002W0164.wav";
        $handle = fopen($file_path, 'rb');
        while (!feof($handle)) {
            $chunk = fread($handle, 10240);
            $ws->send($chunk, 'binary');
        }
        fclose($handle);
        $ws->send(build_end_frame());
    } else {
        echo "服务器准备失败, 报文: {$message_obj}\n";
    }
}

// 处理中间识别结果报文
function after_process_partial_result($ws, $message_obj) {
    global $result;
    echo "处理中间结果报文\n";
    $nbest = json_decode($message_obj->nbest, true);
    $sentence = $nbest[0]['sentence'];
    if (strlen($sentence) === 0) {
        echo "没有识别出结果，跳过此次中间结果报文处理\n";
        return;
    }
    if (strlen($result) > 0) {
        echo "当前语音识别结果：" . $result . "，{$sentence}\n";
    } else {
        echo "当前语音识别结果：{$sentence}\n";
    }
}

// 处理最终识别结果报文
function after_process_final_result($ws, $message_obj) {
    global $result;
    echo "处理最终结果报文\n";
    $nbest = json_decode($message_obj->nbest, true);
    $sentence = $nbest[0]['sentence'];
    if (strlen($sentence) === 0) {
        echo "没有识别出结果，跳过此次最终结果报文处理\n";
        return;
    }
    if (strlen($result) > 0) {
        $result .= '，';
        $result .= $sentence;
        echo "当前语音识别结果：" . $result . "\n";
    } else {
        $result[] = $sentence;
        echo "当前语音识别结果：" . implode('', $result) . "\n";
    }
}

// 处理识别结束报文
function after_process_speech_end($ws, $message_obj) {
    global $result;
    echo "收到识别结束报文\n";
    if (strlen($result) > 0) {
        $result .= '。';
    }
    echo "最终语音识别结果：" . $result . "\n";
    $ws->close();
}

// websocket 消息处理
function on_message($message) {
    global $ws;
    echo "接收到消息：{$message}\n";
    $message_obj = json_decode($message);
    if ($message_obj->type === 'verify') {
        after_process_verify($ws, $message_obj);
    } elseif ($message_obj->type === 'server_ready') {
        after_process_server_ready($ws, $message_obj);
    } elseif ($message_obj->type === 'partial_result') {
        after_process_partial_result($ws, $message_obj);
    } elseif ($message_obj->type === 'final_result') {
        after_process_final_result($ws, $message_obj);
    } elseif ($message_obj->type === 'speech_end') {
        after_process_speech_end($ws, $message_obj);
    }
}

function on_close($code, $reason) {
    echo "WebSocket连接关闭\n";
}

function on_error($code, $message) {
    echo "WebSocket发生异常: {$message}\n";
}

$url = "wss://k8s.arity.cn/asr/ws";
# 业务方唯一标识id，最高128位，建议不要重复，这里只是模拟
$btId = "123";
$accessKey = "accessKey(请替换为正确的accessKey)";
$accessKeySecret = "accessKey(请替换为正确的accessKeySecret)";
$appCode = "appCode(请替换为正确的appCode)";
$channelCode = "channelCode(请替换为正确的channelCode)";

$complete_url = get_websocket_url($url, $btId, $accessKey, $accessKeySecret, $appCode, $channelCode);
echo "构建参数后的url: {$complete_url}\n";

$ws = new Client($complete_url);
$ws->onMessage = 'on_message';
$ws->onClose = 'on_close';
$ws->onError = 'on_error';

$ws->run();

?>
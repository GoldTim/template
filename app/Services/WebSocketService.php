<?php

namespace App\Services;

use App\Models\ChatConnection;
use App\Models\ChatMessage;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface
{
    public function __construct()
    {

    }

    // 连接建立时触发
    public function onOpen(Server $server, Request $request)
    {
        // 在触发 WebSocket 连接建立事件之前，Laravel 应用初始化的生命周期已经结束，你可以在这里获取 Laravel 请求和会话数据
        // 调用 push 方法向客户端推送数据，fd 是客户端连接标识字段
        $server->push($request->fd, '服务器连接成功');
    }

    // 收到消息时触发
    public function onMessage(Server $server, Frame $frame)
    {
        // 调用 push 方法向客户端推送数据

        /**
         * 获取用户双方发送的信息
         * 检查发送对象
         * 将发送的信息推送至对方
         */
//        $room = InterChat::where('')->where('')->first();
//        $server->push($room->roomId, '');
//        $server->push($frame->fd, 'This is a message sent from WebSocket Server at ' . date('Y-m-d H:i:s'));

        $data = json_decode($frame->data, true);
        Log::info($data);
        $connectionList = ChatConnection::where([
            'source' => $data['source'],
            'roomId' => $data['roomId'],
            'fd' => $frame->fd,
            'status' => array_search('否', config('params.status'))
        ])->get(['id', 'fd']);
        foreach ($connectionList as $item) {
            if ($server->isEstablished($item->fd)) {
                $server->push($item->fd, json_encode([
                    'data' => $data['message'],
                    'userName' => ''
                ]));
            } else {
                ChatConnection::where('id', $item->id)->update([
                    'status' => array_search('是', config('params.status'))
                ]);
            }
        }
        $connection = ChatConnection::firstOrCreate([
            'fd' => $frame->fd,
            'roomId' => $data['roomId'],
            'source' => $data['source']
        ], ['status' => array_search('否', config('params.status'))]);
        if (config('params.status.' . $connection->status) === '是')
            $connection->update([
                'status' => array_search('否', config('params.status'))
            ]);
        ChatMessage::create([
            "roomId" => $data['roomId'],
            "type" => $data['type'],
            "source" => $data['source'],
            "message" => $data['message'],
            "status"=>array_search('是',config('params.status'))
        ]);
    }

    // 关闭连接时触发
    public function onClose(Server $server, $fd, $reactorId)
    {
        Log::info('WebSocket 连接关闭');
    }
}

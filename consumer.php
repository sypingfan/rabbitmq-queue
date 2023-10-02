<?php
/**
 * Created by PhpStorm.
 * User: 平凡
 * Date: 2023/9/26
 * Time: 16:05
 */

$workerNum = 4;//进程数量 ，看配置来设置--消耗CPU加内存--建议最多设置CPU4倍左右
$pool  = new Swoole\Process\Pool($workerNum);


//多进程，共享一个连接（串数据的问题） rabbitmq分不清是那个进程给我返回的消息，会发生串数据（就是你这个进程的数据发给另外一个进程）

//绑定了一个事件(底层C触发的)
$pool->on("WorkerStart",function ($pool,$workerId){
    //子进程空间
    echo "Worker#{$workerId} is started\d";
    //生产任务(rabbitMQ)
    try {
        $exchangeName = "trade";
        $routingKey = "/trade";//路由
        $queueName = "trade";//队列名称
        //1.建立连接
        $connection = new AMQPConnection([
            'host'=>'172.17.0.4',
            'port'=>5672,
            'vhost'=>'/',
            'login'=>'pingfan',
            'password'=>'123456'
        ]);
        $connection->connect();
        //2.建立通道
        $channel = new AMQPChannel($connection);
        //3.创建队列
        $queue = new AMQPQueue($channel);
        $queue->setName($queueName);
        $queue->declareQueue();
        $data = [
            "msg_type"=>'trade',
            "tid"=>uniqid(),
        ];
        //4.绑定路由监听
        $queue->bind($exchangeName,$routingKey);
        //没数据的时候，就是阻塞状态，获取到数据才会执行
        $queue->consume(function ($envelope,$queue)use($workerId){
            //消费 (刚取出消息，业务终止了，取出消息。执行不成功) 丢失数据
            //业务逻辑的地方

            var_dump($workerId,$envelope->getBody);
            //假如消息中间件单独一个服务器，因为要很大内存 --网络传输不可靠性
            //ack 应答机制 只要是跨机器的消息传输基本上都会用到
            $queue->ack($envelope->getDeliveryTag());//分布式实物处理
        });



    }catch (\Exception $e){
        var_dump($e);
    }
});


//进程关闭
//$pool->on("WorkerStop",function ($pool,$workerId){
//    echo "Worker#{$workerId} is stopped\d";
//});

$pool->start();



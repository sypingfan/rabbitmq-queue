<?php
/**
 * Created by PhpStorm.
 * User: 平凡
 * Date: 2023/9/26
 * Time: 16:33
 */

try {
    $exchangeName = "trade";
    $routingKey = "/trade";//路由
    //1.建立连接
    $connection = new AMQPConnection([
        'host'=>'127.0.0.1',
        'port'=>5672,
        'vhost'=>'/',
        'login'=>'quest',
        'password'=>'quest'
    ]);
    $connection->connect();
    //2.建立通道
    $channel = new AMQPChannel($connection);
    //3.创建交换机
    $exchange =  new AMQPExchange($channel);
    $exchange->setName($exchangeName);
    /**
     * 设置交换机类型
     * AMQP_EX_TYPE_DIRECT:直连交换机
     * AMQP_EX_TYPE_FANOUT:扇形交换机
     * AMQP_EX_TYPE_HEADERS:头交换机
     * AMQP_EX_TYPE_TOPIC:主题交换机
     */
    $exchange->setType(AMQP_EX_TYPE_DIRECT); //模式
    $exchange->declareExchange();
    $data = [
        "msg_type"=>'trade',
        "tid"=>uniqid(),
    ];
    //4.绑定路由关系发送消息
    $exchange->publish(json_encode($data),$routingKey);


}catch (\Exception $e){
    var_dump($e);
}

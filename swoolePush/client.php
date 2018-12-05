<?php

class Client
{
    private $client;

    public function __construct()
    {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);
    }

    public function connect()
    {
        if (!$this->client->connect("0.0.0.0", 9600, 1)) {
            throw new Exception(sprintf('Swoole Error: %s', $this->client->errCode));
        }
    }

    public function send($data)
    {
        if ($this->client->isConnected()) {
            if (!is_string($data)) {
                $data = json_encode($data);
            }
            return $this->client->send($data);
        } else {
            throw new Exception('Swoole Server does not connected.');
        }
    }

    public function close()
    {
        $this->client->close();
    }
}

$data = array(
    "url" => "http://127.0.0.1:9501",
    "param" => array(
        "username" => 'test',
        "password" => 'test'
    )
);
$client = new Client();
$client->connect();
if ($client->send($data)) {
    echo 'success';
} else {
    echo 'fail';
}
$client->close();
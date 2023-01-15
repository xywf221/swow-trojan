<?php

namespace xywf221\Trojan\Core;

use Psr\Log\LoggerInterface;
use Swow\Coroutine;
use Swow\Socket;
use xywf221\Trojan\Session\ServerSession;

class Service
{
    private Socket $acceptor;

    private string $session;

    private bool $stopped = false;

    public function __construct(private readonly array $config, private readonly LoggerInterface $logger)
    {
        $this->acceptor = new Socket(Socket::TYPE_TCP);
        if ($this->config['tcp']['no_delay']) {
            $this->acceptor->setTcpNodelay(true);
        }
        if ($this->config['tcp']['keep_alive']) {
            $this->acceptor->setTcpKeepAlive(true, $this->config['tcp']['keep_alive_delay']);
        }
        if ($this->config['tcp']['accept_balance']) {
            $this->acceptor->setTcpAcceptBalance(true);
        }
        $dict = [
            'server' => ServerSession::class
        ];
        $this->session = $dict[$this->config['run_type']] ?: 'client';

        $this->acceptor->bind($this->config['local_addr'], $this->config['local_port'], Socket::BIND_FLAG_REUSEADDR)->listen();
        $this->logger->critical("listen {$this->config['local_addr']}:{$this->config['local_port']}");
    }

    public function run(): void
    {
        while (!$this->stopped) {
            $socket = $this->acceptor->accept();
            $this->logger->info("incoming connection from {$socket->getPeerAddress()}:{$socket->getPeerPort()}");
            //todo 设置超时时间
            Coroutine::run(function () use ($socket) {
                $session = new $this->session($this->config, $this->logger);
                $session->start($socket);
            });
        }
    }

    public function stop(): void
    {
        $this->logger->critical("service shutdown");
        $this->stopped = true;
        $this->acceptor->close();
    }
}
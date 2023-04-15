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

    private array $sessionDict = [
        'server' => ServerSession::class
    ];

    public function __construct(private readonly array $config, private readonly LoggerInterface $logger)
    {
        $this->acceptor = new Socket(Socket::TYPE_TCP);
        if ($this->config['tcp']['accept_balance']) {
            $this->acceptor->setTcpAcceptBalance(true);
        }
        $this->session = $this->sessionDict[$this->config['run_type']] ?: 'server';

        $this->acceptor->bind($this->config['local_addr'], $this->config['local_port'])->listen();
        $this->logger->notice("listen {$this->config['local_addr']}:{$this->config['local_port']}");
    }

    public function run(): void
    {
        while (!$this->stopped) {
            $socket = $this->acceptor->accept();
            $this->logger->info("incoming connection from {$socket->getPeerAddress()}:{$socket->getPeerPort()}");
            Coroutine::run(function () use ($socket) {
                $this->configSocket($socket);
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

    private function configSocket(Socket $socket): void
    {
        if ($this->config['tcp']['no_delay']) {
            $socket->setTcpNodelay(true);
        }
        if ($this->config['tcp']['keep_alive']) {
            $socket->setTcpKeepAlive(true, $this->config['tcp']['keep_alive_delay']);
        }
        //todo 设置超时时间
    }
}
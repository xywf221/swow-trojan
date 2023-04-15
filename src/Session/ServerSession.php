<?php

namespace xywf221\Trojan\Session;

use Psr\Log\LoggerInterface;
use Swow\Coroutine;
use Swow\Socket;
use Swow\SocketException;
use Swow\Sync\WaitReference;
use xywf221\Trojan\Common\Buffer;
use xywf221\Trojan\Core\Constant;
use xywf221\Trojan\Exception\ParseRequestException;
use xywf221\Trojan\Exception\ParseUdpPacketException;
use xywf221\Trojan\Protocol\TrojanCommand;
use xywf221\Trojan\Protocol\TrojanRequest;
use xywf221\Trojan\Protocol\UdpPacket;
use xywf221\Trojan\Protocol\UdpWrapSocket;
use function Swow\defer;

class ServerSession implements SessionInterface
{
    public function __construct(private readonly array $config, private readonly LoggerInterface $logger)
    {
    }

    public function start(Socket $inSocket)
    {
        defer(static function () use ($inSocket): void {
            $inSocket->close();
        });

        if (!$this->tlsHandshake($inSocket)) {
            $this->logger->notice("tls handshake failed");
            return;
        }
        $this->logger->notice("tls handshake success");

        $buffer = new Buffer(Constant::MAX_LENGTH);
        $inSocket->recv($buffer);

        $valid = false;
        $request = null;

        try {
            $request = TrojanRequest::Parse($buffer);
            //检查密码
            if (in_array($request->password, $this->config['password'])) {
                $this->logger->info("authenticated by authenticator (" . substr($request->password, 0, 7) . ')');
                $valid = true;
            } else {
                $this->logger->info("valid trojan request structure but possibly incorrect password ($request->password)");
            }
            // 判断是否是udp数据包如果是再次解析
            if ($valid and $request->command == TrojanCommand::UDP_ASSOCIATE) {
                $this->logger->info("Command UDP associate");
                //再次解析下
                $packet = UdpPacket::Parse($buffer, $request->length);
                $request->addr = $packet->addr;
                $request->length = $packet->length;
            }
        } catch (ParseRequestException $exception) {
            $this->logger->debug("parse request failed reason " . $exception->getMessage());
        } catch (ParseUdpPacketException $e) {
            $this->logger->debug("parse udp packet failed reason " . $e->getMessage());
            //如果密码都对但是udp数据包解析出错那就是客户端协议出问题了没必须继续
            return;
        }

        $queryAddr = $valid ? $request->addr->address : $this->config['remote_addr'];
        $queryPort = $valid ? $request->addr->port : $this->config['remote_port'];

        $bufferOffset = 0;
        if ($valid) {
            $this->logger->info("requested connection to {$request->addr->address}:{$request->addr->port}");
            $bufferOffset = $request->length;
        } else {
            $this->logger->info("not trojan request, connecting to $queryAddr:$queryPort");
        }

        // 流量统计方式以客户端为主
        try {
            $sentTraffic = $recvTraffic = 0;
            if (!$valid or $request->command == TrojanCommand::CONNECT) {
                $outSocket = new Socket(Socket::TYPE_TCP);
            } else {
                $outSocket = new Socket(Socket::TYPE_UDP);
            }
            $outSocket->connect($queryAddr, $queryPort);
            // intSocket <[ == ]> outSocket
            $outSocket->send($buffer, $bufferOffset);
            $recvTraffic += $buffer->getLength() - $bufferOffset;
            $buffer->clear();

            $wr = new WaitReference();
            // 这里是从 客户端读 然后写到 远程端
            Coroutine::run(static function (Socket $src, Socket $dst) use ($wr, &$recvTraffic): void {
                $recvTraffic += swow_socket_copy($src, $dst);
                $dst->close();
            }, $inSocket, $outSocket);

            // 这里是从 远程端读 然后写到 客户端
            Coroutine::run(static function (Socket $src, Socket $dst) use ($wr, &$sentTraffic): void {
                $sentTraffic = swow_socket_copy($src, $dst);
                $dst->close();
            }, $outSocket, $valid && $request->command == TrojanCommand::UDP_ASSOCIATE ? new UdpWrapSocket($inSocket, $queryAddr, $queryPort) : $inSocket);
            WaitReference::wait($wr);
            $this->logger->info("forward traffic usage addr:[$queryAddr:$queryPort],recv:$recvTraffic,sent:$sentTraffic");
        } catch (\Exception $exception) {
            $this->logger->notice("forward data failed reason " . $exception->getMessage());
        }
    }

    private function tlsHandshake(Socket $conn): bool
    {
        try {
            $conn->enableCrypto([
                'certificate' => $this->config['ssl']['cert'],
                'certificate_key' => $this->config['ssl']['key'],
            ]);
        } catch (SocketException $exception) {
            $this->logger->debug($exception->getMessage());
            return false;
        }
        return true;
    }
}
<?php

namespace xywf221\Trojan\Command;

use Psr\Log\LogLevel;
use Swow\Channel;
use Swow\Coroutine;
use Swow\Signal;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use xywf221\Trojan\Core\Configuration;
use xywf221\Trojan\Core\Service;

class ServiceCommand extends Command
{
    protected function configure()
    {
        $this->setName('service')
            ->setDescription('run trojan')
            ->addArgument('config', InputArgument::OPTIONAL, 'Trojan configuration file', 'config.yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);
        $config = Yaml::parseFile($input->getArgument('config'));
        $configuration = new Configuration();
        $processor = new Processor();
        $serviceConfiguration = $processor->processConfiguration(
            $configuration,
            [$config]
        );
        for ($i = 0; $i < count($serviceConfiguration['password']); $i++) {
            $serviceConfiguration['password'][$i] = hash('sha224', $serviceConfiguration['password'][$i]);
            $logger->debug("sha224 hash password " . substr($serviceConfiguration['password'][$i], 0, 7));
        }
        $service = new Service($serviceConfiguration, $logger);
        Coroutine::run(static function () use ($service) {
            $service->run();
        });
        $stopChannel = new Channel();
        $signals = [
            Signal::INT,
            Signal::TERM,
            Signal::SEGV
        ];
        foreach ($signals as $signal) {
            Coroutine::run(static function () use ($signal, $stopChannel) {
                Signal::wait($signal);
                $stopChannel->push($signal);
            });
        }
        $stopChannel->pop();
        $service->stop();
        return Command::SUCCESS;
    }
}
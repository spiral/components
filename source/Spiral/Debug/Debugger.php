<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Debug;

use Psr\Log\LoggerAwareInterface;
use Spiral\Core\ConfiguratorInterface;
use Spiral\Core\ContainerInterface;
use Spiral\Core\Singleton;
use Spiral\Core\Traits\ConfigurableTrait;
use Spiral\Debug\Exceptions\BenchmarkException;
use Spiral\Debug\Traits\LoggerTrait;

/**
 * Debugger is responsible for global log, benchmarking and configuring spiral loggers.
 */
class Debugger extends Singleton implements BenchmarkerInterface, LoggerAwareInterface
{
    /**
     * Logger trait is required for Dumper to perform dump into debug log.
     */
    use ConfigurableTrait, LoggerTrait;

    /**
     * Declares to IoC that component instance should be treated as singleton.
     */
    const SINGLETON = self::class;

    /**
     * Configuration section.
     */
    const CONFIG = 'debug';

    /**
     * @invisible
     * @var array
     */
    private $benchmarks = [];

    /**
     * Global log contains every message payload generated by Logger instance.
     *
     * @see Logger
     * @var array
     */
    private $globalLog = [];

    /**
     * @invisible
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @param ConfiguratorInterface $configurator
     * @param ContainerInterface    $container
     */
    public function __construct(ConfiguratorInterface $configurator, ContainerInterface $container)
    {
        $this->config = $configurator->getConfig(static::CONFIG);
        $this->container = $container;
    }

    /**
     * Configure logger handlers.
     *
     * @param Logger $logger
     */
    public function configureLogger(Logger $logger)
    {
        if (!isset($this->config['loggers'][$logger->getName()])) {
            //Nothing to configure
            return;
        }

        foreach ($this->config['loggers'][$logger->getName()] as $logLevel => $handler) {
            $logger->setHandler($logLevel, $this->container->get($handler['class'], [
                'options' => $handler
            ]));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws BenchmarkException
     */
    public function benchmark($caller, $record, $context = '')
    {
        $benchmarkID = count($this->benchmarks);
        if (is_array($record)) {
            $benchmarkID = $record[0];
        } elseif (!isset($this->benchmarks[$benchmarkID])) {
            $callerID = is_object($caller) ? spl_object_hash($caller) : $caller;
            $this->benchmarks[$benchmarkID] = [$caller, $record, $context, microtime(true)];

            //Payload
            return [$callerID];
        }

        if (!isset($this->benchmarks[$benchmarkID])) {
            throw new BenchmarkException("Unpaired benchmark record '{$benchmarkID}'.");
        }

        $this->benchmarks[$benchmarkID][4] = microtime(true);

        return $this->benchmarks[$benchmarkID][4] - $this->benchmarks[$benchmarkID][3];
    }

    /**
     * Retrieve all active and finished benchmark records.
     *
     * @return array|null
     */
    public function getBenchmarks()
    {
        return $this->benchmarks;
    }

    /**
     * Register global log message (every spiral logger will do that). Use config to disable this
     * functionality.
     *
     * @param string $channel
     * @param string $logLevel
     * @param string $message Must not be interpolated.
     * @param array  $context
     */
    public function logGlobal($channel, $logLevel, $message, array $context = [])
    {
        if (!$this->config['globalLogging']['enabled']) {
            return;
        }

        //No interpolation at this moment
        $this->globalLog[] = [
            Logger::MESSAGE_CHANNEL   => $channel,
            Logger::MESSAGE_TIMESTAMP => microtime(true),
            Logger::MESSAGE_LEVEL     => $logLevel,
            Logger::MESSAGE_BODY      => $message,
            Logger::MESSAGE_CONTEXT   => $context
        ];

        if (count($this->globalLog) > $this->config['globalLogging']['maxSize']) {
            //Removing the oldest message
            $this->globalLog = array_slice($this->globalLog, 1);
        }
    }

    /**
     * Payloads of every message raised by spiral Logger.
     *
     * @return array
     */
    public function globalMessages()
    {
        $result = [];
        foreach ($this->globalLog as $message) {
            //Delayed interpolating
            $message[Logger::MESSAGE_BODY] = \Spiral\interpolate(
                $message[Logger::MESSAGE_BODY],
                $message[Logger::MESSAGE_CONTEXT]
            );

            $result[] = $message;
        }

        return $result;
    }
}
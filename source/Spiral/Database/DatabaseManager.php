<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Database;

use Spiral\Core\Component;
use Spiral\Core\Container;
use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Database\Configs\DatabasesConfig;
use Spiral\Database\Entities\Database;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Exceptions\DatabaseException;
use Spiral\Database\Exceptions\DBALException;

class DatabaseManager extends Component implements SingletonInterface, InjectorInterface
{
    /**
     * @var Database[]
     */
    private $databases = [];

    /**
     * @var Driver[]
     */
    private $connections = [];

    /**
     * @var DatabasesConfig
     */
    protected $config = null;

    /**
     * @invisible
     *
     * @var FactoryInterface
     */
    protected $factory = null;

    /**
     * @param DatabasesConfig  $config
     * @param FactoryInterface $factory
     */
    public function __construct(DatabasesConfig $config, FactoryInterface $factory = null)
    {
        $this->config = $config;
        $this->factory = $factory ?? new Container();
    }

    /**
     * Manually set database.
     *
     * @param Database $database
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addDatabase(Database $database): DatabaseManager
    {
        if (isset($this->databases[$database->getName()])) {
            throw new DBALException("Database '{$database->getName()}' already exists");
        }

        $this->databases[$database->getName()] = $database;

        return $this;
    }

    /**
     * Automatically create database instance based on given options and connection (in a form or
     * instance or alias).
     *
     * @param string        $name
     * @param string        $prefix
     * @param string|Driver $connection Connection name or instance.
     *
     * @return Database
     *
     * @throws DBALException
     */
    public function createDatabase(string $name, string $prefix, $connection): Database
    {
        if (!$connection instanceof Driver) {
            $connection = $this->driver($connection);
        }

        $instance = $this->factory->make(
            Database::class,
            ['name' => $name, 'prefix' => $prefix, 'driver' => $connection]
        );

        $this->addDatabase($instance);

        return $instance;
    }

    /**
     * Get Database associated with a given database alias or automatically created one.
     *
     * @param string|null $database
     *
     * @return Database
     *
     * @throws DBALException
     */
    public function database(string $database = null): Database
    {
        if (empty($database)) {
            $database = $this->config->defaultDatabase();
        }

        //Spiral support ability to link multiple virtual databases together using aliases
        $database = $this->config->resolveAlias($database);

        if (isset($this->databases[$database])) {
            return $this->databases[$database];
        }

        if (!$this->config->hasDatabase($database)) {
            throw new DBALException(
                "Unable to create Database, no presets for '{$database}' found"
            );
        }

        //No need to benchmark here, due connection will happen later
        $instance = $this->factory->make(Database::class, [
            'name'   => $database,
            'prefix' => $this->config->databasePrefix($database),
            'driver' => $this->driver($this->config->databaseDriver($database)),
        ]);

        return $this->databases[$database] = $instance;
    }

    /**
     * Manually set connection instance.
     *
     * @param Driver $driver
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addDriver(Driver $driver): DatabaseManager
    {
        if (isset($this->connections[$driver->getName()])) {
            throw new DBALException("Connection '{$driver->getName()}' already exists");
        }

        $this->connections[$driver->getName()] = $driver;

        return $this;
    }

    /**
     * Create and register connection under given name.
     *
     * @param string $name
     * @param string $driver Driver class.
     * @param string $dns
     * @param string $username
     * @param string $password
     *
     * @return Driver
     */
    public function createDriver(
        string $name,
        string $driver,
        string $dns,
        string $username,
        string $password = ''
    ): Driver {
        $instance = $this->factory->make($driver, [
            'name'    => $name,
            'options' => [
                'connection' => $dns,
                'username'   => $username,
                'password'   => $password
            ]
        ]);

        $this->addDriver($instance);

        return $instance;
    }

    /**
     * Get connection/driver by it's name. Every driver associated with configured connection,
     * there is minor de-sync in naming due legacy code.
     *
     * @param string $connection
     *
     * @return Driver
     *
     * @throws DBALException
     */
    public function driver(string $connection): Driver
    {
        if (isset($this->connections[$connection])) {
            return $this->connections[$connection];
        }

        if (!$this->config->hasDriver($connection)) {
            throw new DBALException(
                "Unable to create Driver, no presets for '{$connection}' found"
            );
        }

        $instance = $this->factory->make($this->config->driverClass($connection), [
            'name'    => $connection,
            'options' => $this->config->driverOptions($connection),
        ]);

        return $this->connections[$connection] = $instance;
    }

    /**
     * Get instance of every available database.
     *
     * @return Database[]
     *
     * @throws DatabaseException
     */
    public function getDatabases(): array
    {
        $result = [];

        foreach ($this->config->databaseNames() as $name) {
            $result[] = $this->database($name);
        }

        foreach ($this->databases as $database) {
            if (!in_array($database->getName(), $result)) {
                $result[] = $database->getName();
            }
        }

        return $result;
    }

    /**
     * Get instance of every available driver/connection.
     *
     * @return Driver[]
     *
     * @throws DatabaseException
     */
    public function getDrivers(): array
    {
        $result = [];

        //todo: include manually added drivers
        foreach ($this->config->driverNames() as $name) {
            $result[] = $this->driver($name);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createInjection(\ReflectionClass $class, string $context = null)
    {
        //If context is empty default database will be returned
        return $this->database($context);
    }
}
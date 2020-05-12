<?php

namespace App\Db;

use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;

class PDO
{
    protected $pools;

    /**
     * @var array
     */
    protected $config = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
        'options' => [],
        'size' => 64,
    ];

    private static $instance;

    public function __construct(array $config)
    {
        if (empty($this->pools)) {
            $this->config = array_replace_recursive($this->config, $config);
            $this->pools = new PDOPool(
                (new PDOConfig())
                    ->withHost($this->config['host'])
                    ->withPort($this->config['port'])
                    ->withDbName($this->config['database'])
                    ->withCharset($this->config['charset'])
                    ->withUsername($this->config['username'])
                    ->withPassword($this->config['password'])
                    ->withOptions($this->config['options']),
                $this->config['size']
            );
        }
    }

    public static function getInstance($config = null)
    {
        if (empty(self::$instance)) {
            if (empty($config)) {
                throw new \RuntimeException('pdo config empty');
            }
            self::$instance = new static($config);
        }

        return self::$instance;
    }

    public function getConnection()
    {
        $pdo = $this->pools->get();
        \Swoole\Coroutine::defer(
            function () use ($pdo) {
                if (@$pdo->is_transaction) {
                    $pdo->rollBack();
                    $pdo->is_transaction = false;
                }
                $this->close($pdo);
            }
        );
        return $pdo;
    }

    public function close($connection = null)
    {
        $this->pools->put($connection);
    }
}

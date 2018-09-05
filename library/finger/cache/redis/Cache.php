<?php
/**
 * Redis缓存。
 * -- 错误码：3008xxx
 * 
 * @author fingerQin
 * @date 2016-09-11
 */

namespace finger\cache\redis;

class Cache {

    /**
     * 当前对象。
     * @var finger\cache\redis
     */
    protected $client = null;

    public function __construct() {
        $clientName = 'finger_cache_redis';
        if (\Yaf_Registry::has($clientName)) {
            $this->client = \Yaf_Registry::get($clientName);
            $redisIndex   = YCore::appconfig('database.redis.index');
            $this->client->select($redisIndex); // 必须显示切换到指定的 Redis 库。避免使用过程中被其他程序切换未还原。
        } else {
            $this->client = $this->connect();
            \Yaf_Registry::set($clientName, $this->client);
        }
    }

    /**
     * 获取 Redis 客户端连接。
     *
     * @return finger\cache\redis
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * 连接 redis
     */
    protected function connect() {
        $ok = \Yaf_Registry::has('redis');
        if ($ok) {
            return \Yaf_Registry::get('redis');
        } else {
            $config      = \Yaf_Registry::get('config');
            $redis_host  = $config->database->redis->host;
            $redis_port  = $config->database->redis->port;
            $redis_pwd   = $config->database->redis->pwd;
            $redis_index = $config->database->redis->index;
            $redis = new \Redis();
            $redis->connect($redis_host, $redis_port);
            $redis->auth($redis_pwd);
            $redis->select($redis_index);
            \Yaf_Registry::set('redis', $redis);
            return $redis;
        }
    }

    /**
     * 自增1。
     * @param  string $cacheKey 缓存 KEY。
     * @return integer 自增之后的值。
     */
    public function incr($cacheKey) {
        return $this->client->incr($cacheKey);
    }

    /**
     * 获取缓存。
     * @param string $cache_key 缓存 KEY。
     * @return string|array|boolean
     */
    public function get($cacheKey) {
        $cacheData = $this->client->get($cacheKey);
        return $cacheData ? json_decode($cacheData, true) : false;
    }

    /**
     * 写缓存。
     * @param string $cacheKey 缓存 KEY。
     * @param string|array $cacheData 缓存数据。
     * @param integer $expire 生存时间。单位(秒)。0 代表永久生效。
     * @return boolean
     */
    public function set($cacheKey, $cacheData, $expire = 0) {
        if ($expire > 0) {
            return $this->client->setEx($cacheKey, $expire, json_encode($cacheData));
        } else {
            return $this->client->set($cacheKey, json_encode($cacheData));
        }
    }

    /**
     * 删除缓存。
     * @param string $cacheKey
     * @return boolean
     */
    public function delete($cacheKey) {
        return $this->client->del($cacheKey);
    }
}
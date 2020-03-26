<?php
/**
 * Class DistributedLocks 基于redis的分布式锁
 * @package app\common\locks
 */
class DistributedLocks
{
    protected $Handler;

    /**
     * DistributedLocks constructor.
     * @param $Redis redis对象
     */
    public function __construct($Redis)
    {
        $this->Handler = $Redis;
    }

    /**
     * GetLock 获取锁函数
     * @param $Key string 锁key
     * @param $Expire string 锁定的时间(秒)
     * @return bool 获取结果 成功true 失败false 成功外面做业务逻辑处理
     */
    public function GetLock($Key, $Expire)
    {
        // 判断参数是否为空
        if (empty($Key) || empty($Expire)) {
            return false;
        }

        $Result = $this->Handler->get($Key);
        if (!$Result) {
            // 如果没有值 设置值
            $lockResult = SETNX($Key, time() + $Expire);
            if ($lockResult) {
                // 加锁成功 设置过期时间
                $this->handler->expire($Key, $Expire);
                return true;
            }
        } else {
            // 有值 判断是否 过期 防止死锁
            if ($this->Handler->get($Key) < time()) {
                // 上锁判断上一个值是否过期 防止其他进程已经上锁
                if ($this->Handler->getSet($Key, time() + $Expire) < time()) {
                    $this->Handler->expire($Key, $Expire);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 释放锁函数
     * @param $Key string 锁key
     * @return bool 释放结果
     */
    public function release($Key)
    {
        if ($this->Handler->ttl($Key)) {
            $this->Handler->del($Key);
        }

        return true;
    }

    private function __clone() {}
}
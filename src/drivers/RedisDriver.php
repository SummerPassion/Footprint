<?php
/**
 * User: summerpassion
 * DateTime: 2019/9/17 16:10
 */

namespace Mustang\Footprint\drivers;

use Mustang\Footprint\contracts\Driver;
use Mustang\Utils\Utils;

class RedisDriver extends Driver
{
    /**
     * redis实例
     * @var object
     */
    private $redis_obj = null;

    /**
     * 配置
     * @var array|mixed
     */
    protected $fp_config = [];

    /**
     * RedisCart constructor.
     */
    public function __construct()
    {
        $this->redis_obj = Utils::redis();
        $this->fp_config = config('footprint');
    }

    /**
     * 记录
     * @param string $val 记录纸
     * @param null $uid 用户标识
     * @param string|null $ord 排序
     * @param string|null $env 场景
     * @return bool|mixed
     */
    public function log($val, $uid = null, $ord = self::SEQ, $env = self::DEFAULT)
    {
        if (!$val && !is_numeric($val)) {
            throw new \InvalidArgumentException("记录值不能为空。");
        }

        $max_len = $this->fp_config['max_len'] ?? $this->max_len;

        if (mb_strlen($val, 'UTF-8') > $max_len) {
            throw new \InvalidArgumentException("记录值长度不能大于{$max_len}个中英文字符。");
        }

        $key = self::$fp_prefix . ($env ?: self::DEFAULT) . ':' . ($uid ? self::$user_prefix . $uid : '');

        switch ($ord) {
            case self::HEAT:
                $this->logByHeat($key, $val);
                break;
            case self::SEQ:
                $this->logBySeq($key, $val);
                break;
            default:
                throw new \InvalidArgumentException("不支持的排序参数。");
        }

        return true;
    }

    /**
     * 记录热度历史
     * @param $key
     * @param $val
     */
    protected function logByHeat($key, $val)
    {
        $step = 1;
        // 原子操作
        if ($this->redis_obj->zIncrby($key, $step, $val)) {

            // 超限判断
            $fp_config = $this->fp_config['heat'];

            if ($this->redis_obj->zCard($key) > $fp_config['persist']) {
                if (!$this->redis_obj->zRemRangeByRank($key, 0, $fp_config['del'])) {
                    throw new \RuntimeException('清除历史信息异常！');
                }
            }
        } else {
            throw new \RuntimeException('记录热度排名历史异常！');
        }
    }

    /**
     * 记录时序历史
     * @param $key
     * @param $val
     */
    protected function logBySeq($key, $val)
    {
        $this->redis_obj->lRem($key, 0, $val);
        if (false === $this->redis_obj->lPush($key, $val)) {
            throw new \RuntimeException('记录时序排名历史异常！');
        }

        // 超限判断
        $fp_config = $this->fp_config['seq'];

        if ($this->redis_obj->lLen($key) > $fp_config['persist']) {
            $this->redis_obj->lTrim($key, 0, ($fp_config['persist'] - $fp_config['del']));
        }
    }

    /**
     * 获取历史
     * @param int $end 长度
     * @param null $uid 用户标识
     * @param string $ord 排序
     * @param string $env 场景
     * @return mixed|void
     */
    public function get($end, $uid = null, $ord = self::SEQ, $env = self::DEFAULT)
    {
        $key = self::$fp_prefix . ($env ?: self::DEFAULT) . ':' . ($uid ? self::$user_prefix . $uid : '');

        $end ?? $end = $end - 1;

        switch ($ord) {
            case self::HEAT:
                $method = "zRevRange";
                break;
            case self::SEQ:
                $method = "lRange";
                break;
            default:
                throw new \RuntimeException("不支持的排序参数。");
        }

        return $this->redis_obj->{$method}($key, 0, $end - 1);
    }

    /**
     * 清除历史
     * @param null $uid 用户标识
     * @param null $env 场景
     * @return mixed
     */
    public function clear($uid=null, $env=null)
    {
        $key = self::$fp_prefix . ($env ?: self::DEFAULT) . ':' . ($uid ? self::$user_prefix . $uid : '');

        return $this->redis_obj->del($key);
    }
}
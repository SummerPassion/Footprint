<?php
/**
 * User: summerpassion
 * DateTime: 2019/9/17 14:41
 */

namespace Mustang\Footprint;

use Mustang\Footprint\contracts\Driver;
use Mustang\Footprint\exception\FootprintException;

/**
 * 历史和足迹相关
 *  热门搜索
 *  浏览足迹
 * Class Footprint
 * @method static Footprint log($val, $uid = null, $env = self::DEFAULT, $ord = self::SEQ) 记录
 * @method static Footprint get($end, $uid = null, $env = self::DEFAULT, $ord = self::SEQ) 获取
 * @method static Footprint pageQuery($uid, $page=1, $pageSize=10, $env = self::DEFAULT, $ord = self::SEQ) 分页获取
 * @method static Footprint clear($uid=null, $env=null) 清除
 * @package Mustang\Footprint
 */
class Footprint
{
    /**
     * @var object 存储介质
     */
    protected static $driver = null;

    /**
     * @var string 类后缀
     */
    protected static $suffix = 'Driver';

    /**
     * ShoppingCart constructor.
     */
    protected function __construct(...$params) {}

    /**
     * Magic static call.
     * @param $method
     * @param $params
     */
    public static function __callStatic($method, $params)
    {
        $instance = new self($params);
        $driver = config('footprint.driver') ?: 'redis';
        $class = $instance->create($driver);

        if (method_exists($class, $method)) {
            return call_user_func_array([$class, $method], $params);
        } else {
            throw new FootprintException("[{$method}] 方法不存在！");
        }
    }

    /**
     * 创建实例
     * @param string $driver
     * @return Driver
     */
    protected function create($driver): Driver
    {
        $driver = __NAMESPACE__.'\\drivers\\' . ucfirst($driver . self::$suffix);

        if (self::$driver) {
            return self::$driver;
        } else {
            if (class_exists($driver)) {
                return $this->make($driver);
            } else {
                throw new FootprintException("Driver [{$driver}] Not Exists");
            }
        }
    }

    /**
     * make
     * @param string $driver
     * @return Driver
     */
    protected function make($driver) : Driver
    {
        $app = new $driver();

        if ($app instanceof Driver) {
            return $app;
        }

        throw new FootprintException("[{$driver}] Must Be An Instance Of Driver");
    }
}

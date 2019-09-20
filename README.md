#mustang/footprint
>"thinkphp5.0 商城足迹基于redis的实现，可自行扩展其他存储介质。

##安装方法
```
composer require mustang/footprint
```

##在项目中使用footprint
>V1.0版本仅更新了以Redis为存储介质的历史存取，后期可能扩展其他存储介质。 
>需要在config.php中配置以下信息


```php
// redis相关配置
'redis' => [
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '',
    'db_id' => 0
]

// footprint 历史
'footprint' => [
    'driver' => 'redis',
    'max_len' => 80,
    // 热度排名
    'heat' => [
        'persist' => 1000, // 默认保留
        'del' => 100, // 满足长度后删除排名靠后的
    ],
    // 时序排名
    'seq' => [
        'persist' => 1000, // 默认保留
        'del' => 100, // 满足长度后保留从N位裁剪掉
    ]
]

```

**记录**

```php
/**
 * 记录
 * @param string $val 记录纸
 * @param null $uid 用户标识
 * @param string|null $ord 排序
 * @param string|null $env 场景
 * @return bool|mixed
 */
Footprint::log($val, $uid = null, $ord = self::SEQ, $env = self::DEFAULT);
```

**获取**

```php
/**
 * 获取历史
 * @param int $end 长度
 * @param null $uid 用户标识
 * @param string $ord 排序
 * @param string $env 场景
 * @return mixed|void
 */
Footprint::get($end, $uid = null, $ord = self::SEQ, $env = self::DEFAULT);
```

**分页获取**

```php
/**
 * 分页获取
 * @param int $uid 用户id
 * @param int $page 第N页 N>0
 * @param int $pageSize 页面大小
 * @param string $env 场景
 * @param string $ord 排序
 */
Footprint::pageQuery($uid, $page=1, $pageSize=10, $env = self::DEFAULT, $ord = self::SEQ);
```

**清理**

```php
/**
 * 清除历史
 * @param null $uid 用户标识
 * @param null $env 场景
 * @return mixed
 */
Footprint::clear($uid=null, $env=null);
```

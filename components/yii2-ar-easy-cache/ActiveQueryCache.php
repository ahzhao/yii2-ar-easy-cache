<?php
/**
 * Created by
 * Author: zhao <m420092@126.com>
 * Time: 16/6/1 13:59
 * Description:
 */

namespace components\yii2ArEasyCache;


use yii\base\ErrorException;
use yii\caching\Cache;
use yii\db\ActiveQuery;
use yii\db\Command;

class ActiveQueryCache extends ActiveQuery
{

    /**
     * @var string 缓存key值前缀
     */
    public $cacheKeyPrefix = 'ar-';
    /**
     * @var string 缓存使用的组件名
     */
    public $cacheComponentName = 'cache';
    /**
     * @var int 缓存过期时间
     */
    public $cacheDuration =60;

    private $cache = null;
    private  $disableCache = false;

    public function all($db = null)
    {
        if($this->disableCache) return parent::all($db);
        $command = $this->createCommand($db);
        $rows = $this->getCacheData($command);
        if ($rows === false) {
            //缓存的数组结构数据，通过populate在处理结构
            $rows = $command->queryAll();
            //即使是空也进行缓存生成，避免缓存穿透。但会浪费缓存空间
            $this->setCacheData($command, $rows);
        }

        return $this->populate($rows);
    }

    public function one($db = null)
    {
        if($this->disableCache) return parent::one($db);
        $command = $this->createCommand($db);
        $row = $this->getCacheData($command);
        if ($row === false) {
            $row = $command->queryOne();
            //即使是空也进行缓存生成，避免缓存穿透。但会浪费缓存空间
            $this->setCacheData($command, $row === false ? null : $row);
        }
        if ($row === false || is_null($row)) {
            return null;
        } else {
            $models = $this->populate([$row]);
            return reset($models) ?: null;
        }
    }

    /**
     * @return $this
     * Description:
     */
    public function disableCache(){
        $this->disableCache=true;
        return $this;
    }

    /**
     * @return Cache
     * @throws ErrorException
     * Description:
     */
    private function getCacheComponent()
    {
        if (!$this->cache instanceof Cache) {
            $cacheComponent = $this->cacheComponentName;
            $this->cache = \Yii::$app->$cacheComponent;
            if (!$this->cache instanceof Cache)
                throw new ErrorException('cache is unavailable');
        }
        return $this->cache;
    }

    private function getCacheData(Command $command)
    {
        $res = $this->getCacheComponent()->get($this->getCacheKey($command));
        return $res ? unserialize($res) : $res;
    }

    private function setCacheData(Command $command, $data)
    {
        return $this->getCacheComponent()->set(
            $this->getCacheKey($command),
            serialize($data),
            $this->cacheDuration
        );
    }

    /**
     * @param Command $command
     * @return string
     * Description:
     */
    protected function getCacheKey(Command $command)
    {
        return $this->cacheKeyPrefix.md5(serialize([$command->params,$command->sql,$command->db->dsn,$command->db->username]));
    }
}
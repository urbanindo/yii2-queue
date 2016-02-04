<?php
/**
 * DbQueue class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */

namespace UrbanIndo\Yii2\Queue\Queues;

use yii\redis\Connection;
use UrbanIndo\Yii2\Queue\Job;

/**
 * RedisQueue provides Redis storing for Queue.
 *
 * This uses `yiisoft/yii2-redis` extension that doesn't shipped in the default
 * composer dependency. To use this you have to manually add `yiisoft/yii2-redis`
 * in the `composer.json`.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */
class RedisQueue extends \UrbanIndo\Yii2\Queue\Queue
{
    /**
     * Stores the redis connection.
     * @var string|array|Connection
     */
    public $db = 'redis';
    
    /**
     * The name of the key to store the queue.
     * @var string
     */
    public $key = 'queue';
    
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->db = \yii\di\Instance::ensure($this->db, Connection::className());
    }

    /**
     * Delete the job.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    public function deleteJob(Job $job)
    {
        return true;
    }

    /**
     * Return next job from the queue.
     * @return Job|boolean the job or false if not found.
     */
    protected function fetchJob()
    {
        $json = $this->db->lpop($this->key);
        if ($json == false) {
            return false;
        }
        $data = \yii\helpers\Json::decode($json);
        $job = $this->deserialize($data['data']);
        $job->id = $data['id'];
        $job->header['serialized'] = $data['data'];
        return $job;
    }

    /**
     * Post new job to the queue.  This contains implementation for database.
     *
     * @param Job $job The job to post.
     * @return boolean whether operation succeed.
     */
    protected function postJob(Job $job)
    {
        return $this->db->rpush($this->key, \yii\helpers\Json::encode([
            'id' => uniqid('queue_', true),
            'data' => $this->serialize($job),
        ]));
    }

    /**
     * Put back job to the queue.
     *
     * @param Job $job The job to restore.
     * @return boolean whether the operation succeed.
     */
    protected function releaseJob(Job $job)
    {
        return $this->db->rpush($this->key, \yii\helpers\Json::encode([
            'id' => $job->id,
            'data' => $job->header['serialized'],
        ]));
    }
    
    /**
     * Returns the total number of all queue size.
     * @return integer
     */
    public function getSize()
    {
        return $this->db->llen($this->key);
    }
    
    /**
     * Purge the whole queue.
     * @return boolean
     */
    public function purge()
    {
        return $this->db->del($this->key);
    }
}

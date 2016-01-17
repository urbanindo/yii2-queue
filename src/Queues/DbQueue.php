<?php
/**
 * DbQueue class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */

namespace UrbanIndo\Yii2\Queue\Queues;

use UrbanIndo\Yii2\Queue\Job;

/**
 * DbQueue provides Yii2 database storing for Queue.
 *
 * The schema of the table should follow:
 *
 * CREATE TABLE queue (
 *     id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT
 *     status TINYINT NOT NULL DEFAULT 0
 *     timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
 *     data BLOB
 * );
 *
 * The queue works under the asumption that the `id` fields is AUTO_INCREMENT and
 * the `timestamp` will be set using current timestamp.
 *
 * For other implementation, override the `fetchLatestRow` method and `postJob`
 * method.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */
class DbQueue extends \UrbanIndo\Yii2\Queue\Queue
{
    /**
     * Status when the job is ready.
     */
    const STATUS_READY = 0;
    
    /**
     * Status when the job is being runned by the worker.
     */
    const STATUS_ACTIVE = 1;
    
    /**
     * Status when the job is deleted.
     */
    const STATUS_DELETED = 2;
    
    /**
     * The database used for the queue.
     *
     * This will use default `db` component from Yii application.
     * @var string|\yii\db\Connection
     */
    public $db = 'db';
    
    /**
     * The name of the table to store the queue.
     *
     * The table should be pre-created as follows for MySQL:
     *
     * ```php
     * CREATE TABLE queue (
     *     id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT
     *     status TINYINT NOT NULL DEFAULT 0
     *     timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     *     data LONGBLOB
     * );
     * ```
     * @var string
     */
    public $tableName = '{{%queue}}';
    
    /**
     * Whether to do hard delete of the deleted job, instead of just flagging the
     * status.
     * @var boolean
     */
    public $hardDelete = true;
    
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->db = \yii\di\Instance::ensure($this->db, \yii\db\Connection::className());
    }
    
    /**
     * Return next job from the queue.
     * @return Job|boolean the job or false if not found.
     */
    protected function fetchJob()
    {
        //Avoiding multiple job.
        $transaction = $this->db->beginTransaction();
        $row = $this->fetchLatestRow();
        if ($row == false || !$this->flagRunningRow($row)) {
            $transaction->rollBack();
            return false;
        }
        $transaction->commit();
        
        $job = $this->deserialize($row['data']);
        $job->id = $row['id'];
        $job->header['timestamp'] = $row['timestamp'];
        
        return $job;
    }
    
    /**
     * Fetch latest ready job from the table.
     *
     * Due to the use of AUTO_INCREMENT ID, this will fetch the job with the
     * largest ID.
     *
     * @return array
     */
    protected function fetchLatestRow()
    {
        return (new \yii\db\Query())
                    ->select('*')
                    ->from($this->tableName)
                    ->where(['status' => self::STATUS_READY])
                    ->orderBy(['id' => SORT_DESC])
                    ->limit(1)
                    ->one($this->db);
    }
    
    /**
     * Flag a row as running. This will update the row ID and status if ready.
     *
     * @param array $row The row to update.
     * @return boolean Whether successful or not.
     */
    protected function flagRunningRow(array $row)
    {
        $updated = $this->db->createCommand()
                ->update(
                    $this->tableName,
                    ['status' => self::STATUS_ACTIVE],
                    [
                        'id' => $row['id'],
                        'status' => self::STATUS_READY,
                    ]
                )->execute();
        return $updated == 1;
    }

    /**
     * Post new job to the queue.  This contains implementation for database.
     *
     * @param Job $job The job to post.
     * @return boolean whether operation succeed.
     */
    protected function postJob(Job &$job)
    {
        return $this->db->createCommand()->insert($this->tableName, [
            'timestamp' => new \yii\db\Expression('NOW()'),
            'data' => $this->serialize($job),
        ])->execute() == 1;
    }

    /**
     * Delete the job. Override this for the queue implementation.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    public function deleteJob(Job $job)
    {
        if ($this->hardDelete) {
            return $this->db->createCommand()->delete($this->tableName, [
                'id' => $job->id,
            ])->execute() == 1;
        } else {
            return $this->db->createCommand()->update(
                $this->tableName,
                ['status' => self::STATUS_DELETED],
                ['id' => $job->id]
            )->execute() == 1;
        }
    }
    
    /**
     * Restore job from active to ready.
     *
     * @param Job $job The job to restore.
     * @return boolean whether the operation succeed.
     */
    public function restoreJob(Job $job)
    {
        return $this->db->createCommand()->update(
            $this->tableName,
            ['status' => self::STATUS_READY],
            ['id' => $job->id]
        )->execute() == 1;
    }
}

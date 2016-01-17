<?php

use UrbanIndo\Yii2\Queue\Queues\DbQueue;

class DbQueueTest extends TestCase
{
    
    public $counter = 0;
    
    protected function setUp()
    {
        parent::setUp();
        $this->counter = 0;
        $faker = Faker\Factory::create();
        $tableName = 'queue_' . $faker->firstNameMale;
        $this->mockApplication([
            'components' => [
                'db' => [
                    'class' => '\yii\db\Connection',
                    'dsn' => 'mysql:host=127.0.0.1;dbname=test',
                    'username' => 'test',
                    'password' => 'test',
                ],
                'queue' => [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\DbQueue',
                    'tableName' => $tableName,
                ]
            ]
        ]);
        
        Yii::$app->db->createCommand()
             ->createTable($tableName, [
                'id' => 'BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT',
                'status' => 'TINYINT NOT NULL DEFAULT 0',
                'timestamp' => 'DATETIME NOT NULL',
                'data' => 'LONGBLOB',
             ])->execute();
        
    }
    
    protected function tearDown()
    {
        parent::tearDown();
        Yii::$app->db->createCommand()
             ->dropTable(Yii::$app->queue->tableName);
    }
    
    /**
     * 
     * @return DbQueue
     */
    protected function getQueue()
    {
        return Yii::$app->queue;
    }
    
    protected function countTable($condition = null) {
        $query = (new yii\db\Query)
            ->select('COUNT(*)')
            ->from($this->getQueue()->tableName);
        if ($condition) {
            $query->where($condition);
        }
        return $query->scalar();
    }
    
    public function testPost()
    {
        $queue = $this->getQueue();
        $db = Yii::$app->db;
        $tableName = $queue->tableName;
        
        $this->assertEquals(0, $this->countTable());
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $this->assertEquals(1, $this->countTable());
        
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_READY]));
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 2;
        }]));
        
        $this->assertEquals(2, $this->countTable());
        
        $this->assertEquals(2, $this->countTable(['status' => DbQueue::STATUS_READY]));
    }
    
    public function testFetch()
    {
        $queue = $this->getQueue();
        $db = Yii::$app->db;
        $tableName = $queue->tableName;
        
        $this->assertEquals(0, $this->countTable());
        
        $job = $queue->fetch();
        
        $this->assertFalse($job);
        
        $this->assertEquals(0, $this->countTable(['status' => DbQueue::STATUS_ACTIVE]));
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $job = $queue->fetch();
        
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_ACTIVE]));
        
        $this->assertTrue($job instanceof UrbanIndo\Yii2\Queue\Job);
    }
    
    public function testHardDelete()
    {
        $queue = $this->getQueue();
        $db = Yii::$app->db;
        $tableName = $queue->tableName;
        
        $this->assertEquals(0, $this->countTable());
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $this->assertEquals(2, $this->countTable());
        
        $job = $queue->fetch();
        
        $this->assertEquals(2, $this->countTable());
        
        $queue->delete($job);
        
        $this->assertEquals(1, $this->countTable());
        
    }
    
    public function testSoftDelete()
    {
        $queue = $this->getQueue();
        $queue->hardDelete = false;
        $db = Yii::$app->db;
        $tableName = $queue->tableName;
        
        $this->assertEquals(0, $this->countTable());
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $queue->post(new UrbanIndo\Yii2\Queue\Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $this->assertEquals(2, $this->countTable(['status' => DbQueue::STATUS_READY]));
        $this->assertEquals(2, $this->countTable());
        
        $job = $queue->fetch();
        
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_READY]));
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_ACTIVE]));
        $this->assertEquals(2, $this->countTable());
        
        $queue->delete($job);
        
        $this->assertEquals(2, $this->countTable());
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_READY]));
        $this->assertEquals(0, $this->countTable(['status' => DbQueue::STATUS_ACTIVE]));
        $this->assertEquals(1, $this->countTable(['status' => DbQueue::STATUS_DELETED]));
        
    }
}

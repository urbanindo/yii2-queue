<?php

/**
 * SqsQueue class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue;

use \Aws\Sqs\SqsClient;

/**
 * SqsQueue provides queue for AWS SQS.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
class SqsQueue extends Queue {

    /**
     * The SQS url.
     * @var string
     */
    public $url;

    /**
     * The config for SqsClient.
     * 
     * This will be used for SqsClient::factory($config);
     * @var array
     */
    public $config = [];

    /**
     * Stores the SQS client.
     * @var \Aws\Sqs\SqsClient
     */
    private $_client;

    /**
     * Initialize the queue component.
     */
    public function init() {
        parent::init();
        $this->_client = SqsClient::factory($this->config);
    }

    /**
     * Return next job from the queue.
     * @return Job|boolean the job or false if not found.
     */
    public function fetch() {
        $message = $this->_client->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
        ]);
        if (isset($message['Messages']) && count($message['Messages']) > 0) {
            return $this->createJobFromMessage($message['Messages'][0]);
        } else {
            return false;
        }
    }

    /**
     * Create job from SQS message.
     * 
     * @param array $message the message.
     * @return \UrbanIndo\Yii2\Queue\Job
     */
    private function createJobFromMessage($message) {
        $job = $this->deserialize($message['Body']);
        $job->header['ReceiptHandle'] = $message['ReceiptHandle'];
        $job->id = $message['MessageId'];
        return $job;
    }

    /**
     * Post the job to queue.
     * 
     * @param Job $job the job model.
     * @return boolean whether operation succeed.
     */
    public function post(&$job) {
        $model = $this->_client->sendMessage([
            'QueueUrl' => $this->url,
            'MessageBody' => $this->serialize($job),
        ]);
        if (model !== null) {
            $job->id = $model[' MessageId'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the job from the queue.
     * 
     * @param Job $job
     * @return boolean whether the operation succeed.
     */
    public function delete($job) {
        if (!empty($job->header['ReceiptHandle'])) {
            $receiptHandle = $job->header['ReceiptHandle'];
            $response = $this->_client->deleteMessage([
                'QueueUrl' => $this->url,
                'ReceiptHandle' => $receiptHandle,
            ]);
            return $response !== null;
        } else {
            return false;
        }
    }

    /**
     * Returns the SQS client used.
     * 
     * @return \Aws\Sqs\SqsClient
     */
    public function getClient() {
        return $this->_client;
    }

}

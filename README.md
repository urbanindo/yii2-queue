# Queue Component for Yii2

This provides queue component for Yii2

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist urbanindo/yii2-queue "*"
```

or add

```
"urbanindo/yii2-queue": "*"
```

to the require section of your `composer.json` file.

## Setting Up

After the installation, first step is to set the console controller.

```php
return [
    // ...
    'controllerMap' => [
        'queue' => 'UrbanIndo\Yii2\Queue\Console\QueueController'
    ],
];
```

For the task worker, set a new module, e.g. `task` and declare it in the config.

```php
'modules' => [
    'task' => [
        'class' => 'app\modules\task\Module',
    ]
]
```

And then set the queue component. Don't forget to set the module name that runs
the task in the component. For example, queue using AWS SQS

```php
'components' => [
    'queue' => [
        'class' => 'UrbanIndo\Yii2\Queue\SqsQueue',
        'module' => 'task',
        'url' => 'https://sqs.ap-southeast-1.amazonaws.com/123456789012/queue',
            'config' => [
                'key' => 'AKIA1234567890123456',
                'secret' => '1234567890123456789012345678901234567890',
            'region' => 'ap-southeast-1',
        ],
    ]
]
```

## Creating A Worker

Creating a worker is just the same with creating console or web controller.
In the task module create a controller that extends `UrbanIndo\Yii2\Queue\Controller`

e.g.

```php
class FooController extends UrbanIndo\Yii2\Queue\Controller {
    
    public function actionBar($param1, $param2){
        echo $param1;
    }
}
```

To prevent the job got deleted from the queue, for example when the job is not
completed, return `false` in the action. The job will be run again the next
chance.

e.g.

```php
class FooController extends UrbanIndo\Yii2\Queue\Controller {
    
    public function actionBar($param1, $param2){
        try {
        } catch (\Exception $ex){
            \Yii::error('Ouch something just happened');
            return false;
        }
    }
}
```

## Running The Listener

To run the listener, run the console that set in the above config. If the
controller mapped as `queue` then run.

```
yii queue/listen
```

## Posting A Job

To post a job from source code, put something like this.

```php
use UrbanIndo\Yii2\Queue\Job;

$route = 'foo/bar';
$data = ['param1' => 'foo', 'param2' => 'bar'];
Yii::$app->queue->post(new Job($route, $data));
```

Job can also be posted from the console. The data in the second parameter is in
JSON string.

```
yii queue/post 'foo/bar' '{"param1": "foo", "param2": "bar"}'
```

Job can also be posted as anonymous function. Be careful using this.

```php
Yii::$app->queue->post(new Job(function(){
    echo 'Hello World!';
}));
```

## Road Map

- Add more queue provider such as MySQL, Redis, MemCache, IronMQ, RabbitMQ.
- Add priority queue.
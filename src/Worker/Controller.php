<?php

/**
 * Controller class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue\Worker;

use yii\base\InlineAction;

/**
 * Controller is base class for task controllers.
 * 
 * The usage is pretty much the same with the web or the console. The different
 * is that if the action return false, the job will not deleted. Otherwise
 * the job will be deleted from the queue.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
abstract class Controller extends \yii\base\Controller {
    
    /**
     * Stores all params even some elements are not assigned in the action method.
     * @var array
     */
    private $_params = [];
    
    /**
     * Returns action params from the queue job.
     * @return array
     */
    public function getActionParams() {
        return $this->_params;
    }

    /**
     * Binds the parameters to the action.
     * This method is invoked by [[Action]] when it begins to run with the given parameters.
     * This method will first bind the parameters with the [[options()|options]]
     * available to the action. It then validates the given arguments.
     * @param \yii\base\Action $action the action to be bound with parameters
     * @param array $params the parameters to be bound to the action
     * @return array the valid parameters that the action can run with.
     * @throws \yii\base\Exception if there are unknown options or missing arguments
     */
    public function bindActionParams($action, $params) {
        $this->_params = $params;
        
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = [];
        
        $missing = [];
        
        foreach ($method->getParameters() as $i => $param) {
            /* @var $param \ReflectionParameter */
            $name = $param->getName();
            if (isset($params[$name])) {
                if ($param->isArray()) {
                    $args[] = preg_split('/\s*,\s*/', $params[$name]);
                } else {
                    $args[] = $params[$name];
                }
            } else if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception(\Yii::t('yii',
                    'Missing required arguments: {params}',
                    ['params' => implode(', ', $missing)]));
        }

        return $args;
    }

}

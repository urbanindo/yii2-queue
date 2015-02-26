<?php

/**
 * DeferredEventInterface interface file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

/**
 * DeferredEventInterface provides method interface for handling the deferred
 * event.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
interface DeferredEventInterface {

    /**
     * @param string $event the name of the event.
     */
    public function handleDeferredEvent($eventName);
}

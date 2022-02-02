<?php

namespace App\Traits;

/**
 * Allows saving models without triggering observers
 */
trait CrudWithoutEvent
{
    /**
     * Save model without triggering observers on model
     */
    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
<?php

/**
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2014 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Workflow;

use Netzmacht\Workflow\Data\Entity;
use Netzmacht\Workflow\Factory\Event\CreateEntityEvent;
use Netzmacht\Workflow\Factory\Event\CreateFormEvent;
use Netzmacht\Workflow\Factory\Event\CreateManagerEvent;
use Netzmacht\Workflow\Factory\Event\CreateUserEvent;
use Netzmacht\Workflow\Form\Form;
use Netzmacht\Workflow\Security\User;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as EventDispatcher;

/**
 * Class Factory dispatches events which the implementation can subscribe to create the instances.
 *
 * @package Netzmacht\Workflow
 */
class Factory
{
    /**
     * The event dispatcher.
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Construct.
     *
     * @param EventDispatcher $eventDispatcher The event dispatcher.
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create a workflow manager.
     *
     * @param string      $providerName The provider name. Typically a database table name.
     * @param string|null $type         Optional workflow type limitation. Is passed to the factory event.
     *
     * @return Manager
     *
     * @throws RuntimeException If manager was not created.
     */
    public function createManager($providerName, $type = null)
    {
        $event = new CreateManagerEvent($providerName, $type);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return $this->guardCreated(
            $event->getManager(),
            sprintf('Could not create manager for provider "%s" and type "%s" ', $providerName, $type)
        );
    }


    /**
     * Create a new entity for a model.
     *
     * @param mixed       $model        Create an workflow entity.
     * @param string|null $providerName Provider name if it cannot be extracted from the model.
     *
     * @throws RuntimeException If no entity could be created.
     *
     * @return Entity
     */
    public function createEntity($model, $providerName = null)
    {
        $event = new CreateEntityEvent($model, $providerName);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return $this->guardCreated(
            $event->getEntity(),
            sprintf('Could not create entity for model "%s (%s)"', gettype($model), $providerName)
        );
    }

    /**
     * Create a form.
     *
     * @param string $type The form type.
     *
     * @return Form
     *
     * @throws RuntimeException If form was not created.
     */
    public function createForm($type)
    {
        $event = new CreateFormEvent($type);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return $this->guardCreated($event->getForm(), sprintf('Could not create form type "%s"', $type));
    }

    /**
     * Create user instance.
     *
     * @return User
     */
    public function createUser()
    {
        $event = new CreateUserEvent(new User());
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return $event->getUser();
    }

    /**
     * Guard that result was created.
     *
     * @param mixed  $result  Result of the event dispatched factory.
     * @param string $message The error message.
     *
     * @return mixed
     *
     * @throws RuntimeException If result was not created.
     */
    private function guardCreated($result, $message)
    {
        if (!$result) {
            throw new RuntimeException($message);
        }

        return $result;
    }
}
<?php

namespace App\StateMachines;

use App\Exceptions\InvalidRevisionState;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModelTransitionState implements ModelRevisionStateContract
{
    protected static string $stateColumn = 'state';

    public function __construct(public Model $model)
    {
        //
    }

    /**
     * @throws InvalidRevisionState
     */
    public function submit(): void
    {
        throw new InvalidRevisionState('Cannot submit a new revision');
    }

    /**
     * @throws InvalidRevisionState
     */
    public function approve(): void
    {
        throw new InvalidRevisionState('Cannot approve a new revision');
    }

    /**
     * @throws InvalidRevisionState
     */
    public function reject(): void
    {
        throw new InvalidRevisionState('Cannot reject a new revision');
    }
}

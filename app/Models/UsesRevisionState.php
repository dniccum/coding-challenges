<?php

namespace App\Models;

use App\Enums\RevisionState;
use App\Events\ModelTransitioned;
use App\Events\ModelTransitioning;
use App\Exceptions\InvalidRevisionState;
use App\StateMachines\ApprovedModelRevisionState;
use App\StateMachines\DraftModelRevisionState;
use App\StateMachines\RejectedModelRevisionState;
use App\StateMachines\SubmittedModelRevisionState;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait UsesRevisionState
{
    /**
     * Ensure the trait is only used on Eloquent models.
     */
    public static function bootUsesRevisionState(): void
    {
        if (! is_subclass_of(static::class, Model::class)) {
            throw new LogicException(static::class.' must extend '.Model::class.' to use '.self::class);
        }
    }

    /** @var array<RevisionState> */
    protected static array $states = [
        RevisionState::Draft,
        RevisionState::Submitted,
        RevisionState::Approved,
        RevisionState::Rejected,
    ];

    /**
     * @throws InvalidRevisionState
     */
    public function transitionTo(string|RevisionState $newState): ApprovedModelRevisionState|DraftModelRevisionState|RejectedModelRevisionState|SubmittedModelRevisionState
    {
        if (is_string($newState)) {
            try {
                $newState = RevisionState::from($newState);
            } catch (\Throwable $e) {
                throw new InvalidRevisionState("$newState is not a valid revision state.");
            }
        }

        event(new ModelTransitioning($this));

        $state = match ($newState) {
            RevisionState::Draft => new DraftModelRevisionState($this),
            RevisionState::Submitted => new SubmittedModelRevisionState($this),
            RevisionState::Approved => new ApprovedModelRevisionState($this),
            RevisionState::Rejected => new RejectedModelRevisionState($this),
            default => throw new InvalidRevisionState("$newState is not a valid revision state."),
        };

        event(new ModelTransitioned($this));

        return $state;
    }
}

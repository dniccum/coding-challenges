<?php

namespace App\StateMachines;

use App\Enums\RevisionState;

class SubmittedModelRevisionState extends BaseModelTransitionState
{
    public function approve(): void
    {
        $this->model->forceFill([self::$stateColumn => RevisionState::Approved])->save();
    }

    public function reject(): void
    {
        $this->model->forceFill([self::$stateColumn => RevisionState::Rejected])->save();
    }
}

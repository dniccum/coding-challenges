<?php

namespace App\StateMachines;

use App\Enums\RevisionState;

class DraftModelRevisionState extends BaseModelTransitionState
{
    public function submit(): void
    {
        $this->model->forceFill([self::$stateColumn => RevisionState::Submitted])->save();
    }
}

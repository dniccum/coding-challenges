<?php

namespace App\StateMachines;

interface ModelRevisionStateContract
{
    public function submit();

    public function approve();

    public function reject();
}

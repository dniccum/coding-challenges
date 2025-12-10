<?php

use App\Enums\RevisionState;
use App\Events\ModelTransitioned;
use App\Events\ModelTransitioning;
use App\Exceptions\InvalidRevisionState;
use App\Models\Appointment;
use App\StateMachines\ApprovedModelRevisionState;
use App\StateMachines\DraftModelRevisionState;
use App\StateMachines\RejectedModelRevisionState;
use App\StateMachines\SubmittedModelRevisionState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('transitions to Draft from enum and returns the correct state class', function () {
    $appointment = Appointment::factory()->create();

    Event::fake();

    $state = $appointment->transitionTo(RevisionState::Draft);

    expect($state)->toBeInstanceOf(DraftModelRevisionState::class);

    Event::assertDispatched(ModelTransitioning::class);
    Event::assertDispatched(ModelTransitioned::class);
});

it('transitions to Submitted from enum and returns the correct state class', function () {
    $appointment = Appointment::factory()->create();

    Event::fake();

    $state = $appointment->transitionTo(RevisionState::Submitted);

    expect($state)->toBeInstanceOf(SubmittedModelRevisionState::class);

    Event::assertDispatched(ModelTransitioning::class);
    Event::assertDispatched(ModelTransitioned::class);
});

it('transitions to Approved from enum and returns the correct state class', function () {
    $appointment = Appointment::factory()->create();

    Event::fake();

    $state = $appointment->transitionTo(RevisionState::Approved);

    expect($state)->toBeInstanceOf(ApprovedModelRevisionState::class);

    Event::assertDispatched(ModelTransitioning::class);
    Event::assertDispatched(ModelTransitioned::class);
});

it('transitions to Rejected from enum and returns the correct state class', function () {
    $appointment = Appointment::factory()->create();

    Event::fake();

    $state = $appointment->transitionTo(RevisionState::Rejected);

    expect($state)->toBeInstanceOf(RejectedModelRevisionState::class);

    Event::assertDispatched(ModelTransitioning::class);
    Event::assertDispatched(ModelTransitioned::class);
});

it('transitions correctly from string values and returns the correct state classes', function () {
    $appointment = Appointment::factory()->create();

    Event::fake();
    expect($appointment->transitionTo('draft'))->toBeInstanceOf(DraftModelRevisionState::class);
    expect($appointment->transitionTo('submitted'))->toBeInstanceOf(SubmittedModelRevisionState::class);
    expect($appointment->transitionTo('approved'))->toBeInstanceOf(ApprovedModelRevisionState::class);
    expect($appointment->transitionTo('rejected'))->toBeInstanceOf(RejectedModelRevisionState::class);

    Event::assertDispatched(ModelTransitioning::class);
    Event::assertDispatched(ModelTransitioned::class);
});

it('throws an exception when an invalid string state is provided', function () {
    $appointment = Appointment::factory()->create();

    $this->expectException(InvalidRevisionState::class);
    $this->expectExceptionMessage('invalid-state is not a valid revision state.');

    $appointment->transitionTo('invalid-state');
});

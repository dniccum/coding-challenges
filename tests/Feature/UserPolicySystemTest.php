<?php

use App\Exceptions\InvalidUserAction;
use App\Models\User;
use App\Models\UserActions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('the application throws an exception if the user action does not exist', function () {
    $user = User::factory()->staff()->create();
    expect($user->canPerformAction('submit_form'))->toBeTrue();
})->throws(InvalidUserAction::class);

it('a user can successfully submit a form', function () {
    $user = User::factory()->staff()->create([
        'email_verified_at' => now(),
    ]);
    UserActions::factory()->create([
        'action' => 'submit_form',
        'rules' => [
            [
                'field' => 'role',
                'operator' => '==',
                'value' => 'staff',
            ],
            [
                'field' => 'role',
                'operator' => '!=',
                'value' => null,
            ],
        ],
    ]);

    expect($user->canPerformAction('submit_form'))->toBeTrue();
});

it('a user cannot login if they do not have Jon in their name', function () {
    $user = User::factory()->staff()->create([
        'name' => 'Jan Doe',
    ]);
    UserActions::factory()->create([
        'action' => 'login',
        'rules' => [
            [
                'field' => 'role',
                'operator' => '==',
                'value' => 'staff',
            ],
            [
                'field' => 'name',
                'operator' => 'contains',
                'value' => 'Jon',
            ],
        ],
    ]);

    expect($user->canPerformAction('login'))->toBeFalse();
});

it('a user cannot login if they do not have the right first name', function () {
    $user = User::factory()->create([
        'name' => 'Sarah'
    ]);
    UserActions::factory()->create([
        'action' => 'login',
        'rules' => [
            [
                'field' => 'role',
                'operator' => '==',
                'value' => 'patient',
            ],
            [
                'field' => 'name',
                'operator' => 'in',
                'value' => [
                    'Jeff',
                    'Sally',
                    'Joseph'
                ],
            ],
        ],
    ]);

    expect($user->canPerformAction('login'))->toBeFalse();
});

it('a user cannot verify their email if they haven\'t registered in the last month', function () {
    $user = User::factory()->create([
        'created_at' => now()->subMonths(2),
    ]);
    UserActions::factory()->create([
        'action' => 'verify_email',
        'rules' => [
            [
                'field' => 'role',
                'operator' => '==',
                'value' => 'patient',
            ],
            [
                'field' => 'created_at',
                'operator' => '>',
                'value' => now()->subMonths(),
            ],
        ],
    ]);

    expect($user->canPerformAction('verify_email'))->toBeFalse();
});

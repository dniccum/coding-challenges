# PHP/Laravel Coding Challenges

This project/repository contains a series of challenges to evaluate experience, discernment, and overall coding skills.

## Challenges that are included

### Dynamic Rule Engine (Policy-like System)

**Problem:** Create a system that dynamically checks if a user can perform a given action based on JSON rules stored in the database.

Associated unit tests can be run with `php artisan test --filter UserPolicySystemTest`

#### Files to note

- `app/Library/RuleEvaluator.php`
- `tests/Feature/UserPolicySystemTest.php`

### Nested Eloquent Search Filter

**Problem:** Build a flexible filter class that accepts a JSON filter and applies it across multiple relationships.

Associated unit tests can be run with `php artisan test --filter NestedEloquentFilterTest`

#### Files to note

- `app/Models/Appointment.php`
- `app/Library/NestedEloquentFilter.php`
- `tests/Feature/AppointmentJsonFilterTest.php`

### State Machine for Models

**Problem:** Implement a lightweight StateMachine trait for Eloquent models.

Associated unit tests can be run with `php artisan test --filter StateMachineTest`

#### Files to note

- `app/Models/Appointment.php`
- `app/Models/UsesRevisionState.php`
- `app/StateMachines/ApprovedModelRevisionState.php`
- `app/StateMachines/BaseModelTransitionState.php`
- `app/StateMachines/DraftModelRevisionState.php`
- `app/StateMachines/ModelRevisionStateContract.php`
- `app/StateMachines/RejectedModelRevisionState.php`
- `app/StateMachines/SubmittedModelRevisionstate.php`
- `tests/Feature/StateMachineTest.php`

<?php

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds valid SQL request with valid JSON string', function () {
    $patients = Patient::factory()
        ->count(10)
        ->afterCreating(fn (Patient $patient) => Appointment::factory()->count(3)->confirmed()->create(['patient_id' => $patient->id]))
        ->create();

    $firstPatient = $patients->first();
    $query = Appointment::query()->jsonSearch('{"patient.name": "'.$firstPatient->name.'", "appointment.status": "confirmed", "patient.city": "'.$firstPatient->city.'"}');

    // Capture SQL and bindings for assertion
    $sql = $query->toSql();
    $bindings = $query->getBindings();

    // Basic shape assertions (stable across drivers/versions)
    expect($sql)->toContain('select * from "appointments"')
        ->and($sql)->toContain('where')
        ->and($sql)->toContain('exists')
        ->and($sql)->toContain('"patients"')
        ->and($bindings)->toBe([
            $firstPatient->name,
            'confirmed',
            $firstPatient->city,
        ]);

    // Assert the placeholders count and binding order

    // Assert full SQL exact string for sqlite grammar used in tests
    // NOTE: If this assertion ever fails due to framework SQL grammar changes,
    // update the expected string below by running this test and copying the produced $sql.
    $expected = 'select * from "appointments" where exists (select * from "patients" where "appointments"."patient_id" = "patients"."id" and "name" = ?) and "status" = ? and exists (select * from "patients" where "appointments"."patient_id" = "patients"."id" and "city" = ?)';

    expect($sql)->toBe($expected);
});


it('throws an error when a non-existent direct column is provided', function () {
    // The builder validates direct (non-relation) columns against the model's table schema
    // and should throw an InvalidSearchValue if the column does not exist.
    expect(fn () => Appointment::query()->jsonSearch('{"appointment.nonexistent": "value"}'))
        ->toThrow(\App\Exceptions\InvalidSearchValue::class);
});



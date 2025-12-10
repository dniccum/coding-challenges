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

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('select * from "appointments"')
        ->and($sql)->toContain('where')
        ->and($sql)->toContain('exists')
        ->and($sql)->toContain('"patients"')
        ->and($bindings)->toBe([
            $firstPatient->name,
            'confirmed',
            $firstPatient->city,
        ]);

    $expected = 'select * from "appointments" where exists (select * from "patients" where "appointments"."patient_id" = "patients"."id" and "name" = ?) and "status" = ? and exists (select * from "patients" where "appointments"."patient_id" = "patients"."id" and "city" = ?)';

    expect($sql)->toBe($expected);
});

it('throws an error when a non-existent direct column is provided', function () {
    expect(fn () => Appointment::query()->jsonSearch('{"appointment.nonexistent": "value"}'))
        ->toThrow(\App\Exceptions\InvalidSearchValue::class);
});

<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use App\Models\Role;
use App\Models\RoleAssignment;
use Illuminate\Database\Seeder;

class RoleAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $person = Person::where('identity', '2063531218')->firstOrFail();

        // تخصیص نقش مستقل از شعبه
        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person->id,
                'role_id' => 1,
                'membership_id' => null,
            ],
            [
                'is_active' => true,
            ],
        );

        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person->id,
                'role_id' => 2,
                'membership_id' => null,
            ],
            [
                'is_active' => true,
            ],
        );

        $person = Person::where('identity', '3500984886')->firstOrFail();

        // تخصیص نقش مستقل از شعبه
        RoleAssignment::updateOrCreate(
            [
                'person_id' => $person->id,
                'role_id' => 2,
                'membership_id' => null,
            ],
            [
                'is_active' => true,
            ],
        );

    }
}

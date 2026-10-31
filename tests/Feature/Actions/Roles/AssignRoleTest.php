<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Roles;

use App\Actions\Roles\AssignRole;
use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AssignRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_it_assigns_branch_role_to_active_membership(): void
    {
        $person = Person::factory()->create();
        $branch = Branch::factory()->create();

        $membership = Membership::factory()->create([
            'person_id' => $person->id,
            'branch_id' => $branch->id,
        ]);

        $role = Role::where('code', RoleCode::Instructor->value)->firstOrFail();

        $assignment = app(AssignRole::class)->handle(
            $person,
            $role,
            $membership,
        );

        $this->assertDatabaseHas('role_assignments', [
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => $membership->id,
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_branch_role_without_membership(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $person = Person::factory()->create();

        $role = Role::where('code', RoleCode::Instructor->value)->firstOrFail();

        app(AssignRole::class)->handle($person, $role);
    }

    public function test_it_rejects_membership_belonging_to_another_person(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $person = Person::factory()->create();
        $anotherPerson = Person::factory()->create();

        $membership = Membership::factory()->create([
            'person_id' => $anotherPerson->id,
        ]);

        $role = Role::where('code', RoleCode::Instructor->value)->firstOrFail();

        app(AssignRole::class)->handle($person, $role, $membership);
    }

    public function test_it_assigns_system_role_without_membership(): void
    {
        $person = Person::factory()->create();

        $role = Role::where('code', RoleCode::SuperAdmin->value)->firstOrFail();

        $assignment = app(AssignRole::class)->handle($person, $role);

        $this->assertDatabaseHas('role_assignments', [
            'id' => $assignment->id,
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_it_reactivates_existing_assignment_instead_of_creating_duplicate(): void
    {
        $person = Person::factory()->create();
        $membership = Membership::factory()->create([
            'person_id' => $person->id,
        ]);

        $role = Role::where('code', RoleCode::Instructor->value)->firstOrFail();

        $firstAssignment = app(AssignRole::class)->handle(
            $person,
            $role,
            $membership,
        );

        $firstAssignment->update(['is_active' => false]);

        $secondAssignment = app(AssignRole::class)->handle(
            $person,
            $role,
            $membership,
        );

        $this->assertSame($firstAssignment->id, $secondAssignment->id);
        $this->assertTrue($secondAssignment->is_active);

        $this->assertDatabaseCount('role_assignments', 1);
    }
}

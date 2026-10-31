<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Roles;

use App\Actions\Roles\RevokeRole;
use App\Enums\RoleCode;
use App\Models\Branch;
use App\Models\Membership;
use App\Models\Person;
use App\Models\Role;
use App\Models\RoleAssignment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RevokeRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_it_deactivates_branch_role_without_deleting_assignment(): void
    {
        $person = Person::factory()->create();
        $branch = Branch::factory()->create();

        $membership = Membership::factory()->create([
            'person_id' => $person->id,
            'branch_id' => $branch->id,
        ]);

        $role = Role::query()
            ->where('code', RoleCode::Instructor->value)
            ->firstOrFail();

        $assignment = RoleAssignment::query()->create([
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => $membership->id,
            'is_active' => true,
        ]);

        $result = app(RevokeRole::class)->handle(
            $person,
            $role,
            $membership->id,
        );

        $this->assertTrue($result);

        $this->assertDatabaseHas('role_assignments', [
            'id' => $assignment->id,
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => $membership->id,
            'is_active' => false,
        ]);
    }

    public function test_it_returns_false_when_assignment_is_already_inactive(): void
    {
        $person = Person::factory()->create();
        $branch = Branch::factory()->create();

        $membership = Membership::factory()->create([
            'person_id' => $person->id,
            'branch_id' => $branch->id,
        ]);

        $role = Role::query()
            ->where('code', RoleCode::Instructor->value)
            ->firstOrFail();

        RoleAssignment::query()->create([
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => $membership->id,
            'is_active' => false,
        ]);

        $result = app(RevokeRole::class)->handle(
            $person,
            $role,
            $membership->id,
        );

        $this->assertFalse($result);
    }

    public function test_it_rejects_branch_role_without_membership_id(): void
    {
        $person = Person::factory()->create();

        $role = Role::query()
            ->where('code', RoleCode::Instructor->value)
            ->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(RevokeRole::class)->handle($person, $role);
    }

    public function test_it_rejects_non_branch_role_with_membership_id(): void
    {
        $person = Person::factory()->create();

        $role = Role::query()
            ->where('code', RoleCode::SuperAdmin->value)
            ->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(RevokeRole::class)->handle($person, $role, 1);
    }

    public function test_it_does_not_revoke_another_persons_assignment(): void
    {
        $person = Person::factory()->create();
        $anotherPerson = Person::factory()->create();

        $role = Role::query()
            ->where('code', RoleCode::SuperAdmin->value)
            ->firstOrFail();

        $assignment = RoleAssignment::query()->create([
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => null,
            'is_active' => true,
        ]);

        $result = app(RevokeRole::class)->handle(
            $anotherPerson,
            $role,
        );

        $this->assertFalse($result);

        $this->assertDatabaseHas('role_assignments', [
            'id' => $assignment->id,
            'is_active' => true,
        ]);
    }

    public function test_it_deactivates_system_role_without_membership(): void
    {
        $person = Person::factory()->create();

        $role = Role::query()
            ->where('code', RoleCode::SuperAdmin->value)
            ->firstOrFail();

        $assignment = RoleAssignment::query()->create([
            'person_id' => $person->id,
            'role_id' => $role->id,
            'membership_id' => null,
            'is_active' => true,
        ]);

        $result = app(RevokeRole::class)->handle($person, $role);

        $this->assertTrue($result);

        $this->assertDatabaseHas('role_assignments', [
            'id' => $assignment->id,
            'is_active' => false,
        ]);
    }
}

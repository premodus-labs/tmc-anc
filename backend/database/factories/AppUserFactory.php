<?php

namespace Database\Factories;

use App\Models\AppUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppUser>
 */
class AppUserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'username' => fake()->unique()->userName(),
            'password_hash' => static::$password ??= 'password',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Give the user a role holding exactly these permission codes.
     */
    public function withPermissions(string ...$codes): static
    {
        return $this->afterCreating(function (AppUser $user) use ($codes) {
            $role = Role::create(['code' => 'role-'.Str::random(8), 'name' => 'Test role']);
            $role->permissions()->attach(
                collect($codes)->map(fn ($code) => Permission::firstOrCreate(['code' => $code])->id)
            );
            $user->roles()->attach($role);
        });
    }
}

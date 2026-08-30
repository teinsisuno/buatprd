<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WizardPrompt;
use App\Models\WizardPromptVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardPromptTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles & permissions + wizard prompts
        $this->seed(\Database\Seeders\RoleAndSuperadminSeeder::class);
        $this->seed(\Database\Seeders\WizardPromptSeeder::class);

        $this->superadmin = User::where('email', 'superadmin@buatprd.test')->first();
        if (! $this->superadmin) {
            $this->superadmin = User::factory()->create(['email' => 'superadmin@buatprd.test']);
            $this->superadmin->assignRole('superadmin');
        }
        $this->admin = User::where('email', 'admin@buatprd.test')->first();
        if (! $this->admin) {
            $this->admin = User::factory()->create(['email' => 'admin@buatprd.test']);
            $this->admin->assignRole('admin');
        }
        // ensure verified
        $this->superadmin->email_verified_at = now();
        $this->superadmin->save();
        $this->admin->email_verified_at = now();
        $this->admin->save();
    }

    public function test_superadmin_can_view_index(): void
    {
        $res = $this->actingAs($this->superadmin)->get(route('admin.wizard-prompts.index'));
        $res->assertOk();
        $res->assertInertia(fn($a) => $a->component('Admin/WizardPrompts/Index'));
    }

    public function test_admin_cannot_view_index(): void
    {
        $res = $this->actingAs($this->admin)->get(route('admin.wizard-prompts.index'));
        $res->assertForbidden();
    }

    public function test_update_with_unknown_var_rejected(): void
    {
        $res = $this->actingAs($this->superadmin)->put(route('admin.wizard-prompts.update', 1), [
            'name' => 'Test',
            'system' => 'System with {{unknown_var}} and valid length more than 20 chars ok',
            'user_template' => 'Template {{title}} {{unknown_var}} plus enough length to pass min 20 chars',
            'json_schema' => null,
            'is_active' => true,
        ]);
        $res->assertSessionHasErrors('user_template');
    }

    public function test_update_and_version_and_rollback(): void
    {
        $prompt = WizardPrompt::where('step', 2)->first();
        $origName = $prompt->name;
        $origSystem = $prompt->system;

        // update
        $res = $this->actingAs($this->superadmin)->put(route('admin.wizard-prompts.update', 2), [
            'name' => 'Updated L2',
            'system' => 'Kamu Metric Suggester UPDATED — must be at least 20 chars long here',
            'user_template' => 'KONTEKS: {{title}} dan {{description}} plus {{history}} untuk test L2 valid',
            'json_schema' => '{"type":"object"}',
            'is_active' => true,
        ]);
        $res->assertRedirect();

        $prompt->refresh();
        $this->assertEquals('Updated L2', $prompt->name);
        $count = WizardPromptVersion::where('wizard_prompt_id', $prompt->id)->count();
        $this->assertGreaterThanOrEqual(2, $count);

        // versions endpoint
        $res2 = $this->actingAs($this->superadmin)->get(route('admin.wizard-prompts.versions', 2));
        $res2->assertOk();
        $res2->assertJsonStructure(['versions']);

        // rollback to v1
        $res3 = $this->actingAs($this->superadmin)->post(route('admin.wizard-prompts.rollback', [2, 1]));
        $res3->assertRedirect();
        $prompt->refresh();
        $this->assertEquals($origName, $prompt->name);
    }

    public function test_invalid_json_rejected(): void
    {
        $res = $this->actingAs($this->superadmin)->put(route('admin.wizard-prompts.update', 3), [
            'name' => 'Test',
            'system' => 'System valid with more than 20 characters for test',
            'user_template' => 'Template {{title}} dengan panjang cukup untuk validasi min 20 chars',
            'json_schema' => '{invalid json',
            'is_active' => true,
        ]);
        $res->assertSessionHasErrors('json_schema');
    }

    public function test_prompt_resolver_cache_and_fallback(): void
    {
        $this->assertEquals('db', \App\Services\PromptResolver::get(1)['source']);
        $p = WizardPrompt::where('step', 1)->first();
        $orig = $p->is_active;
        $p->update(['is_active' => false]);
        \App\Services\PromptResolver::forget(1);
        $this->assertEquals('config', \App\Services\PromptResolver::get(1)['source']);
        $p->update(['is_active' => $orig]);
        \App\Services\PromptResolver::forget(1);
        $this->assertEquals('db', \App\Services\PromptResolver::get(1)['source']);
    }
}

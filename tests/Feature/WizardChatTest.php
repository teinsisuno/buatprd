<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Project;
use App\Models\AiProvider;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MembershipTierSeeder::class);
        $this->seed(\Database\Seeders\RoleAndSuperadminSeeder::class);
        $this->seed(\Database\Seeders\WizardPromptSeeder::class);
        // mock provider
        AiProvider::create([
            'provider'=>'openai','label'=>'Mock','api_key'=>'sk-test-123','base_url'=>'https://api.openai.com/v1',
            'enabled_models'=>['gpt-4o-mini'],'available_models'=>[['id'=>'gpt-4o-mini','label'=>'gpt-4o-mini']],'is_active'=>true
        ]);
        Setting::set('ai_default_model','openai:gpt-4o-mini','ai','test','text');
    }

    public function test_wizard_l1_l3_flow(): void
    {
        $member = User::where('email','member@buatprd.test')->first();
        $member->email_verified_at = now(); $member->save();
        $membership = $member->activeMembership()->first();
        $quotaBefore = $membership->ai_quota_used;
        // create project
        $res = $this->actingAs($member)->post(route('member.projects.store'), ['title'=>'TOYAA Test','description'=>'test']);
        $res->assertRedirect();
        $project = Project::where('user_id',$member->id)->latest()->first();
        $this->assertNotNull($project);
        $this->assertEquals(8, $project->sections()->count());
        $this->assertEquals(1, $project->current_step);

        // L1 chat with clone URL
        $res2 = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,1]), [
            'message' => 'Aku mau bikin TOYAA seperti https://meterpams.com tapi untuk UMKM — bedanya pricing murah, target toko kelontong',
            'model' => 'openai:gpt-4o-mini',
        ]);
        $res2->assertOk();
        $data2 = $res2->json();
        $this->assertArrayHasKey('history', $data2);
        $this->assertArrayHasKey('final_output', $data2);
        $this->assertCount(2, $data2['history']); // user + assistant
        // check that final_output has expected keys (mock will give them)
        // second chat L1 to test history persistence
        $res3 = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,1]), [
            'message' => 'Target pasarnya warung kelontong Jawa Tengah, pricing 25k/bulan flat, tanpa QR',
        ]);
        $res3->assertOk();
        $data3 = $res3->json();
        $this->assertCount(4, $data3['history']); // 2 rounds
        // quota should have deducted 2
        $membership->refresh();
        $this->assertEquals($quotaBefore+2, $membership->ai_quota_used);

        // L2 chat
        $res4 = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,2]), [
            'message' => 'Target industri retail UMKM, 50 toko, activation 60% minggu pertama',
            'industry' => 'Retail / UMKM',
        ]);
        $res4->assertOk();
        $data4 = $res4->json();
        $this->assertArrayHasKey('final_output', $data4);

        // L3 chat
        $res5 = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,3]), [
            'message' => 'Butuh modul kasir offline, rekap harian, cetak struk bluetooth',
            'stack' => 'Laravel + Vue + MySQL',
        ]);
        $res5->assertOk();
        $data5 = $res5->json();
        $this->assertArrayHasKey('final_output', $data5);
        $this->assertArrayHasKey('architecture', $data5['final_output']);
        $this->assertArrayHasKey('modules', $data5['final_output']);
        $this->assertArrayHasKey('folder_structure', $data5['final_output']);

        // Verify project progress updated (should be 3/8 = 37%)
        $project->refresh();
        $this->assertGreaterThanOrEqual(37, $project->progress);

        // Test attachment handling pdf/txt
        $tmpFile = tempnam(sys_get_temp_dir(),'test');
        file_put_contents($tmpFile, "isi file txt untuk wizard test");
        $upload = new \Illuminate\Http\UploadedFile($tmpFile, 'brief.txt', 'text/plain', null, true);
        $res6 = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,1]), [
            'message' => 'Tambah brief via file',
            'attachments' => [$upload],
        ]);
        $res6->assertOk();
        unlink($tmpFile);
    }

    public function test_quota_exceeded_returns_403(): void
    {
        $member = User::where('email','member@buatprd.test')->first();
        $member->email_verified_at = now(); $member->save();
        $membership = $member->activeMembership()->first();
        // exhaust quota
        $membership->update(['ai_quota_used'=> $membership->ai_quota_total, 'credit_balance'=>0]);
        $project = Project::create(['user_id'=>$member->id,'title'=>'Quota Test','slug'=>'quota-'.rand(),'description'=>'test','status'=>'draft','current_step'=>1,'progress'=>0]);
        foreach (\App\Models\ProjectSection::STEP_TITLES as $step=>$title) {
            \App\Models\ProjectSection::create(['project_id'=>$project->id,'step'=>$step,'title'=>$title,'content'=>['history'=>[],'final_output'=>null]]);
        }
        $res = $this->actingAs($member)->post(route('member.wizard.chat', [$project->id,1]), ['message'=>'test quota']);
        $res->assertStatus(403);
        $res->assertJson(['code'=>'quota_exceeded']);
    }
}

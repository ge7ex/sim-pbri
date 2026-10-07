<?php

namespace Tests\Feature\Booking;

use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\SimResource\Services\SaveResourceWithImage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RoomImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('room-images');
    }

    private function actor(UserRole $role = UserRole::Admin, ?int $collegeId = null): User
    {
        return User::factory()->create(['college_id' => $collegeId ?? College::factory()->create()->id, 'role' => $role->value]);
    }

    private function payload(): array
    {
        return ['name' => 'Photo room', 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1,
            'is_exclusive' => true, 'capacity' => 20];
    }

    private function upload(User $admin): SimResource
    {
        $this->actingAs($admin)->post('/app/resources', [...$this->payload(), 'image' => UploadedFile::fake()->image('room.png', 2000, 1000)])
            ->assertRedirect()->assertSessionHasNoErrors();

        return SimResource::where('name', 'Photo room')->sole();
    }

    public function test_upload_reencodes_resizes_hides_path_and_records_actor(): void
    {
        $admin = $this->actor();
        $room = $this->upload($admin);
        Storage::disk('room-images')->assertExists($room->image_path);
        $bytes = Storage::disk('room-images')->get($room->image_path);
        $size = getimagesizefromstring($bytes);
        $this->assertSame([1600, 800], [$size[0], $size[1]]);
        $this->assertSame(IMAGETYPE_JPEG, $size[2]);
        $this->assertArrayNotHasKey('image_path', $room->toArray());
        $this->assertStringContainsString('/app/resources/'.$room->id.'/image?v=', $room->image_url);
        $this->assertDatabaseHas('sim_resource_image_changes', ['sim_resource_id' => $room->id, 'actor_user_id' => $admin->id, 'action' => 'uploaded']);
    }

    public function test_actual_png_jpeg_and_webp_bytes_are_supported(): void
    {
        $admin = $this->actor();
        foreach (['png' => 'imagepng', 'jpg' => 'imagejpeg', 'webp' => 'imagewebp'] as $extension => $encode) {
            $image = imagecreatetruecolor(32, 16);
            ob_start();
            $encode($image);
            $bytes = ob_get_clean();
            imagedestroy($image);
            $this->actingAs($admin)->post('/app/resources', [
                ...$this->payload(), 'name' => 'format-'.$extension,
                'image' => UploadedFile::fake()->createWithContent('photo.'.$extension, $bytes),
            ])->assertRedirect()->assertSessionHasNoErrors();
            $room = SimResource::where('name', 'format-'.$extension)->sole();
            $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring(Storage::disk('room-images')->get($room->image_path))[2]);
        }
    }

    public function test_image_access_requires_login_and_same_college_including_lecturer(): void
    {
        $admin = $this->actor();
        $room = $this->upload($admin);
        foreach ([UserRole::Admin, UserRole::Staff, UserRole::Lecturer] as $role) {
            $this->actingAs($this->actor($role, $admin->college_id))->get($room->image_url)->assertOk()
                ->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->actingAs($this->actor())->get($room->image_url)->assertForbidden();
        auth()->forgetGuards();
        $this->get($room->image_url)->assertRedirect('/login');
    }

    public function test_multipart_replace_remove_and_metadata_update_preserve_expected_photo(): void
    {
        $admin = $this->actor();
        $room = $this->upload($admin);
        $oldUrl = $room->image_url;
        $this->actingAs($admin)->put('/app/resources/'.$room->id, [...$this->payload(), 'status' => 'maintenance'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($oldUrl, $room->refresh()->image_url);
        $this->post('/app/resources/'.$room->id, [...$this->payload(), '_method' => 'put', 'image' => UploadedFile::fake()->image('new.webp')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotSame($oldUrl, $room->refresh()->image_url);
        $this->assertDatabaseHas('sim_resource_image_changes', ['action' => 'replaced', 'actor_user_id' => $admin->id]);
        $this->post('/app/resources/'.$room->id, [...$this->payload(), '_method' => 'put', 'remove_image' => true])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($room->refresh()->image_path);
        $this->get('/app/resources/'.$room->id.'/image')->assertNotFound();
        $this->assertDatabaseHas('sim_resource_image_changes', ['action' => 'removed', 'actor_user_id' => $admin->id]);
    }

    public function test_unsafe_invalid_and_excessive_files_are_rejected_without_writes(): void
    {
        $admin = $this->actor();
        $files = [
            UploadedFile::fake()->createWithContent('bad.jpg', '<?php echo "unsafe";'),
            UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('bad.gif'),
            UploadedFile::fake()->image('wide.png', 3001, 1),
            UploadedFile::fake()->image('large.jpg')->size(5121),
        ];
        foreach ($files as $file) {
            $this->actingAs($admin)->post('/app/resources', [...$this->payload(), 'image' => $file])->assertSessionHasErrors('image');
        }
        $this->assertDatabaseCount('sim_resources', 0);
        $this->assertDatabaseCount('sim_resource_image_changes', 0);
        $this->assertSame([], Storage::disk('room-images')->allFiles());
    }

    public function test_trailing_payload_is_removed_and_user_cannot_supply_storage_path(): void
    {
        $file = UploadedFile::fake()->image('payload.jpg');
        $file = UploadedFile::fake()->createWithContent('payload.jpg', file_get_contents($file->getRealPath()).'<?php echo "marker_payload";');
        $admin = $this->actor();
        $this->actingAs($admin)->post('/app/resources', [...$this->payload(), 'image' => $file, 'image_path' => '../secret'])->assertSessionHasNoErrors();
        $room = SimResource::sole();
        $this->assertStringNotContainsString('marker_payload', Storage::disk('room-images')->get($room->image_path));
        $this->assertNotSame('../secret', $room->image_path);
        $room->update(['image_path' => '../secret']);
        $this->get('/app/resources/'.$room->id.'/image')->assertNotFound();
    }

    public function test_staff_foreign_admin_and_equipment_cannot_upload_and_conflicting_operation_is_rejected(): void
    {
        $admin = $this->actor();
        $room = $this->upload($admin);
        foreach ([$this->actor(UserRole::Staff, $admin->college_id), $this->actor()] as $actor) {
            $this->actingAs($actor)->post('/app/resources/'.$room->id, [...$this->payload(), '_method' => 'put', 'image' => UploadedFile::fake()->image('room.png')])->assertForbidden();
        }
        $this->actingAs($admin)->post('/app/resources', [...$this->payload(), 'kind' => 'equipment', 'image' => UploadedFile::fake()->image('equipment.png')])->assertSessionHasErrors('image');
        $this->post('/app/resources/'.$room->id, [...$this->payload(), '_method' => 'put', 'remove_image' => true, 'image' => UploadedFile::fake()->image('room.png')])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('sim_resource_image_changes', 1);
    }

    public function test_database_failure_cleans_new_file_and_rolls_back_resource(): void
    {
        $admin = $this->actor();
        // Force audit failure after the resource write, exercising transaction rollback and file cleanup.
        DB::statement('DROP TABLE sim_resource_image_changes');
        try {
            app(SaveResourceWithImage::class)->execute([...$this->payload(), 'image' => UploadedFile::fake()->image('room.png')], $admin);
            $this->fail('Expected audit persistence failure');
        } catch (QueryException) {
            $this->assertDatabaseCount('sim_resources', 0);
            $this->assertSame([], Storage::disk('room-images')->allFiles());
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_publish_edit_and_unpublish_an_announcement(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['title' => 'Market hours', 'message' => 'We open at 7 AM.', 'status' => 'Draft', 'is_pinned' => 1];

        $this->actingAs($admin)->post(route('announcements.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('announcements'));
        $post = Announcement::firstOrFail();
        $this->assertDatabaseHas('announcements', ['id' => $post->id, 'user_id' => $admin->id, 'published_at' => null]);
        $this->get(route('announcements.edit', $post))->assertOk()->assertSee('Market hours');

        $this->put(route('announcements.update', $post), [...$data, 'status' => 'Published', 'title' => 'Updated hours'])
            ->assertSessionHasNoErrors()->assertRedirect(route('announcements'));
        $this->assertDatabaseHas('announcements', ['id' => $post->id, 'title' => 'Updated hours', 'published_at' => now()->toDateTimeString(), 'is_pinned' => 1]);

        $this->patch(route('announcements.unpublish', $post))->assertRedirect(route('announcements'));
        $this->assertDatabaseHas('announcements', ['id' => $post->id, 'published_at' => null]);
    }

    public function test_vendor_dashboard_only_shows_current_posts_pinned_first_and_escapes_messages(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        Announcement::factory()->for($admin)->published()->create(['title' => 'Recent notice']);
        Announcement::factory()->for($admin)->published()->create([
            'title' => 'Important notice', 'is_pinned' => true,
            'published_at' => now()->subDay(), 'message' => '<script>alert(1)</script>',
        ]);
        Announcement::factory()->for($admin)->create(['title' => 'Private draft']);
        Announcement::factory()->for($admin)->published()->create(['title' => 'Expired notice', 'expires_at' => now()]);
        Announcement::factory()->for($admin)->create(['title' => 'Future notice', 'published_at' => now()->addDay()]);

        $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertOk()
            ->assertSeeInOrder(['Important notice', 'Recent notice'])
            ->assertDontSee('Private draft')->assertDontSee('Expired notice')->assertDontSee('Future notice')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_vendors_cannot_manage_announcements(): void
    {
        $post = Announcement::factory()->published()->create();
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor)->get(route('announcements'))->assertForbidden();
        $this->get(route('announcements.edit', $post))->assertForbidden();
        $this->post(route('announcements.store'), ['title' => 'Unauthorized'])->assertForbidden();
        $this->put(route('announcements.update', $post), ['title' => 'Unauthorized'])->assertForbidden();
        $this->patch(route('announcements.unpublish', $post))->assertForbidden();
        $this->assertDatabaseHas('announcements', ['id' => $post->id, 'title' => $post->title, 'published_at' => $post->published_at->toDateTimeString()]);
        $this->assertDatabaseCount('announcements', 1);
    }

    public function test_invalid_announcement_is_rejected_without_creating_a_post(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => '', 'message' => '', 'status' => 'Invalid',
            'expires_at' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors(['title', 'message', 'status', 'expires_at']);
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_expiry_remains_visible_through_the_selected_day(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('announcements.store'), [
            'title' => 'Today only', 'message' => 'Market meeting.', 'status' => 'Published',
            'expires_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, Announcement::published()->count());
        $this->travelTo(today()->addDay()->startOfDay());
        $this->assertSame(0, Announcement::published()->count());
    }
}

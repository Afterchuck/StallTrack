<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\SupportRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_notifies_admins_and_admin_reply_reaches_the_vendor(): void
    {
        $admins = User::factory()->count(2)->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendor']);
        Vendor::factory()->for($user)->create();
        $this->actingAs($user)->post(route('vendor.support.store'), [
            'topic' => 'Billing & payments', 'subject' => '<script>subject</script>',
            'message' => '<script>message</script>', 'status' => 'Resolved',
            'admin_reply' => 'Forged reply',
        ])->assertSessionHasNoErrors();
        $supportRequest = SupportRequest::firstOrFail();
        $this->assertSame('Open', $supportRequest->status);
        $this->assertNull($supportRequest->admin_reply);
        $this->assertSame(0, $user->notifications()->count());
        foreach ($admins as $admin) {
            $this->assertSame(1, $admin->unreadNotifications()->count());
        }

        $admin = $admins->first();
        $notice = $admin->notifications()->firstOrFail();
        $this->actingAs($admin)->get(route('admin.notifications'))
            ->assertSee('Notifications, 1 unread')->assertSee('<script>subject</script>')
            ->assertDontSee('<script>subject</script>', false);
        $this->assertNull($notice->fresh()->read_at);
        $this->post(route('admin.notifications.read', $notice->id))
            ->assertRedirect(route('admin.support.show', $supportRequest));
        $this->assertNotNull($notice->fresh()->read_at);
        $this->assertSame(1, $admins->last()->unreadNotifications()->count());
        $this->get(route('admin.support.show', $supportRequest))
            ->assertSee('Notifications, 0 unread')->assertSee('<script>message</script>')
            ->assertDontSee('<script>message</script>', false);
        $this->patch(route('admin.support.update', $supportRequest), [
            'admin_reply' => '<script>reply</script>', 'status' => 'Resolved',
            'subject' => 'Tampered subject',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.support.show', $supportRequest));
        $this->assertNotNull($supportRequest->fresh()->replied_at);
        $this->assertSame('<script>subject</script>', $supportRequest->fresh()->subject);
        $this->actingAs($user)->get(route('vendor.support'))->assertSee('Resolved')
            ->assertSee('<script>reply</script>')->assertDontSee('<script>reply</script>', false);
    }

    public function test_support_admin_routes_require_admin_and_notification_ownership(): void
    {
        $supportRequest = SupportRequest::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new SupportRequestSubmitted($supportRequest));
        $notice = $admin->notifications()->firstOrFail();
        $this->get(route('admin.notifications'))->assertRedirect(route('login'));
        $this->get(route('admin.support.show', $supportRequest))->assertRedirect(route('login'));
        $this->patch(route('admin.support.update', $supportRequest), [])->assertRedirect(route('login'));
        $this->post(route('admin.notifications.read', $notice->id))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'vendor']));
        $this->get(route('admin.notifications'))->assertForbidden();
        $this->get(route('admin.support.show', $supportRequest))->assertForbidden();
        $this->patch(route('admin.support.update', $supportRequest), [])->assertForbidden();
        $this->post(route('admin.notifications.read', $notice->id))->assertForbidden();
        $this->actingAs($otherAdmin)->post(route('admin.notifications.read', $notice->id))->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);
        $this->assertNull($supportRequest->fresh()->admin_reply);
    }

    public function test_admin_can_find_older_requests_and_invalid_replies_are_not_saved(): void
    {
        $supportRequest = SupportRequest::factory()->create(['subject' => 'Older request']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.notifications'))->assertSee('Older request')->assertSee('No support notifications yet.');
        $this->patch(route('admin.support.update', $supportRequest), [])
            ->assertSessionHasErrors(['admin_reply', 'status']);
        $this->patch(route('admin.support.update', $supportRequest), [
            'admin_reply' => str_repeat('x', 5001), 'status' => 'Invalid',
        ])->assertSessionHasErrors(['admin_reply', 'status']);
        $this->assertNull($supportRequest->fresh()->admin_reply);
        $this->assertSame('Open', $supportRequest->fresh()->status);
        $this->get(route('admin.support.show', 999999))->assertNotFound();
    }

    public function test_invalid_vendor_submission_does_not_notify_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendor']);
        Vendor::factory()->for($user)->create();
        $this->actingAs($user)->post(route('vendor.support.store'), [])
            ->assertSessionHasErrors(['topic', 'subject', 'message']);
        $this->assertDatabaseCount('support_requests', 0);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_vendor_can_view_and_submit_a_support_request(): void
    {
        $user = User::factory()->create(['role' => 'vendor']);
        $vendor = Vendor::factory()->for($user)->create();

        $this->actingAs($user)->get(route('vendor.support'))
            ->assertOk()
            ->assertSee('Help &amp; Support', false)
            ->assertSee('No support requests yet');

        $this->post(route('vendor.support.store'), [
            'topic' => 'Billing & payments',
            'subject' => 'Receipt is missing',
            'message' => 'My latest payment receipt is not visible.',
        ])->assertSessionHasNoErrors()->assertRedirect(route('vendor.support'));

        $request = SupportRequest::firstOrFail();
        $this->assertSame($vendor->id, $request->vendor_id);
        $this->assertSame('Open', $request->status);

        $this->get(route('vendor.support'))
            ->assertSee('Receipt is missing')
            ->assertSee('Billing &amp; payments', false)
            ->assertSee('Open');
    }

    public function test_vendor_cannot_view_another_vendors_support_requests(): void
    {
        $owner = Vendor::factory()->create();
        SupportRequest::factory()->for($owner)->create(['subject' => 'Private request']);
        $otherUser = User::factory()->create(['role' => 'vendor']);
        Vendor::factory()->for($otherUser)->create();

        $this->actingAs($otherUser)->get(route('vendor.support'))
            ->assertOk()
            ->assertDontSee('Private request');
    }

    public function test_guests_cannot_access_vendor_support(): void
    {
        $this->get(route('vendor.support'))->assertRedirect(route('login'));
        $this->post(route('vendor.support.store'), [])->assertRedirect(route('login'));
    }
}

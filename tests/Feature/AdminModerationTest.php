<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AreaSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->admin = User::factory()->admin()->create();

        Filament::setCurrentPanel('admin');
    }

    public function test_admin_can_approve_pending_event(): void
    {
        $event = Event::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListEvents::class, ['activeTab' => 'pending'])
            ->callTableAction('approve', $event);

        $fresh = $event->fresh();

        $this->assertSame(EventStatus::Approved, $fresh->status);
        $this->assertSame($this->admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_rejection_requires_reason_and_stores_it(): void
    {
        $event = Event::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListEvents::class, ['activeTab' => 'pending'])
            ->callTableAction('reject', $event, data: ['rejection_reason' => 'التاريخ متعارض مع فعالية أخرى بنفس المركز'])
            ->assertHasNoTableActionErrors();

        $fresh = $event->fresh();

        $this->assertSame(EventStatus::Rejected, $fresh->status);
        $this->assertSame('التاريخ متعارض مع فعالية أخرى بنفس المركز', $fresh->rejection_reason);
    }

    public function test_rejection_without_reason_fails_validation(): void
    {
        $event = Event::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListEvents::class, ['activeTab' => 'pending'])
            ->callTableAction('reject', $event, data: ['rejection_reason' => ''])
            ->assertHasTableActionErrors(['rejection_reason' => 'required']);

        $this->assertSame(EventStatus::Pending, $event->fresh()->status);
    }
}

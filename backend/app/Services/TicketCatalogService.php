<?php

namespace App\Services;

use App\Models\Ticket;
use App\Repository\TicketCatalogRepository;
use Illuminate\Support\Facades\Cache;

class TicketCatalogService
{
    public const OPTIONS_CACHE_KEY = 'ticket_catalog_options';

    public function __construct(
        private readonly TicketCatalogRepository $catalog,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return Cache::remember(self::OPTIONS_CACHE_KEY, now()->addMinutes(15), fn () => [
            'departments' => $this->catalog->activeDepartments(),
            'categories' => $this->catalog->activeCategories(),
            'priorities' => $this->catalog->activePriorities(),
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public static function clearOptionsCache(): void
    {
        Cache::forget(self::OPTIONS_CACHE_KEY);
    }
}

<?php

namespace Flute\Modules\Announcement\Services;

use DateTimeImmutable;
use Flute\Modules\Announcement\database\Entities\Announcement;

class AnnouncementService
{
    public const CACHE_KEY = 'flute.module.announcements';

    protected const CACHE_TIME = 60 * 60;

    protected array $cachedItems;

    public function __construct()
    {
        $this->cachedItems = cache()->callback(
            self::CACHE_KEY,
            fn() => $this->getActiveAnnouncements(),
            self::CACHE_TIME,
        );
    }

    /**
     * Returns all visible announcements
     */
    public function all(): array
    {
        $now = new DateTimeImmutable();

        return array_filter($this->cachedItems, static function ($item) use ($now) {
            if (!$item['isActive']) {
                return false;
            }

            if ($item['startAt'] !== null && $now < $item['startAt']) {
                return false;
            }

            return !( $item['endAt'] !== null && $now > $item['endAt'] );
        });
    }

    /**
     * Check if announcement is dismissed by user
     */
    public function isDismissed(int $id): bool
    {
        $dismissed = cookie()->get('dismissed_announcements', '');
        $dismissedIds = $dismissed ? explode(',', $dismissed) : [];

        return in_array((string) $id, $dismissedIds, true);
    }

    /**
     * Get visible announcements (excluding dismissed)
     */
    public function getVisible(): array
    {
        $isAuthed = user()->isLoggedIn();

        return array_filter($this->all(), function ($item) use ($isAuthed) {
            if ($this->isDismissed($item['id'])) {
                return false;
            }

            $target = $item['target'] ?? 'all';

            if ($target === 'guests' && $isAuthed) {
                return false;
            }

            if ($target === 'auth' && !$isAuthed) {
                return false;
            }

            return true;
        });
    }

    /**
     * Format announcement entity to array
     */
    public function format(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,
            'content' => $announcement->content,
            'icon' => $announcement->icon,
            'url' => $announcement->url,
            'buttonText' => $announcement->buttonText,
            'buttonUrl' => $announcement->buttonUrl,
            'buttonIcon' => $announcement->buttonIcon,
            'buttonNewTab' => $announcement->buttonNewTab,
            'type' => $announcement->type,
            'target' => $announcement->target,
            'closable' => $announcement->closable,
            'isActive' => $announcement->isActive,
            'position' => $announcement->position,
            'startAt' => $announcement->startAt,
            'endAt' => $announcement->endAt,
        ];
    }

    /**
     * Clear the announcements cache
     */
    public function clearCache(): void
    {
        cache()->delete(self::CACHE_KEY);
        $this->cachedItems = $this->getActiveAnnouncements();
    }

    /**
     * Get all active announcements from database
     */
    protected function getActiveAnnouncements(): array
    {
        $announcements = Announcement::query()
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->fetchAll();

        $result = [];
        foreach ($announcements as $announcement) {
            $result[] = $this->format($announcement);
        }

        return $result;
    }
}

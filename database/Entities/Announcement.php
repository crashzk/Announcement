<?php

namespace Flute\Modules\Announcement\database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity]
#[Table(indexes: [
    new Index(columns: ['is_active']),
    new Index(columns: ['position']),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class Announcement extends ActiveRecord
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'string', nullable: true)]
    public ?string $buttonText = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $buttonUrl = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $buttonIcon = null;

    #[Column(type: 'boolean', default: false)]
    public bool $buttonNewTab = false;

    #[Column(type: 'string', nullable: true)]
    public ?string $icon = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $url = null;

    #[Column(type: 'string', default: 'info')]
    public string $type = 'info';

    #[Column(type: 'string', default: 'all')]
    public string $target = 'all';

    #[Column(type: 'boolean', default: false)]
    public bool $closable = false;

    #[Column(type: 'boolean', default: true)]
    public bool $isActive = true;

    #[Column(type: 'integer', default: 0)]
    public int $position = 0;

    #[Column(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $startAt = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $endAt = null;

    #[Column(type: 'datetime')]
    public DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    /**
     * Check if announcement is currently visible.
     */
    public function isVisible(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new DateTimeImmutable();

        if ($this->startAt !== null && $now < $this->startAt) {
            return false;
        }

        return !( $this->endAt !== null && $now > $this->endAt );
    }
}

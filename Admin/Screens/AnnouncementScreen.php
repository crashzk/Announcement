<?php

namespace Flute\Modules\Announcement\Admin\Screens;

use DateTimeImmutable;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RadioCards;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\Announcement\database\Entities\Announcement;
use Flute\Modules\Announcement\Services\AnnouncementService;
use Throwable;

class AnnouncementScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.announcement';

    public $announcements;

    public function mount(): void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-announcement.title'));

        $this->loadAnnouncements();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::sortable('announcements', [
                Sight::make(
                    'content',
                    __('admin-announcement.table.content'),
                )->render(static function (Announcement $announcement) {
                    $badge = match ($announcement->type) {
                        'success' => '<span class="badge success">'
                            . __('admin-announcement.types.success')
                            . '</span>',
                        'warning' => '<span class="badge warning">'
                            . __('admin-announcement.types.warning')
                            . '</span>',
                        'error' => '<span class="badge error">' . __('admin-announcement.types.error') . '</span>',
                        default => '<span class="badge info">' . __('admin-announcement.types.info') . '</span>',
                    };
                    $content = e(mb_strimwidth($announcement->content, 0, 80, '...'));

                    return (
                        '<div class="announcement-cell"><div class="announcement-type">'
                        . $badge
                        . '</div><div class="announcement-content">'
                        . $content
                        . '</div></div>'
                    );
                }),
                Sight::make(
                    'isActive',
                    __('admin-announcement.table.status'),
                )->render(static function (Announcement $announcement) {
                    if ($announcement->isActive) {
                        return '<span class="badge success">' . __('def.active') . '</span>';
                    }

                    return '<span class="badge">' . __('def.inactive') . '</span>';
                }),
                Sight::make('actions', __('admin-announcement.table.actions'))->render(
                    static fn(Announcement $announcement) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('def.edit'))
                                ->modal('editAnnouncementModal', ['announcement' => $announcement->id])
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('def.delete'))
                                ->confirm(__('admin-announcement.confirms.delete'))
                                ->method('deleteAnnouncement', ['id' => $announcement->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ]),
                ),
            ])
                ->onSortEnd('updatePositions')
                ->commands([
                    Button::make(__('def.create'))
                        ->icon('ph.bold.plus-bold')
                        ->size('medium')
                        ->modal('createAnnouncementModal')
                        ->type(Color::PRIMARY),
                ])
                ->title(__('admin-announcement.sections.list.title'))
                ->description(__('admin-announcement.sections.list.description')),
        ];
    }

    /**
     * Update positions after sorting
     */
    public function updatePositions()
    {
        $sortableResult = json_decode(request()->input('sortableResult'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-announcement.messages.invalid_sort_data'), 'danger');

            return;
        }

        $position = 0;
        foreach ($sortableResult as $item) {
            $announcement = Announcement::findByPK($item['id']);
            if ($announcement) {
                $announcement->position = ++$position;
                $announcement->save();
            }
        }

        orm()->getHeap()->clean();
        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Modal for creating a new announcement
     */
    public function createAnnouncementModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, $this->getFormFields())
            ->title(__('admin-announcement.modal.create_title'))
            ->applyButton(__('def.create'))
            ->method('saveAnnouncement');
    }

    /**
     * Save new announcement
     */
    public function saveAnnouncement()
    {
        $data = request()->input();

        $validation = $this->validate([
            'content' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'button_text' => ['nullable', 'string', 'max-str-len:255'],
            'button_url' => ['nullable', 'string', 'max-str-len:255'],
            'button_icon' => ['nullable', 'string', 'max-str-len:255'],
            'type' => ['required', 'string', 'in:info,success,warning,error'],
            'target' => ['required', 'string', 'in:all,guests,auth'],
        ], $data);

        if (!$validation) {
            return;
        }

        $lastItem = Announcement::query()->orderBy('position', 'desc')->fetchOne();
        $position = $lastItem ? $lastItem->position + 1 : 1;

        $announcement = new Announcement();
        $announcement->content = $data['content'];
        $announcement->icon = $data['icon'] ?? null;
        $announcement->url = $data['url'] ?? null;
        $announcement->buttonText = $data['button_text'] ?? null;
        $announcement->buttonUrl = $data['button_url'] ?? null;
        $announcement->buttonIcon = $data['button_icon'] ?? null;
        $announcement->buttonNewTab = isset($data['button_new_tab']) && $data['button_new_tab'] ? true : false;
        $announcement->type = $data['type'] ?? 'info';
        $announcement->target = $data['target'] ?? 'all';
        $announcement->closable = isset($data['closable']) && $data['closable'] ? true : false;
        $announcement->isActive = isset($data['is_active']) && $data['is_active'] ? true : false;
        $announcement->position = $position;

        if (!empty($data['start_at'])) {
            $announcement->startAt = new DateTimeImmutable($data['start_at']);
        }

        if (!empty($data['end_at'])) {
            $announcement->endAt = new DateTimeImmutable($data['end_at']);
        }

        $announcement->save();

        $this->flashMessage(__('admin-announcement.messages.created'), 'success');
        $this->closeModal();

        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Modal for editing an announcement
     */
    public function editAnnouncementModal(Repository $parameters)
    {
        $announcementId = $parameters->get('announcement');
        $announcement = Announcement::findByPK($announcementId);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, $this->getFormFields($announcement))
            ->title(__('admin-announcement.modal.edit_title'))
            ->applyButton(__('def.save'))
            ->method('updateAnnouncement');
    }

    /**
     * Update existing announcement
     */
    public function updateAnnouncement()
    {
        $data = request()->input();
        $announcementId = $this->modalParams->get('announcement');

        $announcement = Announcement::findByPK($announcementId);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        $validation = $this->validate([
            'content' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
            'button_text' => ['nullable', 'string', 'max-str-len:255'],
            'button_url' => ['nullable', 'string', 'max-str-len:255'],
            'button_icon' => ['nullable', 'string', 'max-str-len:255'],
            'type' => ['required', 'string', 'in:info,success,warning,error'],
            'target' => ['required', 'string', 'in:all,guests,auth'],
        ], $data);

        if (!$validation) {
            return;
        }

        $announcement->content = $data['content'];
        $announcement->icon = $data['icon'] ?? null;
        $announcement->url = $data['url'] ?? null;
        $announcement->buttonText = $data['button_text'] ?? null;
        $announcement->buttonUrl = $data['button_url'] ?? null;
        $announcement->buttonIcon = $data['button_icon'] ?? null;
        $announcement->buttonNewTab = isset($data['button_new_tab']) && $data['button_new_tab'] ? true : false;
        $announcement->type = $data['type'] ?? 'info';
        $announcement->target = $data['target'] ?? 'all';
        $announcement->closable = isset($data['closable']) && $data['closable'] ? true : false;
        $announcement->isActive = isset($data['is_active']) && $data['is_active'] ? true : false;

        if (!empty($data['start_at'])) {
            $announcement->startAt = new DateTimeImmutable($data['start_at']);
        } else {
            $announcement->startAt = null;
        }

        if (!empty($data['end_at'])) {
            $announcement->endAt = new DateTimeImmutable($data['end_at']);
        } else {
            $announcement->endAt = null;
        }

        $announcement->save();

        $this->flashMessage(__('admin-announcement.messages.updated'), 'success');
        $this->closeModal();

        $this->clearCache();
        $this->loadAnnouncements();
    }

    /**
     * Delete an announcement
     */
    public function deleteAnnouncement()
    {
        $id = request()->input('id');

        $announcement = Announcement::findByPK($id);
        if (!$announcement) {
            $this->flashMessage(__('admin-announcement.messages.not_found'), 'error');

            return;
        }

        $announcement->delete();
        $this->flashMessage(__('admin-announcement.messages.deleted'), 'success');

        $this->clearCache();
        $this->loadAnnouncements();
    }

    protected function loadAnnouncements()
    {
        $this->announcements = Announcement::query()->orderBy('position', 'asc')->fetchAll();
    }

    protected function getFormFields(?Announcement $announcement = null): array
    {
        return [
            LayoutFactory::field(
                Input::make('content')
                    ->type('textarea')
                    ->placeholder(__('admin-announcement.modal.fields.content.placeholder'))
                    ->value($announcement?->content),
            )
                ->label(__('admin-announcement.modal.fields.content.label'))
                ->required()
                ->small(__('admin-announcement.modal.fields.content.help')),

            LayoutFactory::field(
                RadioCards::make('type')
                    ->options([
                        'info' => [
                            'label' => __('admin-announcement.types.info'),
                            'icon' => 'ph.bold.info-bold',
                        ],
                        'success' => [
                            'label' => __('admin-announcement.types.success'),
                            'icon' => 'ph.bold.check-circle-bold',
                        ],
                        'warning' => [
                            'label' => __('admin-announcement.types.warning'),
                            'icon' => 'ph.bold.warning-bold',
                        ],
                        'error' => [
                            'label' => __('admin-announcement.types.error'),
                            'icon' => 'ph.bold.warning-circle-bold',
                        ],
                    ])
                    ->columns(4)
                    ->value($announcement?->type ?? 'info'),
            )
                ->label(__('admin-announcement.modal.fields.type.label'))
                ->required(),

            LayoutFactory::field(
                ButtonGroup::make('target')
                    ->options([
                        'all' => [
                            'label' => __('admin-announcement.targets.all'),
                            'icon' => 'ph.bold.users-bold',
                        ],
                        'guests' => [
                            'label' => __('admin-announcement.targets.guests'),
                            'icon' => 'ph.bold.eye-bold',
                        ],
                        'auth' => [
                            'label' => __('admin-announcement.targets.auth'),
                            'icon' => 'ph.bold.lock-bold',
                        ],
                    ])
                    ->value($announcement?->target ?? 'all'),
            )
                ->label(__('admin-announcement.modal.fields.target.label'))
                ->small(__('admin-announcement.modal.fields.target.help')),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('icon')
                        ->type('icon')
                        ->placeholder(__('admin-announcement.modal.fields.icon.placeholder'))
                        ->value($announcement?->icon),
                )
                    ->label(__('admin-announcement.modal.fields.icon.label'))
                    ->small(__('admin-announcement.modal.fields.icon.help')),

                LayoutFactory::field(
                    Input::make('url')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.url.placeholder'))
                        ->value($announcement?->url),
                )
                    ->label(__('admin-announcement.modal.fields.url.label'))
                    ->small(__('admin-announcement.modal.fields.url.help')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('button_text')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.button_text.placeholder'))
                        ->value($announcement?->buttonText),
                )
                    ->label(__('admin-announcement.modal.fields.button_text.label'))
                    ->small(__('admin-announcement.modal.fields.button_text.help')),

                LayoutFactory::field(
                    Input::make('button_url')
                        ->type('text')
                        ->placeholder(__('admin-announcement.modal.fields.button_url.placeholder'))
                        ->value($announcement?->buttonUrl),
                )
                    ->label(__('admin-announcement.modal.fields.button_url.label'))
                    ->small(__('admin-announcement.modal.fields.button_url.help')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('button_icon')
                        ->type('icon')
                        ->placeholder(__('admin-announcement.modal.fields.button_icon.placeholder'))
                        ->value($announcement?->buttonIcon),
                )
                    ->label(__('admin-announcement.modal.fields.button_icon.label'))
                    ->small(__('admin-announcement.modal.fields.button_icon.help')),

                LayoutFactory::field(
                    CheckBox::make('button_new_tab')
                        ->label(__('admin-announcement.modal.fields.button_new_tab.label'))
                        ->popover(__('admin-announcement.modal.fields.button_new_tab.help'))
                        ->value($announcement?->buttonNewTab ?? false),
                ),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('start_at')
                        ->type('datetime-local')
                        ->value($announcement?->startAt?->format('Y-m-d\TH:i')),
                )
                    ->label(__('admin-announcement.modal.fields.start_at.label'))
                    ->small(__('admin-announcement.modal.fields.start_at.help')),

                LayoutFactory::field(
                    Input::make('end_at')
                        ->type('datetime-local')
                        ->value($announcement?->endAt?->format('Y-m-d\TH:i')),
                )
                    ->label(__('admin-announcement.modal.fields.end_at.label'))
                    ->small(__('admin-announcement.modal.fields.end_at.help')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    CheckBox::make('closable')
                        ->label(__('admin-announcement.modal.fields.closable.label'))
                        ->popover(__('admin-announcement.modal.fields.closable.help'))
                        ->value($announcement?->closable ?? false),
                ),

                LayoutFactory::field(
                    CheckBox::make('is_active')
                        ->label(__('admin-announcement.modal.fields.is_active.label'))
                        ->popover(__('admin-announcement.modal.fields.is_active.help'))
                        ->value($announcement?->isActive ?? true),
                ),
            ]),
        ];
    }

    /**
     * Clear announcement cache
     */
    private function clearCache(): void
    {
        try {
            app(AnnouncementService::class)->clearCache();
        } catch (Throwable $e) {
            // Swallow exceptions to avoid breaking admin UI
        }
    }
}

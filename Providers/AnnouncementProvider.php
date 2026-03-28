<?php

namespace Flute\Modules\Announcement\Providers;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\Announcement\Admin\AnnouncementAdminPackage;
use Flute\Modules\Announcement\Services\AnnouncementService;
use Throwable;

class AnnouncementProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\Container $container): void
    {
        $this->loadEntities();
        $this->loadTranslations();
        $this->loadViews('Resources/views', 'announcement');

        if (is_installed()) {
            $this->loadScss('Resources/assets/scss/announcement.scss');
            $this->loadPackage(new AnnouncementAdminPackage());

            if (!is_admin_path()) {
                $this->registerAnnouncementBar();
            }
        }
    }

    public function register(\DI\Container $container): void
    {
        $container->set(AnnouncementService::class, static fn() => new AnnouncementService());
    }

    /**
     * Register announcement bar to display in header
     */
    protected function registerAnnouncementBar(): void
    {
        try {
            $content = view('announcement::announcement-bar')->render();

            if (!empty(trim($content))) {
                template()->prependToSection('before-content', $content);
            }
        } catch (Throwable $e) {
            logs('modules')->error('Failed to render announcement bar: ' . $e->getMessage());
        }
    }
}

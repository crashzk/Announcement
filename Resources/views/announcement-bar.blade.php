@php
    $announcementService = app(\Flute\Modules\Announcement\Services\AnnouncementService::class);
    $announcements = $announcementService->getVisible();

    $typeIcons = [
        'info' => 'ph.bold.info-bold',
        'success' => 'ph.bold.check-circle-bold',
        'warning' => 'ph.bold.warning-bold',
        'error' => 'ph.bold.warning-circle-bold',
    ];
@endphp

@if (count($announcements) > 0 && !request()->htmx()->isHtmxRequest())
    <div class="announcement-container" id="announcement-container">
        @foreach ($announcements as $i => $announcement)
            @php
                $icon = $announcement['icon'] ?: ($typeIcons[$announcement['type']] ?? $typeIcons['info']);
                $barUrl = $announcement['url'];
                $hasButton = $announcement['buttonText'] && $announcement['buttonUrl'];
            @endphp
            <div class="announcement-bar announcement-bar--{{ $announcement['type'] }} @if($barUrl) announcement-bar--clickable @endif"
                 data-announcement-id="{{ $announcement['id'] }}"
                 style="animation-delay: {{ $i * 60 }}ms"
                 @if ($barUrl) data-url="{{ $barUrl }}" @endif
                 @if ($announcement['closable']) data-closable="true" @endif>
                <div class="container">
                    <div class="announcement-bar__inner">
                        <div class="announcement-bar__spacer"></div>

                        <div class="announcement-bar__center">
                            <span class="announcement-bar__icon">
                                <x-icon path="{{ $icon }}" />
                            </span>
                            <span class="announcement-bar__text">{!! markdown()->parse($announcement['content']) !!}</span>

                            @if ($hasButton)
                                <a href="{{ $announcement['buttonUrl'] }}"
                                   class="announcement-bar__link"
                                   @if ($announcement['buttonNewTab']) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $announcement['buttonText'] }}
                                    <x-icon path="ph.bold.arrow-right-bold" class="announcement-bar__arrow" />
                                </a>
                            @endif
                        </div>

                        <div class="announcement-bar__right">
                            @if ($announcement['closable'])
                                <button type="button"
                                        class="announcement-bar__close"
                                        aria-label="@t('def.close')"
                                        onclick="dismissAnnouncement({{ $announcement['id'] }})">
                                    <x-icon path="ph.bold.x-bold" />
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function syncAnnouncementStackHeight() {
            var root = document.documentElement;
            var container = document.getElementById('announcement-container');
            if (!container || !container.isConnected) {
                root.style.removeProperty('--announcement-stack-height');
                root.removeAttribute('data-announcement-active');
                return;
            }
            var h = container.offsetHeight;
            if (h <= 0) {
                root.style.setProperty('--announcement-stack-height', '0px');
                root.removeAttribute('data-announcement-active');
                return;
            }
            root.style.setProperty('--announcement-stack-height', h + 'px');
            root.setAttribute('data-announcement-active', 'true');
        }

        function dismissAnnouncement(id) {
            var row = document.querySelector('[data-announcement-id="' + id + '"]');
            if (row) {
                row.classList.add('announcement-bar--hiding');
                setTimeout(function() {
                    row.remove();

                    var dismissed = getCookie('dismissed_announcements') || '';
                    var ids = dismissed ? dismissed.split(',') : [];
                    if (!ids.includes(String(id))) {
                        ids.push(String(id));
                        setCookie('dismissed_announcements', ids.join(','), { expires: 30, path: '/' });
                    }

                    var container = document.getElementById('announcement-container');
                    if (container && container.children.length === 0) {
                        container.remove();
                    }
                    syncAnnouncementStackHeight();
                }, 350);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var bar = document.getElementById('announcement-container');
            if (bar && document.body) {
                document.body.appendChild(bar);
                setTimeout(function() {
                    bar.classList.add('announcement-container--active');
                    syncAnnouncementStackHeight();

                    bar.addEventListener('transitionend', function(e) {
                        if (e.target === bar) syncAnnouncementStackHeight();
                    });

                    if (typeof ResizeObserver !== 'undefined') {
                        var ro = new ResizeObserver(function() {
                            syncAnnouncementStackHeight();
                        });
                        ro.observe(bar);
                    }
                }, 50);

                bar.addEventListener('click', function(e) {
                    var row = e.target.closest('[data-url]');
                    if (!row) return;
                    if (e.target.closest('a, button')) return;
                    window.location.href = row.dataset.url;
                });
            }
        });
    </script>
@endif

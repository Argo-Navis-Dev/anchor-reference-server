<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
            {{ __('home_lang.page.subtitle') }}
        </h2>
        <p class="text-md text-gray-500 dark:text-gray-400 mt-2">
            {!! __('home_lang.page.description') !!}
        </p>
        <div class="flex mt-3 gap-x-3">
            <x-filament::link
                    color="gray"
                    icon="bi-github"
                    href="https://github.com/Argo-Navis-Dev/anchor-reference-server"
                    rel="noopener noreferrer"
                    target="_blank"
            >
                Anchor Reference Server
            </x-filament::link>

            <x-filament::link
                    color="gray"
                    icon="bi-github"
                    href="https://github.com/Argo-Navis-Dev/php-anchor-sdk"
                    rel="noopener noreferrer"
                    target="_blank"
            >
                PHP Anchor SDK
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
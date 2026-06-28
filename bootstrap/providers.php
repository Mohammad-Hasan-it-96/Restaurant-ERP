<?php

return [
    // First: puts the app in pre-DB "installer mode" until the wizard completes.
    App\Providers\InstallerServiceProvider::class,
    App\Providers\AppServiceProvider::class,
];

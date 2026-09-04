<?php

foreach (glob(app_path('Modules/*/api-routes.php')) as $moduleApiRoutes) {
    require $moduleApiRoutes;
}

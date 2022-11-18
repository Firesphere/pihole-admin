<?php

use App\Frontend\Modules\ClientActivity;
use App\Frontend\Modules\Module;
use App\Frontend\Modules\Overview;
use App\Frontend\Modules\TotalQueries;

Module::registerModule(ClientActivity::class);
Module::registerModule(Overview::class);
Module::registerModule(TotalQueries::class);

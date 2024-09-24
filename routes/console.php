<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:update-handicap-index')->daily();

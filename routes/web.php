<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/import-sql-with-project', 'dictionary.import-sql-with-project');
Volt::route('/import-sql', 'dictionary.import-sql');
Volt::route('/', 'dictionary.index');

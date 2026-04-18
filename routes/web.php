<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/import-sql', 'dictionary.import-sql');
Volt::route('/', 'dictionary.index');

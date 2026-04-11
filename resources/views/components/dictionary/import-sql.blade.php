<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SqlSchemaParser;

new class extends Component {
    use WithFileUploads;

    public $sqlFile;
    public $suggestions = [];

    public function process()
    {
        //
    }

    public function save()
    {
        // 
    }
}; ?>

<div>
    仮作成
</div>
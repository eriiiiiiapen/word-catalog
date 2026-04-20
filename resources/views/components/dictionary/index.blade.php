<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SqlSchemaParser;
use App\Models\DictionaryEntry;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component {
    // public $dictionaryEntry = [];
    public ?string $search = '';

    // public function mount()
    // {
    //     $this->dictionaryEntry = DictionaryEntry::all();
    // }

    // public function updatedSearch()
    // {
    //     if (empty($this->search)) {
    //         $this->dictionaryEntry = DictionaryEntry::all();
    //         return;
    //     }

    //     $this->dictionaryEntry = DictionaryEntry::query()
    //         ->where('logical_name', 'LIKE', '%'.$this->search.'%')
    //         ->get();
    // }

    #[Computed]
    public function dictionaryEntry()
    {
        return DictionaryEntry::where(function ($query) {
            $query->where('table_name', 'LIKE', '%'.$this->search.'%')
                ->orWhere('logical_name', 'LIKE', '%'.$this->search.'%')
                ->orWhere('physical_name', 'LIKE', '%'.$this->search.'%');
        })->get();
    }
}; 

?>

<div>
    <div class="p-6">
        <div class="w-full flex flex-row justify-between items-center px-8 py-2">
            <h1 class="text-2xl font-bold mb-4">一覧</h1>
            <div class="flex justify-end">
                検索：<input type="text" class="border rounded px-1" wire:model.live="search">
            </div>
        </div>
        @if(count($this->dictionaryEntry) > 0)
        <div class="px-8">
            <table class="w-full bg-white border">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border px-4 py-2">テーブル名</th>
                        <th class="border px-4 py-2">物理名</th>
                        <th class="border px-4 py-2">論理名</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->dictionaryEntry as $index => $item)
                        <tr>
                            <td class="border px-4 py-2">{{ $item->table_name }}</td>
                            <td class="border px-4 py-2"><code>{{ $item->physical_name }}</code></td>
                            <td class="border px-4 py-2">
                                {{ $item->logical_name }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="mt-6 flex justify-start">
                登録されているものはありません。
            </div>
        @endif
    </div>
</div>
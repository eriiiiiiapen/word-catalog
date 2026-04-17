<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SqlSchemaParser;
use App\Models\DictionaryEntry;
use Illuminate\Support\Str;

new class extends Component {
    use WithFileUploads;

    public $sqlFile;
    public $suggestions = [];

    public function process()
    {
        $this->validate([
            'sqlFile' => 'required|max:1024',
        ]);

        $content = file_get_contents($this->sqlFile->getRealPath());
        $parser = new SqlSchemaParser();
        
        $this->suggestions = $parser->parse($content);
    }

    public function save()
    {
        $this->validate([
            'suggestions.*.logical_name' => 'required|string|max:255',
        ]);

        foreach ($this->suggestions as $entry) {
            DictionaryEntry::create([
                'table_name'    => $entry['table_name'],
                'physical_name' => $entry['physical_name'],
                'logical_name'  => $entry['logical_name'],
                'public_token' => (string) Str::uuid(),
            ]);
        }

        $this->suggestions = [];
        session()->flash('message', '正常にインポートされました。');
    }
}; ?>

<div>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">DDLインポート</h1>

        <div class="mb-8 p-4 border-2 border-dashed rounded-lg">
            <input type="file" wire:model="sqlFile" class="border p-4">
            <button wire:click="process" class="text-white px-4 py-2 rounded ml-2 @if(!$sqlFile) bg-gray-300 @else bg-blue-600 hover:bg-blue-700 cursor-pointer @endif"
            >
                解析開始
            </button>
        </div>

        @if(count($suggestions) > 0)
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border px-4 py-2">テーブル名</th>
                        <th class="border px-4 py-2">物理名</th>
                        <th class="border px-4 py-2">論理名</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suggestions as $index => $item)
                        <tr wire:key="{{ $index }}">
                            <td class="border px-4 py-2">{{ $item['table_name'] }}</td>
                            <td class="border px-4 py-2"><code>{{ $item['physical_name'] }}</code></td>
                            <td class="border px-4 py-2">
                                <input type="text" wire:model="suggestions.{{ $index }}.logical_name" 
                                       class="border p-1 w-full rounded">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-6 flex justify-end">
                <button 
                    type="button" 
                    wire:click="save" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition cursor-pointer"
                >
                    この内容で辞書に登録する
                </button>
            </div>
        @endif
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>
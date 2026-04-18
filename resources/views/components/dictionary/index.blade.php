<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SqlSchemaParser;
use App\Models\DictionaryEntry;
use Illuminate\Support\Str;

new class extends Component {
    public $dictionaryEntry = [];

    public function mount()
    {
        $this->dictionaryEntry = DictionaryEntry::all();
    }
}; 

?>

<div>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">一覧</h1>
        @if(count($dictionaryEntry) > 0)
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border px-4 py-2">テーブル名</th>
                        <th class="border px-4 py-2">物理名</th>
                        <th class="border px-4 py-2">論理名</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dictionaryEntry as $index => $item)
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
        @else
            <div class="mt-6 flex justify-start">
                登録されているものはありません。
            </div>
        @endif
    </div>
</div>
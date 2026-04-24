<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\SqlSchemaParser;
use App\Models\DictionaryEntry;
use App\Models\Project;
use App\Models\Tag;
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
    public $newTableName;
    public $newPhysicalName;
    public $newLogicalName;
    public $projectId;
    public $newTags;

    public function quickSave()
    {
        $this->validate([
            'projectId' => 'required',
            'newPhysicalName' => 'required|string',
            'newLogicalName' => 'required|string',
        ]);

        $entry = DictionaryEntry::create([
            'project_id'    => $this->projectId,
            'table_name'    => $this->newTableName ?? '共通・その他',
            'physical_name' => $this->newPhysicalName,
            'logical_name'  => $this->newLogicalName,
            'public_token'  => (string) Str::uuid(),
        ]);

        if ($this->newTags) {
            $tagNames = collect(explode(',', $this->newTags))
                ->map(fn($t) => trim($t))
                ->filter();

            $tagIds = [];
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(['name' => $name]);
                $tagIds[] = $tag->id;
            }

            $entry->tags()->sync($tagIds);
        }

        $this->reset(['newTableName', 'newPhysicalName', 'newLogicalName', 'projectId', 'newTags']);
    }

    #[Computed]
    public function projects()
    {
        return Project::get();
    }

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
        <div class="px-8 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg flex gap-2 items-end border border-blue-100">
                <div class="flex-1">
                    <label class="block text-xs text-blue-600 font-bold mb-1">プロジェクト</label>
                    <select wire:model="projectId" class="w-full border rounded px-2 py-1 bg-white">
                        <option value="">選択してください</option>
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-blue-600 font-bold mb-1">テーブル/カテゴリ</label>
                    <input type="text" wire:model="newTableName" placeholder="users / 業務用語" class="w-full border rounded px-2 py-1 bg-white">
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-blue-600 font-bold mb-1">物理名 (英字)</label>
                    <input type="text" wire:model="newPhysicalName" placeholder="status_id" class="w-full border rounded px-2 py-1 bg-white">
                </div>
                <div class="flex-1">
                    <label class="block text-xs text-blue-600 font-bold mb-1">論理名 (日本語)</label>
                    <input type="text" wire:model="newLogicalName" placeholder="公開ステータス" class="w-full border rounded px-2 py-1 bg-white" wire:keydown.enter="quickSave">
                </div>
                <div>
                    <label class="block text-xs text-blue-600 font-bold mb-1">タグ (カンマ区切り)</label>
                    <input type="text" wire:model="newTags" placeholder="決済, 重要, 未定" class="w-full border rounded px-2 py-1 bg-white" wire:keydown.enter="quickSave">
                </div>
                <button wire:click="quickSave" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 font-bold shadow-sm">
                    追加
                </button>
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
                            <td class="border px-4 py-2">
                                <code>{{ $item->physical_name }}</code>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($item->tags as $tag)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full text-white bg-blue-500">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
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
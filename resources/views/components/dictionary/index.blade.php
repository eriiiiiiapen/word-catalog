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
    public ?int $selectedTagId = null;

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

    public function selectTag($tagId)
    {
        $this->selectedTagId = ($this->selectedTagId === $tagId) ? null : $tagId;
    }

    #[Computed]
    public function projects()
    {
        return Project::get();
    }

    #[Computed]
    public function dictionaryEntry()
    {
        return DictionaryEntry::query()
        ->with(['project', 'tags'])
        ->when($this->projectId, fn($q) => $q->where('project_id', $this->projectId))
        ->when($this->selectedTagId, function($q) {
            $q->whereHas('tags', fn($inner) => $inner->where('tags.id', $this->selectedTagId));
        })
        ->where(function ($query) {
            $query->where('table_name', 'LIKE', '%'.$this->search.'%')
                ->orWhere('logical_name', 'LIKE', '%'.$this->search.'%')
                ->orWhere('physical_name', 'LIKE', '%'.$this->search.'%');
        })->get();
    }

    #[Computed]
    public function activeTagName()
    {
        return $this->selectedTagId ? Tag::find($this->selectedTagId)?->name : null;
    }

    #[Computed]
    public function popularTags()
    {
        return Tag::withCount('dictionaryEntries')
            ->orderBy('dictionary_entries_count', 'desc')
            ->take(10)
            ->get();
    }
}; 

?>

<div>
    <div class="p-6">
        <div class="w-full flex flex-row justify-between items-center px-8 py-2">
            <h1 class="text-2xl font-bold mb-4">一覧</h1>
            @if($this->activeTagName)
                <div class="flex items-center bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-bold animate-pulse">
                    タグ: {{ $this->activeTagName }}
                    <button wire:click="selectTag({{ $selectedTagId }})" class="ml-2 hover:text-red-500">×</button>
                </div>
            @endif
            <div class="flex justify-end">
                検索：<input type="text" class="border rounded px-1" wire:model.live="search">
            </div>
        </div>
        <div class="px-8 mb-4 flex flex-wrap gap-2 items-center">
            <span class="text-xs font-bold text-gray-500 uppercase">クイックタグ:</span>
            @foreach($this->popularTags as $tag)
                <button 
                    wire:click="selectTag({{ $tag->id }})"
                    class="px-2 py-1 text-xs rounded border transition-all cursor-pointer
                    {{ $selectedTagId === $tag->id 
                        ? 'bg-blue-600 text-white border-blue-600' 
                        : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}"
                >
                    {{ $tag->name }} 
                    <span class="ml-1 text-[10px] opacity-70">({{ $tag->dictionary_entries_count }})</span>
                </button>
            @endforeach
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
            @if($errors->any()) @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach  @endif
        </div>
        @if(count($this->dictionaryEntry) > 0)
        <div class="px-8">
            <table class="w-full bg-white border">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border px-4 py-2">プロジェクト</th>
                        <th class="border px-4 py-2">テーブル名</th>
                        <th class="border px-4 py-2">物理名</th>
                        <th class="border px-4 py-2">論理名</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->dictionaryEntry as $index => $item)
                        <tr class="hover:bg-gray-200">
                            <td class="border px-4 py-2">{{ $item->project ? $item->project->name : '' }}</td>
                            <td class="border px-4 py-2">{{ $item->table_name }}</td>
                            <td class="border px-4 py-2">
                                <code>{{ $item->physical_name }}</code>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($item->tags as $tag)
                                        <button 
                                            type="button"
                                            wire:click="selectTag({{ $tag->id }})"
                                            class="text-[10px] px-2 py-0.5 rounded-full text-white transition-all {{ $selectedTagId === $tag->id ? 'ring-2 ring-offset-1 ring-blue-600 bg-blue-700' : 'bg-blue-500 hover:bg-blue-600' }}"
                                        >
                                            #{{ $tag->name }}
                                        </button>
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
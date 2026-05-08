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
    public $selectedEntryId = null;
    public $description;

    public $newLinkLabel;
    public $newLinkUrl;

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
                $tag = Tag::firstOrCreate(
                    [
                        'name' => $name
                    ],
                    [
                        'color' => $this->getRandomColor()
                    ]
                );
                $tagIds[] = $tag->id;
            }

            $entry->tags()->sync($tagIds);
        }

        $this->reset(['newTableName', 'newPhysicalName', 'newLogicalName', 'projectId', 'newTags']);
    }

    public function addLink()
    {
        $this->validate([
            'newLinkLabel' => 'required|string',
            'newLinkUrl' => 'required|url',
        ]);

        $currentLinks = $this->selectedEntry->links ?? [];

        $currentLinks[] = [
            'label' => $this->newLinkLabel,
            'url' => $this->newLinkUrl,
        ];

        $this->selectedEntry->update([
            'links' => $currentLinks
        ]);

        $this->selectedEntry->logs()->create([
            'action' => 'link_added',
            'changes' => ['label' => $this->newLinkLabel]
        ]);

        $this->reset(['newLinkLabel', 'newLinkUrl']);
    }

    private function getRandomColor()
    {
        $colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
        return $colors[array_rand($colors)];
    }

    public function selectTag($tagId)
    {
        $this->selectedTagId = ($this->selectedTagId === $tagId) ? null : $tagId;
    }

    public function openDetails($id)
    {
        $this->selectedEntryId = $id;
        $this->description = $this->selectedEntry->description;
    }

    public function changeDesctiption()
    {
        if (!$this->selectedEntry) return;

        $this->selectedEntry->update([
            'description' => $this->description
        ]);

        $this->selectedEntry->logs()->create([
            'action' => 'updated',
            'changes' => ['description' => $this->description]
        ]);
    }

    #[Computed]
    public function projects()
    {
        return Project::withCount('dictionaryEntries')
            ->orderBy('name', 'asc')
            ->get();
    }

    #[Computed]
    public function dictionaryEntry()
    {
        return DictionaryEntry::query()
            ->with(['project', 'tags'])
            ->when($this->projectId, fn($q) => $q->where('project_id', $this->projectId))
            ->where(function ($query) {
                $search = $this->search;
                
                // 検索ワードを「ひらがな」から「カタカナ」に変換したものも用意
                $katakanaSearch = mb_convert_kana($search, "KVC"); 

                $query->where('table_name', 'LIKE', "%{$search}%")
                    ->orWhere('physical_name', 'LIKE', "%{$search}%")
                    ->orWhere('logical_name', 'LIKE', "%{$search}%")
                    ->orWhere('logical_name', 'LIKE', "%{$katakanaSearch}%");
            })
            ->get();
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

    #[Computed]
    public function selectedEntry()
    {
        return $this->selectedEntryId ? DictionaryEntry::with(['tags', 'logs'])->find($this->selectedEntryId) : null;
    }
}; 

?>

<div class="flex h-screen bg-gray-100">
    <!-- サイドバー -->
    <div class="w-64 bg-slate-800 text-white flex flex-col shadow-xl">
        <div class="p-6">
            <h2 class="text-xl font-bold tracking-widest text-blue-400">DEV_DICT</h2>
            <p class="text-xs text-slate-400 mt-1">案件別用語辞典</p>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 space-y-2">
            <button 
                wire:click="$set('projectId', null)"
                class="w-full text-left px-4 py-2 rounded transition {{ is_null($projectId) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}"
            >
                📁 すべての案件
            </button>

            <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                プロジェクト一覧
            </div>

            @foreach($this->projects as $project)
                <button 
                    wire:click="$set('projectId', {{ $project->id }})"
                    class="w-full text-left px-4 py-2 rounded text-sm transition flex justify-between items-center cursor-pointer {{ $projectId === $project->id ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <span class="truncate">{{ $project->name }}</span>
                    <span class="text-[10px] bg-slate-900 px-1.5 py-0.5 rounded text-slate-400">
                        {{ $project->dictionary_entries_count }}
                    </span>
                </button>
            @endforeach
        </nav>

        <div class="p-4 border-t border-slate-700 text-xs text-slate-500">
            Total: {{ \App\Models\DictionaryEntry::count() }} entries
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- 上部ヘッダー（検索やタグなど） -->
        <header class="bg-white border-b p-4 shadow-sm z-10">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $projectId ? \App\Models\Project::find($projectId)->name : 'すべての案件' }}
                </h1>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            🔍
                        </span>
                        <input type="text" 
                               wire:model.live="search" 
                               placeholder="物理名・論理名で検索..."
                               class="pl-10 pr-4 py-2 border rounded-full text-sm w-64 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 items-center">
                <span class="text-[10px] font-bold text-gray-400 uppercase">Popular Tags:</span>
                @foreach($this->popularTags as $tag)
                    <button wire:click.stop="selectTag({{ $tag->id }})" 
                            class="px-2 py-0.5 text-[11px] rounded border {{ $selectedTagId === $tag->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-500 border-gray-200' }}">
                        #{{ $tag->name }}
                    </button>
                @endforeach
            </div>
        </header>

        <!-- テーブルエリア -->
        <main class="flex-1 overflow-auto p-8">
            @include('livewire.dictionary.partials.quick-form')
            @include('livewire.dictionary.partials.table')
        </main>
    </div>
</div>
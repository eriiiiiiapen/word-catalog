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

    public $showBulkModal = false;
    public $bulkText = '';
    public $suggestions = [];

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

    public function openBulkModal()
    {
        if (!$this->projectId) {
            return;
        }
        $this->reset(['bulkText', 'suggestions']);
        $this->showBulkModal = true;
    }

    /**
     * テキストエリアが更新されたら自動的にパース
    */
    public function updatedBulkText()
    {
        if (empty($this->bulkText)) {
            $this->suggestions = [];
            return;
        }

        $lines = explode("\n", trim($this->bulkText));
        $parsed = [];

        foreach ($lines as $line) {
            $cols = explode("\t", $line);

            if (count($cols) >= 2) {
                $parsed[] = [
                    'table_name'    => $this->newTableName ?? '一括インポート',
                    'physical_name' => trim($cols[0]),
                    'logical_name'  => trim($cols[1]),
                    'description'   => isset($cols[2]) ? trim($cols[2]) : '',
                ];
            }
        }

        $this->suggestions = $parsed;
    }

    /**
     * パースされたデータを一括保存する
     */
    public function saveBulk()
    {
        if (empty($this->suggestions)) return;

        $this->validate([
            'projectId' => 'required',
        ], [
            'projectId.required' => '案件を選択してから保存してください。'
        ]);

        foreach ($this->suggestions as $entry) {
            DictionaryEntry::create([
                'project_id'    => $this->projectId,
                'table_name'    => $entry['table_name'],
                'physical_name' => $entry['physical_name'],
                'logical_name'  => $entry['logical_name'],
                'description'   => $entry['description'],
                'public_token'  => (string) Str::uuid(),
            ]);
        }

        // リセット
        $this->reset(['bulkText', 'suggestions']);
        
        // 完了メッセージ（任意）
        session()->flash('message', '一括登録が完了しました！');
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
                class="w-full text-left px-4 py-2 rounded transition cursor-pointer {{ is_null($projectId) ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}"
            >
                📁 すべての案件
            </button>

            <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                プロジェクト一覧
            </div>

            @if($projectId)
                <div class="px-4 mb-4">
                    <button 
                        wire:click="openBulkModal"
                        class="w-full bg-blue-500 hover:bg-blue-400 text-white text-xs font-bold py-2 px-4 rounded shadow-sm transition flex items-center justify-center gap-2"
                    >
                        <span>Excelから一括登録</span>
                    </button>
                </div>
            @endif

            @foreach($this->projects as $project)
                <button 
                    wire:click="$set('projectId', {{ $project->id }})"
                    class="w-full text-left px-4 py-2 rounded text-sm transition flex justify-between items-center cursor-pointer {{ $projectId === $project->id ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700' }}"
                >
                    <span class="truncate">{{ $project->name }}</span>
                    <span class="text-[10px] font-bold {{ $projectId === $project->id ? 'bg-white' : 'bg-slate-900' }} px-1.5 py-0.5 rounded text-slate-400">
                        {{ $project->dictionary_entries_count }}
                    </span>
                </button>
            @endforeach
        </nav>

        <div class="p-4 flex items-center">
            <a 
                href="{{ route('dictionary.import-sql-with-project') }}"
                class="w-full text-center text-sm px-4 py-2 rounded transition cursor-pointer border border-white hover:bg-gray-400"
            >
                SQLインポート画面へ
            </a>
        </div>

        <div class="p-4 border-t border-slate-700 text-xs text-slate-500">
            Total: {{ \App\Models\DictionaryEntry::count() }} entries
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- 上部ヘッダー（検索やタグなど） -->
        <header class="bg-white border-b p-4 shadow-sm">
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

    @if($showBulkModal)
    <div class="fixed inset-0 overflow-y-auto z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div class="fixed inset-0 bg-gray-600 opacity-75 transition-opacity" 
                style="z-index: -1;" 
                wire:click="$set('showBulkModal', false)">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative" 
                style="z-index: 101;">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Excelから一括インポート</h3>
                        <button wire:click="$set('showBulkModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4">
                        選択中の案件: <span class="font-bold text-blue-600">{{ \App\Models\Project::find($projectId)->name }}</span>
                    </p>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">コピーしたデータを貼り付け</label>
                        <textarea 
                            wire:model.live="bulkText" 
                            rows="10" 
                            placeholder="physical_name	logical_name	memo..."
                            class="w-full border rounded p-3 bg-gray-50 text-black font-mono text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        ></textarea>
                    </div>

                    @if(count($suggestions) > 0)
                        <div class="max-h-40 overflow-y-auto border rounded bg-gray-50 p-2">
                            <table class="w-full text-xs text-left text-gray-600">
                                <thead class="sticky top-0 bg-gray-200">
                                    <tr>
                                        <th class="p-1">物理名</th>
                                        <th class="p-1">論理名</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($suggestions as $s)
                                        <tr class="border-b">
                                            <td class="p-1 font-mono">{{ $s['physical_name'] }}</td>
                                            <td class="p-1">{{ $s['logical_name'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-200 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button 
                        wire:click="saveBulk" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm {{ count($suggestions) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                        @if(count($suggestions) === 0) disabled @endif
                    >
                        {{ count($suggestions) }} 件を登録する
                    </button>
                    <button 
                        wire:click="$set('showBulkModal', false)" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        キャンセル
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
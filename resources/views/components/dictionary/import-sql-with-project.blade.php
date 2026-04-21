<?php

use App\Services\SqlSchemaParser;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Project;
use App\Models\DictionaryEntry;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends Component {
    use WithFileUploads;

    public $sqlFile;
    public $suggestions = [];
    public $projectId;
    public $newProjectName;

    #[Computed]
    public function projects()
    {
        return Project::orderBy('created_at', 'desc')->get();
    }

    public function process()
    {
        $this->validate(['sqlFile' => 'required|max:1024']);
        $content = file_get_contents($this->sqlFile->getRealPath());
        $parser = new SqlSchemaParser();
        $this->suggestions = $parser->parse($content);
    }

    public function save()
    {
        $this->validate([
            'projectId' => 'required_without:newProjectName',
            'suggestions.*.logical_name' => 'required|string|max:255',
        ]);

        if ($this->newProjectName) {
            $project = Project::create(['name' => $this->newProjectName]);
            $this->projectId = $project->id;
        }

        foreach ($this->suggestions as $entry) {
            DictionaryEntry::create([
                'project_id'    => $this->projectId,
                'table_name'    => $entry['table_name'],
                'physical_name' => $entry['physical_name'],
                'logical_name'  => $entry['logical_name'],
                'public_token'  => (string) Str::uuid(),
            ]);
        }

        return redirect()->to('/')->with('message', '案件に登録されました！');
    }
};

?>

<div class="mb-8 p-6 bg-slate-50 rounded-lg border">
    <h2 class="font-bold mb-4">1. 案件を選択または新規作成</h2>
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">既存の案件から選ぶ</label>
            <select wire:model="projectId" class="w-full border rounded p-2">
                <option value="">選択してください</option>
                @foreach($this->projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">新しい案件名を入力</label>
            <input type="text" wire:model="newProjectName" placeholder="例：〇〇社ECサイト構築" class="w-full border rounded p-2">
        </div>
    </div>

    <h2 class="font-bold mb-4">2. SQLファイルを解析</h2>
    <div class="flex items-center gap-2">
        <input type="file" wire:model="sqlFile" class="border p-2 bg-white flex-1">
        <button wire:click="process" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700">解析開始</button>
    </div>

    @if(count($suggestions) > 0)
    <div class="mt-4">
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
    </div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif
</div>
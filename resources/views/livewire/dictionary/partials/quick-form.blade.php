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
        <button wire:click="quickSave" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 cursor-pointer font-bold shadow-sm">
            追加
        </button>
    </div>
    @if($errors->any()) @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach  @endif
</div>
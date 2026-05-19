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
                <tr wire:click="openDetails({{ $item->id }})" class="cursor-pointer hover:bg-gray-200 transition">
                    <td class="border px-4 py-2">{{ $item->project ? $item->project->name : '' }}</td>
                    <td class="border px-4 py-2">{{ $item->table_name }}</td>
                    <td class="border px-4 py-2">
                        <code>{{ $item->physical_name }}</code>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach($item->tags as $tag)
                                <button 
                                    type="button"
                                    wire:click.stop="selectTag({{ $tag->id }})"
                                    style="background-color: {{ $tag->color }};"
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

    <!-- スライドオーバー (選択されている時だけ右から表示) -->
    @if($this->selectedEntry)
        <div class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl z-50 border-l transform transition-transform duration-300">
            <div class="h-full flex flex-col p-6 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">用語詳細</h2>
                    <button wire:click="$set('selectedEntryId', null)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="space-y-6">
                    <div>
                        <label class="text-xs font-bold text-blue-600 uppercase">論理名</label>
                        <p class="text-lg font-semibold text-gray-800">{{ $this->selectedEntry->logical_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-blue-600 uppercase">物理名</label>
                        <p class="font-mono bg-gray-100 p-2 rounded">{{ $this->selectedEntry->physical_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-blue-600 uppercase">補足説明</label>
                        <textarea class="w-full border rounded p-2 text-sm h-32" 
                                placeholder="この項目の背景や、開発時の注意点などをメモ..."
                                wire:model.blur="description"></textarea>
                        <button wire:click="changeDescription" class="mr-auto bg-blue-400 hover:bg-blue-300 text-white cursor-pointer px-2 rounded text-xs">更新</button>
                    </div>
                </div>
                <div class="mt-6">
                    <label class="text-xs font-bold text-blue-600 uppercase">関連リンク</label>
                    <div class="space-y-2 mt-2">
                        @if($this->selectedEntry->links)
                            @foreach($this->selectedEntry->links as $link)
                                <a href="{{ $link['url'] }}" target="_blank" class="block text-sm text-blue-500 hover:underline flex items-center">
                                    🔗 {{ $link['label'] ?? '外部リンク' }}
                                </a>
                            @endforeach
                        @endif
                        <div class="flex gap-1 mt-2 border border-gray-300 p-4">
                            <input type="text" wire:model="newLinkLabel" placeholder="ラベル" class="text-xs border rounded px-1 w-1/3">
                            <input type="text" wire:model="newLinkUrl" placeholder="URL" class="text-xs border rounded px-1 flex-1">
                            <button wire:click="addLink" class="bg-blue-400 hover:bg-blue-300 text-white cursor-pointer px-2 rounded text-xs">+</button>
                        </div>
                    </div>
                </div>
                <div class="mt-8 border-t pt-4">
                    <p class="text-xs font-bold text-gray-400 uppercase">履歴</p>
                    <ul class="mt-2 space-y-2">
                        @foreach($this->selectedEntry->logs()->latest()->take(5)->get() as $log)
                        <li class="text-[10px] text-gray-500">
                            <span class="font-bold">{{ $log->created_at->format('Y/m/d H:i') }}</span>
                            : {{ $log->action === 'updated' ? '内容更新' : '新規登録' }}
                            @if(isset($log->changes['label']) && $log->changes['label'] === 'description')
                            <p>
                                変更前：{{ isset($log->changes['before']) ? $log->changes['before'] : '' }}<br>
                                変更後：{{ isset($log->changes['after']) ? $log->changes['after'] : '' }}
                            </p>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div wire:click="$set('selectedEntryId', null)" class="fixed inset-0 bg-black opacity-20 z-40"></div>
    @endif
</div>
@else
    <div class="mt-6 flex justify-start">
        登録されているものはありません。
    </div>
@endif
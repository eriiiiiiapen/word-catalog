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
                        <label class="text-xs font-bold text-blue-600 uppercase tracking-wider">物理名</label>
                        <div class="flex items-center gap-2 bg-slate-100 p-2 rounded font-mono text-sm justify-between mt-1">
                            <span class="text-slate-800">{{ $this->selectedEntry->physical_name }}</span>
                            <button 
                                x-data="{ copied: false }"
                                @click="
                                    navigator.clipboard.writeText('{{ $this->selectedEntry->physical_name }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 1500);
                                "
                                class="text-[11px] text-slate-500 hover:text-blue-600 px-2 py-0.5 rounded border border-slate-300 bg-white shadow-sm transition-all"
                            >
                                <span x-show="!copied">コピー</span>
                                <span x-show="copied" class="text-emerald-600 font-bold">Copied</span>
                            </button>
                        </div>
                        <div class="mt-3" x-data="{ copiedMig: false, copiedVal: false }">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">コードスニペット</p>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    @click="
                                        navigator.clipboard.writeText(`{{ $this->snippetMigration }}`);
                                        copiedMig = true;
                                        setTimeout(() => copiedMig = false, 1500);
                                    "
                                    class="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 px-2.5 py-1 rounded font-mono shadow-sm transition-all flex items-center gap-1"
                                >
                                    <span x-show="!copiedMig">Migration</span>
                                    <span x-show="copiedMig" class="text-emerald-400 font-bold">Copied</span>
                                </button>
                                <button 
                                    @click="
                                        navigator.clipboard.writeText(`'{{ $this->selectedEntry->physical_name }}' => ['required'],`);
                                        copiedVal = true;
                                        setTimeout(() => copiedVal = false, 1500);
                                    "
                                    class="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-200 px-2.5 py-1 rounded font-mono shadow-sm transition-all flex items-center gap-1"
                                >
                                    <span x-show="!copiedVal">Validation</span>
                                    <span x-show="copiedVal" class="text-emerald-400 font-bold">Copied</span>
                                </button>
                            </div>
                            <div class="mt-2 text-[10px] font-mono bg-slate-900 text-slate-400 p-2 rounded overflow-x-auto border border-slate-800">
                                <span class="text-slate-600">// コピーされるコード</span><br>
                                <span class="text-emerald-400">{{ $this->snippetMigration }}</span>
                            </div>
                        </div>
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
                <!-- 変更履歴のタイムライン表示 -->
                <div class="mt-8 border-t pt-4">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">仕様変更の経緯</p>
                    
                    <div class="relative border-l-2 border-slate-200 ml-2 pl-4 space-y-6">
                        @foreach($this->selectedEntry->logs()->latest()->take(10)->get() as $log)
                            <div class="relative">
                                <!-- タイムラインのドット -->
                                <span class="absolute -left-[21px] top-1 bg-white p-0.5 rounded-full">
                                    <span class="block w-2 h-2 rounded-full {{ $log->action === 'updated' ? 'bg-amber-500' : ($log->action === 'link_added' ? 'bg-blue-500' : 'bg-emerald-500') }}"></span>
                                </span>

                                <!-- タイムスタンプ -->
                                <time class="block text-[10px] font-mono text-slate-400">
                                    {{ $log->created_at->format('Y/m/d H:i') }}
                                </time>

                                <!-- アクションタイトル -->
                                <h4 class="text-xs font-bold text-slate-700 mt-0.5">
                                    @if($log->action === 'updated')
                                        ✏️ 補足説明の更新
                                    @elseif($log->action === 'link_added')
                                        🔗 関連リンクの追加
                                    @else
                                        📥 辞書への登録
                                    @endif
                                </h4>

                                <!-- 変更詳細（差分表示） -->
                                @if(isset($log->changes['label']) && $log->changes['label'] === 'description')
                                    <div class="mt-1.5 text-[11px] bg-slate-50 p-2 rounded border border-slate-100 space-y-1">
                                        @if(!empty($log->changes['before']))
                                            <div class="text-red-500 line-through">
                                                <span class="font-bold text-[9px] bg-red-50 px-1 py-0.5 rounded mr-1">前</span>{{ $log->changes['before'] }}
                                            </div>
                                        @else
                                            <div class="text-slate-400 italic text-[10px]">（前回の記載なし）</div>
                                        @endif
                                        
                                        <div class="text-emerald-600 font-medium">
                                            <span class="font-bold text-[9px] bg-emerald-50 px-1 py-0.5 rounded mr-1">後</span>{{ $log->changes['after'] }}
                                        </div>
                                    </div>
                                @endif

                                <!-- リンク追加時の詳細 -->
                                @if($log->action === 'link_added' && isset($log->changes['label']))
                                    <div class="mt-1 text-[11px] text-slate-600">
                                        「<span class="font-semibold text-slate-800">{{ $log->changes['label'] }}</span>」のリンクを紐付けました。
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
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
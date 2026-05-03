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
</div>
@else
    <div class="mt-6 flex justify-start">
        登録されているものはありません。
    </div>
@endif
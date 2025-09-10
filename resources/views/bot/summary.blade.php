<div class="w-full bg-white shadow-lg rounded-lg overflow-hidden mt-16">
    <ul class="divide-y divide-gray-200 px-4">
        @foreach($list as $item)
            <li class="py-4">
                <div class="flex items-center">
                    @php
                        $iconColor = !str_contains($item['icon'], 'x') ? '--c-400:var(--success-400);--c-500:var(--success-500);' : '--c-400:var(--danger-400);--c-500:var(--danger-500);';
                    @endphp

                    {{ svg($item['icon'], 'fi-ta-icon-item fi-ta-icon-item-size-lg h-6 w-6 fi-color-custom text-custom-500 dark:text-custom-400 fi-color-danger', ['style' => $iconColor]) }}

                    <label class="block text-gray-900" style="margin-left: 10px">
                        <span class="text-lg">{{$item['text']}}</span>
                    </label>
                </div>
            </li>
        @endforeach
    </ul>
</div>

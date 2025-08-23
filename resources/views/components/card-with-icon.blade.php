<div {{$attributes->merge(['class' => 'reveal bg-gray-800 p-8 rounded-xl border border-gray-700 flex items-start gap-4'])}} style="transition-delay: 200ms;">
    <div><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400 mt-1">{!! $icon !!}</svg></div>
    <div>
        <h4 class="text-xl font-bold mb-2  text-white">{{ $title }}</h4>
        <p class="text-gray-300">{{ $text }}</p>
    </div>
</div>

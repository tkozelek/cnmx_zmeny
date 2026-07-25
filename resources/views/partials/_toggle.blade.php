{{-- Flips a `hide-names` class on <html> (see app.css / app.js) rather than hiding each row
     inline, so a Livewire re-render of a day card cannot lose the setting. --}}
<label class="inline-flex min-h-11 cursor-pointer select-none items-center gap-3">
    <input type="checkbox" id="names_checkbox" class="peer sr-only" checked>
    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-700 transition-colors after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-slate-950"></span>
    <span class="text-sm text-slate-400">Zobraziť mená zamestnancov</span>
</label>

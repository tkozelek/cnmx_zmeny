<x-layout title="Nahlásiť chybu" description="Nahlás chybu v portáli pracovných zmien Cinemax.">
    <div class="container mx-auto max-w-lg px-4 py-8">
        <div class="flex flex-col gap-6">
            <x-page-header icon="fa-bug" title="Nahlásiť chybu" subtitle="Niečo nefunguje, ako má? Napíš mi, čo sa stalo – správa príde priamo mne." />

            <x-error-summary :fields="$errorFields" />

            <x-settings-section id="hlasenie" title="Čo sa stalo" description="Čím presnejšie, tým rýchlejšie to opravím: čo si robil, čo si čakal a čo sa stalo namiesto toho.">
                <form action="{{ route('bug-report.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                    @csrf

                    @if($page)
                        <input type="hidden" name="page" value="{{ $page }}">
                    @endif

                    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="{{ $honeypot }}">Nechaj prázdne</label>
                        <input type="text" name="{{ $honeypot }}" id="{{ $honeypot }}" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="field">
                        <label for="description" class="field__label text-sm font-medium text-neutral-200">Popis</label>
                        <p id="description-hint" class="field__hint text-sm text-neutral-400">Aspoň {{ $descriptionMin }} znakov, najviac {{ $descriptionMax }}.</p>
                        <textarea
                            name="description"
                            id="description"
                            rows="7"
                            minlength="{{ $descriptionMin }}"
                            maxlength="{{ $descriptionMax }}"
                            required
                            placeholder="Napr. Po kliknutí na „Zapísať sa“ v piatok sa stránka len znova načíta a zápis sa neuloží."
                            aria-describedby="description-hint @error('description') description-error @enderror"
                            @error('description') aria-invalid="true" @enderror
                            @class([
                                'w-full px-4 py-3 rounded-xl bg-neutral-900 border text-white placeholder-neutral-400 transition-all duration-200 text-base sm:text-sm',
                                'border-rose-500/70' => $errors->has('description'),
                                'border-neutral-500' => ! $errors->has('description'),
                                'focus:outline-none focus:border-brand-500 hover:border-neutral-400',
                            ])
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p id="description-error" class="field__error flex items-start gap-1.5 text-sm font-medium text-rose-400">
                                <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    @if($accountEmail)
                        <p class="text-sm text-neutral-300">
                            Odpoveď ti príde na <span class="break-all font-semibold text-white">{{ $accountEmail }}</span>.
                        </p>
                    @else
                        <x-form-input name="email" type="email" label="Tvoj e-mail" placeholder="Napr. jana@example.com" icon="fa-envelope" autocomplete="email" hint="Sem ti odpíšem." :value="old('email')" required />
                    @endif

                    <div
                        class="field"
                        x-data="{
                            files: [],
                            overLimit: false,
                            pick(input) {
                                const picked = Array.from(input.files);
                                this.files = picked.map(file => ({ name: file.name, size: (file.size / 1024 / 1024).toFixed(1) }));
                                this.overLimit = picked.length > {{ $maxFiles }}
                                    || picked.some(file => file.size > {{ $maxFileMb }} * 1024 * 1024)
                                    || picked.reduce((sum, file) => sum + file.size, 0) > {{ $maxTotalMb }} * 1024 * 1024;
                            },
                        }"
                    >
                        <label for="attachments" class="field__label text-sm font-medium text-neutral-200">Prílohy <span class="font-normal text-neutral-400">(nepovinné)</span></label>
                        <p id="attachments-hint" class="field__hint text-sm text-neutral-400">
                            @if($allowsPdf)
                                Snímky obrazovky alebo fotky (JPG, PNG, WebP) a PDF
                            @else
                                Snímky obrazovky alebo fotky (JPG, PNG, WebP)
                            @endif
                            – najviac {{ $maxFiles }} súborov, každý do {{ $maxFileMb }} MB, spolu do {{ $maxTotalMb }} MB. Z fotiek sa pred uložením odstráni poloha a ďalšie údaje o fotoaparáte.
                        </p>
                        @if($hasErrors)
                            <p class="text-sm font-medium text-amber-300">Prehliadač si vybrané súbory po chybe nepamätá – ak si nejaké pripájal, vyber ich znova.</p>
                        @endif
                        <input
                            type="file"
                            name="attachments[]"
                            id="attachments"
                            multiple
                            accept="{{ $acceptedTypes }}"
                            @change="pick($event.target)"
                            aria-describedby="attachments-hint{{ $attachmentErrors ? ' attachments-error' : '' }}"
                            @if($attachmentErrors) aria-invalid="true" @endif
                            @class([
                                'w-full rounded-xl border bg-neutral-900 text-sm text-neutral-300 file:mr-4 file:min-h-12 file:cursor-pointer file:border-0 file:bg-neutral-800 file:px-4 file:py-3 file:font-semibold file:text-neutral-100 hover:file:bg-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400',
                                'border-rose-500/70' => $attachmentErrors,
                                'border-neutral-500' => ! $attachmentErrors,
                            ])
                        >

                        <ul x-show="files.length" x-cloak class="flex flex-col gap-1 text-sm text-neutral-300">
                            <template x-for="(file, index) in files" :key="index">
                                <li class="flex items-center gap-2">
                                    <i class="fa-solid fa-paperclip text-xs text-neutral-400" aria-hidden="true"></i>
                                    <span class="truncate" x-text="file.name"></span>
                                    <span class="shrink-0 text-neutral-400" x-text="file.size + ' MB'"></span>
                                </li>
                            </template>
                        </ul>
                        <p x-show="overLimit" x-cloak class="text-sm font-medium text-amber-300" role="status">
                            Vybrané súbory presahujú limit – odober niektoré, inak sa hlásenie neodošle.
                        </p>

                        @if($attachmentErrors)
                            <div id="attachments-error" class="field__error flex flex-col gap-1 text-sm font-medium text-rose-400">
                                @foreach($attachmentErrors as $message)
                                    <p class="flex items-start gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                                        <span>{{ $message }}</span>
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <x-submit-button busy-label="Odosielam…" class="sm:w-auto sm:self-start">Odoslať hlásenie</x-submit-button>

                    <p class="text-xs text-neutral-400">
                        Hlásenie spolu s prílohami uchovávam {{ $retentionMonths }} mesiacov, potom ho vymažem. Viac v <a href="{{ route('privacy') }}#nahlasenie-chyby" class="underline underline-offset-2 hover:text-white">Ochrane osobných údajov</a>.
                    </p>
                </form>
            </x-settings-section>
        </div>
    </div>
</x-layout>

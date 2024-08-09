<x-layout>
    <section class="bg-gray-700">
        <div class="flex flex-col items-center mt-20 px-6 py-8 mx-auto lg:py-0">
            <div class="w-full rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-white md:text-2xl">
                        Nahlásenie chyby
                    </h1>
                    <form class="space-y-4 md:space-y-6" action="{{ route('bugreport.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="subject" class="block mb-2 text-sm font-medium text-white">Predmet</label>
                            <input type="text" value="{{ old('subject') }}" name="subject" id="subject" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Predmet hlásenia">
                            @error('subject')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="where" class="block mb-2 text-sm font-medium text-white">Kde sa to stalo</label>
                            <input type="text" value="{{ old('where') }}" name="where" id="where" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Kde chyba nastala">
                            @error('where')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="description" class="block mb-2 text-sm font-medium text-white">Popis chyby</label>
                            <textarea name="description" id="description" rows="4" class="border sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="Popíšte chybu"></textarea>
                            @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="file" class="block mb-2 text-sm font-medium text-white">Snímka obrazovky</label>
                            <input accept=".jpg, .jpeg, .png, .bmp" type="file" name="file" id="file" class="block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('file')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="w-full text-white bg-slate-900 hover:bg-slate-950 rounded-xl py-4 font-bold tracking-widest uppercase">Nahlásiť</button>
                    </form>
                </div>
            </div>
        </div>
        @if(count($bugs) > 0 && auth()->user()->hasRole(config('constants.roles.admin')))
            <div class="my-10 container mx-auto">
                <div class="text-white font-bold tracking-wider text-xl uppercase">Nahlásené chyby</div>
                <div class="overflow-x-auto"> <!-- Added wrapper for horizontal scroll -->
                    <table class="table-auto w-full text-sm text-left rtl:text-right text-gray-300 text-gray-400">
                        <thead class="text-xs uppercase bg-gray-600 text-gray-200">
                        <tr>
                            <x-table-cell-header>#</x-table-cell-header>
                            <x-table-cell-header>Meno</x-table-cell-header>
                            <x-table-cell-header>Predmet</x-table-cell-header>
                            <x-table-cell-header>Kde?</x-table-cell-header>
                            <x-table-cell-header>Popis</x-table-cell-header>
                            <x-table-cell-header>Snímka obrazovky</x-table-cell-header>
                            <x-table-cell-header>Dátum</x-table-cell-header>
                            <x-table-cell-header>Akcia</x-table-cell-header>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bugs as $bug)
                            <x-table-row>
                                <x-table-cell>{{ $bug->id }}</x-table-cell>
                                <x-table-cell>{{ $bug->user }}</x-table-cell>
                                <x-table-cell>{{ $bug->subject }}</x-table-cell>
                                <x-table-cell>{{ $bug->where }}</x-table-cell>
                                <x-table-cell>{{ $bug->description }}</x-table-cell>
                                <x-table-cell>
                                    @if ($bug->id_file)
                                        <a href="{{ route('files.download', ['file' => $bug->id_file]) }}" target="_blank" class="text-blue-500 hover:underline">Zobraziť</a>
                                    @else
                                        Žiadna
                                    @endif
                                </x-table-cell>
                                <x-table-cell>{{ $bug->created_at->format('d.m.Y H:i') }}</x-table-cell>
                                <x-table-cell class="font-medium text-blue-500 hover:underline"><a href="{{ route('bugreport.destroy', ['bug' => $bug->id]) }}">Vymazať</a></x-table-cell>
                            </x-table-row>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </section>
</x-layout>

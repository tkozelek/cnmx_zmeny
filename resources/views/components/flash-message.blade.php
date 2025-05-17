@if(session()->has('message'))
    <button type="button" @click="closeToast()" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.duration.300ms class="fixed text-lg right-4 bottom-4 z-50 rounded-md bg-green-500 px-6 py-3 text-white transition hover:bg-green-600">
        <div class="flex items-center space-x-2">
            <span class="text-3xl"><i class="{{ session()->has('icon') ? session('icon') : 'fa fa-check' }}"></i></span>
            <p class="font-bold"> {{ session('message') }}</p>
        </div>
    </button>
@elseif(session()->has('error'))
    <button type="button" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.duration.300ms class="fixed text-lg right-4 bottom-4 z-50 rounded-md bg-red-500 px-6 py-3 text-white transition hover:bg-red-600">
        <div class="flex items-center space-x-2">
            <span class="text-3xl"><i class="{{ session()->has('icon') ? session('icon') : 'fa fa-x' }}"></i></span>
            <p class="font-bold"> {{ session('error') }}</p>
        </div>
    </button>
@endif

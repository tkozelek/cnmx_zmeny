<div>
    @if($locked)
        <button type="submit" class="rounded-t add-user-btn w-full btn py-2 bg-gray-300 tracking-wider font-bold" disabled>ZAMKNUTÝ</button>
    @elseif(isset($users) && App\Helpers::findIdInArray(auth()->user()->id, $users))
        <button wire:click="addUser" type="button" class="rounded-t add-user-btn w-full btn py-2 bg-green-300 tracking-wider font-bold">ODPISAŤ</button>
    @else
        <button wire:click="addUser" type="button" class="rounded-t add-user-btn w-full btn py-2 bg-red-300 tracking-wider font-bold">ZAPISAŤ</button>
    @endif
</div>

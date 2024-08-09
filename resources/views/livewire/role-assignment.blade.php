<div>
    <h2>Roles</h2>
    <div id="roles" class="flex border border-black p-4" wire:sortable="updateRoleOrder">
        @foreach($roles as $role)
            <div class="bg-gray-500 mb-2 mr-2 p-2 droppable" id="droppable">
                {{ $role }}
            </div>
        @endforeach
    </div>

    <h2>Users</h2>
    <div id="draggable" class="flex border border-black p-4">
        @foreach($users as $user)
            <div class="bg-gray-200 mb-2 mr-2 p-2 draggable cursor-pointer">
                {{ $user->name }}
            </div>
        @endforeach
    </div>
</div>

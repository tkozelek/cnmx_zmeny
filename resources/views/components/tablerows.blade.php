@props(['users', 'actions', 'blocked'])

@isset($users)
    @foreach($users as $user)
        <tr class="border-b hover:text-white bg-gray-800 border-gray-700 hover:bg-gray-900">
            @isset($actions)
                <td class="pl-2 w-8">
                    <input type="checkbox" class="rounded" name="selected_users[]" id="{{$user->id}}" value="{{$user->id}}">
                </td>
            @endisset
            <td scope="row" class="px-6 py-4 whitespace-nowrap">
                {{ $user->name." ".$user->lastname }}
            </td>
            <td class="px-6 py-4">
                {{ $user->email }}
            </td>
            <td class="px-6 py-4">
                @if($user->id_role == config('constants.roles.neovereny'))
                    {{ $user->created_at }}
                @elseif($user->id_role == config('constants.roles.zablokovany'))
                    {{ $user->updated_at }}
                @else
                    {{ $user->role->text }}
                @endif
            </td>
            <td class="flex flex-row flex-wrap items-center px-5">
                @isset($actions)
                    <a href="{{url('/admin/'.$user->id.'/accept')}}" class="p-2 bg-green-400 hover:bg-green-700 text-black font-bold py-2 px-4 rounded mx-1 my-1"><i class="fa-solid fa-check"></i></a>
                    <a href="{{url('/admin/'.$user->id.'/deny')}}" class="p-2 bg-red-400 hover:bg-red-700 text-black font-bold py-2 px-4 rounded mx-1 my-1"><i class="fa-solid fa-x"></i></a>
                @else
                    <a href="{{url('/admin/'.$user->id.'/edit')}}" class="p-2 bg-orange-400 hover:bg-orange-600 text-black font-bold py-2 px-4 rounded mx-1 my-1"><i class="fa-solid fa-pen"></i></a>
                @endisset
            </td>
        </tr>
    @endforeach
@endisset

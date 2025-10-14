<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Models\Shift;
use App\Models\User;

class HoursController extends Controller
{
    public function index()
    {
        $workData = $this->getData(auth()->user());

        return view('hours.index', [
            'workdata' => $workData,
            'users' => $this->getUsers() ?? null,
        ]);
    }

    public function show(User $user) {
        $workData = $this->getData($user);
        return view('hours.index', [
            'workdata' => $workData,
            'user' => $user,
            'users' => $this->getUsers() ?? null,
        ]);
    }

    private function getData(User $user) {
        return $user->shifts()->get();
    }

    private function getUsers() {
        if (auth()->user()->isAdmin())
        {
            return User::withCount('shifts')->get();
        }
        return null;
    }

    public function store(StoreShiftRequest $request) {
        $validated = $request->validated();

        $user = auth()->user();

        if (!$validated['workData']) {
            $status = $user->shifts()->whereMonth('date', $validated['month'])->delete();
            return response()->json(['status' => $status, 'message' => 'deleted']);
        }

        unset($validated['month'], $validated['year']);

        $workData = array_map(function ($item) use ($user) {
            $item['user_id'] = $user->id;
            $item['break'] = $item['break'] ?? 0;
            return $item;
        }, $validated['workData']);

        $status = Shift::upsert($workData, ['user_id', 'date'], ['start', 'end', 'break']);

        return response()->json(['status' => $status, 'message' => 'updated']);
    }
}

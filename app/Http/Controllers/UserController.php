<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegistrationRequest;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Str;

class UserController extends Controller
{
    protected $fileService;

    /**
     * Create a new controller instance.
     *
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }


    public function create() {
        return view('users.register');
    }

    public function store(UserRegistrationRequest $request) {
        $validatedData = $request->validated();
        // hash
        $validatedData['password'] = Hash::make($validatedData['password']);

        $validatedData['name'] = mb_convert_case($validatedData['name'], MB_CASE_TITLE, 'UTF-8');
        $validatedData['lastname'] = mb_convert_case($validatedData['lastname'], MB_CASE_TITLE, 'UTF-8');

        User::create($validatedData);

        // login
        // auth()->login($user);

        return redirect('/')->with('message', 'Účet vytvorený. Počkaj na schválenie.');
    }

    public function changePasswordSave(Request $request)
    {
        $formFields = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:5'
        ], $this->messages());

        $user = auth()->user();

        if (!Hash::check($request->get('current_password'), $user->password))
        {
            return back()->with('message', "Zle aktuálne heslo.");
        }

        $user->password = Hash::make($formFields['new_password']);
        $user->save();
        return back()->with('message', 'Heslo bolo úspešne zmenené.');
    }

    public function logout(Request $request) {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message','Boli ste úspešné odhlásený.');
    }

    public function login() {
        return view('users.login');
    }

    public function authenticate(Request $request) {
        $remember = $request->has('remember');
        $formFields = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], $this->messages());

        if (auth()->attempt($formFields, $remember)) {
            $request->session()->regenerate();

            return redirect('/')->with('message','Úspešné prihlásený.');
        }
        return back()->with(['message' => 'Nesprávne údajé.'])->onlyInput('email');
    }

    public function update(Request $request, User $user) {
        $formFields = $request->validate([
            'name' => ['required'],
            'lastname' => ['required'],
            'email' => ['required', 'email'],
            'id_role' => ['required'],
        ], $this->messages());

        $user->update($formFields);

        return redirect('/admin/pouzivatelia')->with(['message' => 'Uspesne zmenene.', 'edit' => 'yes']);
    }

    public function add(Request $request) {
        $formFields = $request->validate([
            'name' => ['required'],
            'lastname' => ['required'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'id_role' => 'required'
        ]);
        $formFields['password'] = Hash::make(Str::random(12));

        $formFields['name'] = mb_convert_case($formFields['name'], MB_CASE_TITLE, 'UTF-8');
        $formFields['lastname'] = mb_convert_case($formFields['lastname'], MB_CASE_TITLE, 'UTF-8');

        $user = User::create($formFields);
        $status = Password::RESET_LINK_SENT;
        $error = 0;
        try {
            $status = Password::broker('add_user')->sendResetLink(['email' => $formFields['email']]);

        } catch (\Exception $e) {
            $user->delete();
            $error = 10;
        }

        if ($status != Password::RESET_LINK_SENT || $error == 10 ) {
            return back()->with(['message' => 'Nastala chyba, kontaktujte administratora.']);
        }

        return back()->with(['message' => 'Pouzivatel uspesne vytvoreny']);
    }

    public function destroy(User $user) {
        $files = $user->files();

        foreach ($files as $file) {
            $this->fileService->deleteFile($file);
        }
        $user->days()->detach();
        $user->holidays()->delete();

        $user->delete();

        return to_route('admin.users.index')->with(['message' => 'Účet zmazaný.']);
    }

    public function getNewUserCount() {
        return User::where('id_role', config('constants.roles.neovereny'))->count();
    }

    public function messages()
    {
        return [
            'name.required' => 'Meno je potrebné.',
            'lastname.required' => 'Priezvisko je potrebné.',
            'username.required' => 'Prihlásovacie meno je potrebné.',
            'username.unique' => 'Prihlásovacie meno už je obsadené.',
            'email.required' => 'Emailová adresa je potrebná.',
            'email.unique' => 'Emailová adresa už bola použitá.',
            'password.required' => 'Heslo je potrebné.',
            'password_confirmation.required' => 'Potvrdenie heslá je potrebné.',
            'password.confirmed' => 'Hesla sa nezhoduju.',
            'password.min' => 'Heslo je moc kratke. Musi mat aspon 5 znakov.',
            'new_password' => 'Nové heslo je potrebné.',
            'new_password.min' => 'Heslo je moc kratke. Musi mat aspon 5 znakov.',
            'current_password' => 'Aktuálne heslo je potrebné.',
            'new_password.confirmed' => 'Hesla sa nezhoduju.',
        ];
    }
}

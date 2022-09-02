<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Posts;
use App\Models\SavedPosts;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Auth,
    Hash,
    Config,
    Storage,
    RateLimiter
};
use App\Http\Requests\{
    UpdatePasswordRequest,
    UpdatePhotoRequest,
    UpdateUsernameRequest
};

class DashboardController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::user()->id);

        if (null === $user->token) {
            $user->token = Str::random(60);
            $user->save();

            return back();
        }

        return Inertia::render('Dashboard/Index', [
            'title' => 'DASHBOARD',
        ]);
    }

    public function integration()
    {
        return Inertia::render('Dashboard/Integration', [
            'title' => 'CUYUNIVERSE INTEGRATION',
            'next' => 'DASHBOARD',
            'nextRoute' => 'dash.main',
        ]);
    }

    public function setting()
    {
        return Inertia::render('Dashboard/Setting', [
            'title' => 'USER PROFILE',
            'next' => 'DASHBOARD',
            'nextRoute' => 'dash.main',
        ]);
    }

    public function changePassword()
    {
        return Inertia::render('Dashboard/ChangePassword', [
            'title' => 'CHANGE PASSWORD',
            'next' => 'PROFILE',
            'nextRoute' => 'dash.setting.profile',
        ]);
    }

    public function notification()
    {
        return Inertia::render('Dashboard/Notification', [
            'notifications' => Auth::user()->unreadNotifications,
            'title' => 'NOTIFICATION',
            'next' => 'DASHBOARD',
            'nextRoute' => 'dash.main',
        ]);
    }

    public function markNotificationAsRead($id): void
    {
        $notification = Auth::user()->notifications->find($id);

        $notification->markAsRead();
    }

    public function showSavedPost()
    {
        return Inertia::render('Dashboard/SavedPosts', [
            'data' => SavedPosts::orderByDesc('id')->where('user_id', auth()->user()->id)->with(['posts.users', 'comments'])->get(),
            'title' => 'SAVED POST',
            'page' => 'Postingan yang anda simpan',
            'next' => 'BUAT POSTINGAN',
            'nextRoute' => 'posts.main',
        ]);
    }

    public function stats()
    {
        $posts = Posts::where('author', auth()->user()->username)->withCount('likes')->get();
        $likes_count = 0;

        foreach ($posts as $post) {
            $likes_count += $post->likes_count;
        }

        return Inertia::render('Dashboard/Stats', [
            'title' => 'STATISTICS',
            'next' => 'DASHBOARD',
            'nextRoute' => 'dash.main',
            'likes_count' => $likes_count,
            'posts_count' => $posts->count(),
        ]);
    }

    public function update_photo(UpdatePhotoRequest $request)
    {
        $request->validated();

        $users = new User();
        $user = $users->where('id', Auth::user()->id)->where('token', Auth::user()->token)->first();

        if ($request->hasFile('image')) {
            if (null !== $user->image) {
                Storage::delete('images/' . $user->image);
            }
            $fileName = Auth::user()->username . Str::random(60) . '.' . $request->image->getClientOriginalExtension();
            $request->file('image')->storeAs('images', $fileName);
            $user->image = $fileName;
        }
        $user->save();

        return to_route('dash.setting.profile')->with('message', 'Avatar berhasil diganti');
    }

    public function update_username(UpdateUsernameRequest $request)
    {
        $request->validated();

        $users = User::find(Auth::user()->id)->where('token', $request->token);
        $users->update([
            'username' => $request->username,
        ]);

        return to_route('dash.main')->with('message', 'Username berhasil diganti');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->validated();
        $users = User::find(Auth::user()->id)->where('token', $request->token);
        $users->update([
            'password' => Hash::make($request->newPassword),
        ]);

        return to_route('dash.setting.profile')->with('message', 'Password berhasil diganti');
    }

    public function cli_integration(Request $request)
    {
        if ($redirect = $this->checkCliRateLimiter('cli', Config::get('rate-limit.cli'))) {
            return $redirect;
        }
        $request->validate(
            [
                'isActive' => 'required|boolean',
            ],
        );

        $users = User::find(Auth::user()->id)->where('token', $request->token);
        $users->update([
            'secret' => $request->isActive ? Str::random(20) : null,
            'cuycli' => $request->isActive ? 1 : 0,
        ]);

        return redirect()->back()->with('message', $request->isActive ? 'Integrasi Cuy CLI berhasil diaktifkan ✔' : 'Integrasi Cuy CLI berhasil di non-aktifkan 🔻');
    }

    private function checkCliRateLimiter(string $postType, $perMinute = 4): \Illuminate\Http\RedirectResponse|null
    {
        $key = "cli-store-{$postType}-" . Auth::id();

        if (RateLimiter::tooManyAttempts($key, $perMinute)) {
            return to_route('dash.integration')->with('message', 'Silahkan coba lagi dalam 1 menit kedepan');
        }

        RateLimiter::hit($key);

        return null;
    }
}

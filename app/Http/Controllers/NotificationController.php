<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | MARK ONE NOTIFICATION AS READ
    |--------------------------------------------------------------------------
    */

    public function read(string $notification): RedirectResponse
    {
        $user = auth()->user();

        $item = $user
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if (is_null($item->read_at)) {
            $item->markAsRead();
        }

        /*
        |--------------------------------------------------------------------------
        | COURSE NOTIFICATION
        |--------------------------------------------------------------------------
        | For now, course approval/rejection notifications return the teacher
        | to My Courses after being marked as read.
        */

        if (! empty($item->data['course_id'])) {
            return redirect()
                ->route('teacher.my-courses');
        }

        return back();
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ALL NOTIFICATIONS AS READ
    |--------------------------------------------------------------------------
    */

    public function readAll(): RedirectResponse
    {
        auth()
            ->user()
            ->unreadNotifications
            ->markAsRead();

        return back();
    }
}
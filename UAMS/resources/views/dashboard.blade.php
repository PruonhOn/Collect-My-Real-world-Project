@php
    $user = auth()->user();
    if ($user->isSuperAdmin() || $user->isAdmin()) {
        redirect()->route('admin.dashboard')->send();
    } elseif ($user->isTeacher()) {
        redirect()->route('teacher.dashboard')->send();
    } elseif ($user->isStudent()) {
        redirect()->route('student.dashboard')->send();
    }
    redirect()->route('home')->send();
@endphp
